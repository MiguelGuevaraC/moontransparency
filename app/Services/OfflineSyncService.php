<?php

namespace App\Services;

use App\Models\Household;
use App\Models\OfflineSyncBatch;
use App\Models\OfflineSyncMeasurement;
use App\Models\OfflineSyncParticipation;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class OfflineSyncService
{
    public const CONTRACT_VERSION = '1.0';

    public function __construct(private SurveyedService $surveyedService)
    {
    }

    public function synchronize(array $payload, array $attachments = [], ?User $actor = null): array
    {
        $requestHash = $this->requestHash($payload, $attachments);
        $batch = $this->resolveBatch($payload, $requestHash, $actor);

        if ($batch->status === OfflineSyncBatch::STATUS_COMPLETED && $batch->response_payload) {
            return [
                'http_status' => (int) $batch->response_status,
                'payload' => array_merge($batch->response_payload, ['replayed' => true]),
            ];
        }

        $results = [];
        foreach ($payload['items'] as $index => $item) {
            try {
                $results[] = DB::transaction(
                    fn () => $this->synchronizeItem(
                        $item,
                        $payload['device_id'],
                        $attachments,
                        $index
                    ),
                    3
                );
            } catch (ValidationException $exception) {
                $results[] = $this->itemError($item, 422, 'VALIDATION_ERROR', $exception->getMessage(), $exception->errors());
            } catch (ConflictHttpException $exception) {
                $results[] = $this->itemError($item, 409, 'CONFLICT', $exception->getMessage());
            } catch (ModelNotFoundException) {
                $results[] = $this->itemError($item, 422, 'RELATED_RESOURCE_NOT_FOUND', 'Uno de los recursos relacionados no existe.');
            } catch (QueryException $exception) {
                Log::warning('Conflicto de base de datos durante sincronización offline.', [
                    'batch_id' => $payload['batch_id'],
                    'client_participation_id' => $item['client_participation_id'] ?? null,
                    'error_code' => $exception->getCode(),
                ]);
                $results[] = $this->itemError($item, 409, 'DATABASE_CONFLICT', 'El elemento entró en conflicto con datos ya sincronizados.');
            } catch (Throwable $exception) {
                report($exception);
                $results[] = $this->itemError($item, 500, 'SYNC_ERROR', 'No se pudo sincronizar el elemento. Puede reintentarse con el mismo identificador.');
            }
        }

        $successCount = collect($results)->where('status', 'SYNCED')->count();
        $errorCount = count($results) - $successCount;
        $httpStatus = $errorCount === 0 ? 200 : 207;
        $response = [
            'contract_version' => self::CONTRACT_VERSION,
            'batch_id' => $payload['batch_id'],
            'device_id' => $payload['device_id'],
            'status' => $errorCount === 0
                ? 'FULL_SUCCESS'
                : ($successCount > 0 ? 'PARTIAL_SUCCESS' : 'FAILED'),
            'replayed' => false,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'items' => $results,
        ];

        $batch->update([
            'status' => OfflineSyncBatch::STATUS_COMPLETED,
            'response_status' => $httpStatus,
            'response_payload' => $response,
            'processed_at' => now(),
        ]);

        return ['http_status' => $httpStatus, 'payload' => $response];
    }

    private function resolveBatch(array $payload, string $requestHash, ?User $actor): OfflineSyncBatch
    {
        try {
            return OfflineSyncBatch::create([
                'batch_id' => $payload['batch_id'],
                'contract_version' => $payload['contract_version'],
                'device_id' => $payload['device_id'],
                'request_hash' => $requestHash,
                'status' => OfflineSyncBatch::STATUS_PROCESSING,
                'user_id' => $actor?->id,
            ]);
        } catch (QueryException $exception) {
            $batch = OfflineSyncBatch::where('batch_id', $payload['batch_id'])->first();
            if (! $batch) {
                throw $exception;
            }

            if (! hash_equals($batch->request_hash, $requestHash)) {
                throw new ConflictHttpException(
                    'El batch_id ya fue utilizado con un payload o archivos diferentes.'
                );
            }

            if ($batch->status === OfflineSyncBatch::STATUS_COMPLETED) {
                return $batch;
            }

            if (
                $batch->status === OfflineSyncBatch::STATUS_PROCESSING
                && $batch->updated_at?->isAfter(now()->subMinutes(5))
            ) {
                throw new ConflictHttpException('El lote todavía está siendo procesado.');
            }

            $batch->update(['status' => OfflineSyncBatch::STATUS_PROCESSING]);

            return $batch->fresh();
        }
    }

    private function synchronizeItem(
        array $item,
        string $deviceId,
        array $attachments,
        int $itemIndex
    ): array {
        $clientUpdatedAt = CarbonImmutable::parse($item['client_updated_at']);
        $itemHash = $this->itemHash($item, $attachments);
        $mapping = OfflineSyncParticipation::where(
            'client_participation_id',
            $item['client_participation_id']
        )->lockForUpdate()->first();

        if ($mapping && $mapping->device_id !== $deviceId) {
            throw new ConflictHttpException(
                'El identificador local de participación pertenece a otro dispositivo.'
            );
        }

        if ($mapping && hash_equals($mapping->last_payload_hash, $itemHash)) {
            $surveyed = $this->surveyedService->getSurveyedById((int) $mapping->surveyed_id);
            if (! $surveyed) {
                throw new ConflictHttpException('La participación sincronizada ya no está disponible.');
            }

            return $this->itemSuccess($item, $mapping, $surveyed, true);
        }

        if ($mapping && $mapping->last_client_updated_at->isAfter($clientUpdatedAt)) {
            throw new ConflictHttpException(
                'El servidor contiene una versión más reciente de esta participación.'
            );
        }

        $this->validateRelatedResources($item, $itemIndex);
        $baseData = $this->baseSurveyedData($item, $attachments);
        $surveyed = $mapping
            ? Surveyed::lockForUpdate()->find($mapping->surveyed_id)
            : $this->findExistingSurveyed($item);

        if ($mapping && ! $surveyed) {
            throw new ConflictHttpException(
                'La participación asociada al identificador local ya no está disponible.'
            );
        }

        if ($surveyed?->status === Surveyed::STATUS_FINALIZED) {
            if ($item['action'] !== 'FINALIZE') {
                throw new ConflictHttpException(
                    'La participación está FINALIZADA y debe reabrirse antes de sincronizar cambios.'
                );
            }

            $mapping = $mapping ?: OfflineSyncParticipation::create([
                'client_participation_id' => $item['client_participation_id'],
                'device_id' => $deviceId,
                'surveyed_id' => $surveyed->id,
                'last_client_updated_at' => $clientUpdatedAt,
                'last_payload_hash' => $itemHash,
            ]);

            return $this->itemSuccess($item, $mapping, $this->surveyedService->getSurveyedById($surveyed->id), true);
        }

        $surveyed = $surveyed
            ? $this->surveyedService->updateSurveyedById($surveyed->id, $baseData)
            : $this->surveyedService->createSurveyed($baseData);

        $mapping = $mapping ?: OfflineSyncParticipation::create([
            'client_participation_id' => $item['client_participation_id'],
            'device_id' => $deviceId,
            'surveyed_id' => $surveyed->id,
            'last_client_updated_at' => $clientUpdatedAt,
            'last_payload_hash' => $itemHash,
        ]);

        foreach ($item['measurements'] ?? [] as $measurement) {
            $this->synchronizeMeasurement($mapping, $surveyed, $baseData, $measurement, $attachments);
        }

        if ($item['action'] === 'FINALIZE') {
            $surveyed = $this->surveyedService->finalizeSurveyedById($surveyed->id, $baseData);
        } else {
            $surveyed = $this->surveyedService->getSurveyedById($surveyed->id);
        }

        $mapping->update([
            'last_client_updated_at' => $clientUpdatedAt,
            'last_payload_hash' => $itemHash,
        ]);

        return $this->itemSuccess($item, $mapping->fresh(), $surveyed, false);
    }

    private function synchronizeMeasurement(
        OfflineSyncParticipation $mapping,
        Surveyed $surveyed,
        array $baseData,
        array $measurement,
        array $attachments
    ): void {
        $clientMapping = OfflineSyncMeasurement::where(
            'client_measurement_id',
            $measurement['client_measurement_id']
        )->lockForUpdate()->first();
        $dayMapping = OfflineSyncMeasurement::where('offline_sync_participation_id', $mapping->id)
            ->where('day_number', $measurement['day_number'])
            ->lockForUpdate()
            ->first();

        if (
            $clientMapping
            && (
                (int) $clientMapping->offline_sync_participation_id !== (int) $mapping->id
                || (int) $clientMapping->day_number !== (int) $measurement['day_number']
            )
        ) {
            throw new ConflictHttpException(
                'El identificador local de medición ya pertenece a otra participación o día.'
            );
        }

        if ($dayMapping && $dayMapping->client_measurement_id !== $measurement['client_measurement_id']) {
            throw new ConflictHttpException(
                'El día ya fue sincronizado con otro identificador local de medición.'
            );
        }

        $this->surveyedService->updateSurveyedById($surveyed->id, array_merge($baseData, [
            'day_number' => $measurement['day_number'],
            'responses' => $this->prepareResponses($measurement['responses'] ?? [], $attachments),
        ]));
        $serverMeasurement = SurveyedMeasurement::where('surveyed_id', $surveyed->id)
            ->where('day_number', $measurement['day_number'])
            ->firstOrFail();

        if (! $clientMapping) {
            OfflineSyncMeasurement::create([
                'offline_sync_participation_id' => $mapping->id,
                'client_measurement_id' => $measurement['client_measurement_id'],
                'surveyed_measurement_id' => $serverMeasurement->id,
                'day_number' => $measurement['day_number'],
            ]);
        }
    }

    private function validateRelatedResources(array $item, int $itemIndex): void
    {
        if (! Survey::whereKey($item['survey_id'])->exists()) {
            throw ValidationException::withMessages([
                "items.$itemIndex.survey_id" => 'La encuesta indicada no existe.',
            ]);
        }

        if (
            ! empty($item['household_code'])
            && ! Household::where('code', $item['household_code'])->exists()
        ) {
            throw ValidationException::withMessages([
                "items.$itemIndex.household_code" => 'El ID del hogar indicado no existe.',
            ]);
        }
    }

    private function findExistingSurveyed(array $item): ?Surveyed
    {
        $respondent = Respondent::where('number_document', $item['number_document'])->first();

        return $respondent
            ? Surveyed::where('respondent_id', $respondent->id)
                ->where('survey_id', $item['survey_id'])
                ->lockForUpdate()
                ->first()
            : null;
    }

    private function baseSurveyedData(array $item, array $attachments): array
    {
        return array_filter([
            'number_document' => $item['number_document'],
            'names' => $item['names'],
            'date_of_birth' => $item['date_of_birth'] ?? null,
            'phone' => $item['phone'] ?? null,
            'email' => $item['email'] ?? null,
            'genero' => $item['genero'] ?? null,
            'household_code' => $item['household_code'] ?? null,
            'survey_id' => $item['survey_id'],
            'latitude' => $item['latitude'] ?? null,
            'longitude' => $item['longitude'] ?? null,
            'responses' => $this->prepareResponses($item['responses'] ?? [], $attachments),
        ], static fn ($value) => $value !== null);
    }

    private function prepareResponses(array $responses, array $attachments): array
    {
        return collect($responses)->map(function (array $response) use ($attachments) {
            $attachmentKey = $response['attachment_key'] ?? null;
            unset($response['attachment_key']);

            if ($attachmentKey && isset($attachments[$attachmentKey])) {
                $response['file'] = $attachments[$attachmentKey];
            }

            return $response;
        })->all();
    }

    private function itemSuccess(
        array $item,
        OfflineSyncParticipation $mapping,
        Surveyed $surveyed,
        bool $replayed
    ): array {
        $measurements = OfflineSyncMeasurement::where('offline_sync_participation_id', $mapping->id)
            ->orderBy('day_number')
            ->get()
            ->map(fn (OfflineSyncMeasurement $measurement) => [
                'client_measurement_id' => $measurement->client_measurement_id,
                'day_number' => $measurement->day_number,
                'surveyed_measurement_id' => $measurement->surveyed_measurement_id,
            ])->values()->all();

        return [
            'client_participation_id' => $item['client_participation_id'],
            'status' => 'SYNCED',
            'http_status' => 200,
            'replayed' => $replayed,
            'surveyed_id' => $surveyed->id,
            'surveyed_status' => $surveyed->status,
            'can_edit' => $surveyed->status === Surveyed::STATUS_DRAFT,
            'completed_at' => $surveyed->completed_at?->toIso8601String(),
            'server_updated_at' => $surveyed->updated_at?->toIso8601String(),
            'measurements' => $measurements,
        ];
    }

    private function itemError(
        array $item,
        int $httpStatus,
        string $code,
        string $message,
        array $errors = []
    ): array {
        return array_filter([
            'client_participation_id' => $item['client_participation_id'] ?? null,
            'status' => 'ERROR',
            'http_status' => $httpStatus,
            'code' => $code,
            'message' => $message,
            'errors' => $errors ?: null,
        ], static fn ($value) => $value !== null);
    }

    private function requestHash(array $payload, array $attachments): string
    {
        return hash('sha256', json_encode([
            'payload' => $payload,
            'attachments' => $this->attachmentHashes($attachments),
        ], JSON_THROW_ON_ERROR));
    }

    private function itemHash(array $item, array $attachments): string
    {
        $usedKeys = collect($item['responses'] ?? [])
            ->merge(collect($item['measurements'] ?? [])->flatMap(fn ($measurement) => $measurement['responses'] ?? []))
            ->pluck('attachment_key')
            ->filter()
            ->all();

        return hash('sha256', json_encode([
            'item' => $item,
            'attachments' => $this->attachmentHashes(array_intersect_key($attachments, array_flip($usedKeys))),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, UploadedFile>  $attachments
     */
    private function attachmentHashes(array $attachments): array
    {
        ksort($attachments);

        return collect($attachments)->mapWithKeys(
            fn (UploadedFile $file, string $key) => [$key => hash_file('sha256', $file->getRealPath())]
        )->all();
    }
}
