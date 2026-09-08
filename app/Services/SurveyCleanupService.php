<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyCleanupAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SurveyCleanupService
{
    private const BACKUP_DISK = 'local';

    public function clean(int $surveyId, User $actor, string $reason, string $confirmation): ?SurveyCleanupAudit
    {
        return DB::transaction(function () use ($surveyId, $actor, $reason, $confirmation) {
            $survey = Survey::lockForUpdate()->find($surveyId);
            if (! $survey) {
                return null;
            }

            $snapshot = $this->snapshot($survey);
            $participationIds = collect($snapshot['participations'])->pluck('id')->all();
            if (! $participationIds) {
                throw new ConflictHttpException(
                    'La encuesta no tiene participaciones que deban limpiarse.'
                );
            }

            $backup = $this->storeBackup($survey, $snapshot);
            $counts = [
                'participations' => count($snapshot['participations']),
                'measurements' => count($snapshot['measurements']),
                'responses' => count($snapshot['responses']),
                'response_options' => count($snapshot['response_options']),
                'reopenings' => count($snapshot['reopenings']),
                'offline_mappings' => count($snapshot['offline_participations']),
                'files_preserved' => collect($snapshot['responses'])->whereNotNull('file_path')->count(),
            ];

            $offlineParticipationIds = collect($snapshot['offline_participations'])->pluck('id')->all();
            if ($offlineParticipationIds) {
                DB::table('offline_sync_measurements')
                    ->whereIn('offline_sync_participation_id', $offlineParticipationIds)
                    ->delete();
                DB::table('offline_sync_participations')->whereIn('id', $offlineParticipationIds)->delete();
            }

            DB::table('surveyed_response_options')->whereIn('surveyed_id', $participationIds)->delete();
            DB::table('surveyed_responses')->whereIn('surveyed_id', $participationIds)->delete();
            DB::table('surveyed_reopenings')->whereIn('surveyed_id', $participationIds)->delete();
            DB::table('surveyed_measurements')->whereIn('surveyed_id', $participationIds)->delete();
            DB::table('surveyeds')->whereIn('id', $participationIds)->delete();

            return SurveyCleanupAudit::create([
                'survey_id' => $survey->id,
                'performed_by' => $actor->id,
                'reason' => trim($reason),
                'confirmation' => $confirmation,
                'deleted_counts' => $counts,
                'backup_disk' => self::BACKUP_DISK,
                'backup_path' => $backup['path'],
                'backup_sha256' => $backup['sha256'],
            ]);
        }, 3);
    }

    private function snapshot(Survey $survey): array
    {
        $participations = DB::table('surveyeds')->where('survey_id', $survey->id)->get();
        $participationIds = $participations->pluck('id')->all();
        $measurements = $participationIds
            ? DB::table('surveyed_measurements')->whereIn('surveyed_id', $participationIds)->get()
            : collect();
        $responses = $participationIds
            ? DB::table('surveyed_responses')->whereIn('surveyed_id', $participationIds)->get()
            : collect();
        $offlineParticipations = $participationIds
            ? DB::table('offline_sync_participations')->whereIn('surveyed_id', $participationIds)->get()
            : collect();

        return [
            'contract' => 'survey-cleanup-backup/1.0',
            'created_at' => now()->toIso8601String(),
            'survey' => $survey->toArray(),
            'participations' => $participations->map(fn ($row) => (array) $row)->all(),
            'measurements' => $measurements->map(fn ($row) => (array) $row)->all(),
            'responses' => $responses->map(fn ($row) => (array) $row)->all(),
            'response_options' => $participationIds
                ? DB::table('surveyed_response_options')->whereIn('surveyed_id', $participationIds)->get()->map(fn ($row) => (array) $row)->all()
                : [],
            'reopenings' => $participationIds
                ? DB::table('surveyed_reopenings')->whereIn('surveyed_id', $participationIds)->get()->map(fn ($row) => (array) $row)->all()
                : [],
            'offline_participations' => $offlineParticipations->map(fn ($row) => (array) $row)->all(),
            'offline_measurements' => $offlineParticipations->isNotEmpty()
                ? DB::table('offline_sync_measurements')->whereIn('offline_sync_participation_id', $offlineParticipations->pluck('id'))->get()->map(fn ($row) => (array) $row)->all()
                : [],
        ];
    }

    private function storeBackup(Survey $survey, array $snapshot): array
    {
        $contents = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $compressed = gzencode($contents, 9);
        if ($compressed === false) {
            throw new RuntimeException('No se pudo comprimir el respaldo de la encuesta.');
        }

        $path = sprintf(
            'survey-cleanup-backups/survey-%d-%s-%s.json.gz',
            $survey->id,
            now()->format('YmdHis'),
            Str::uuid()
        );
        if (! Storage::disk(self::BACKUP_DISK)->put($path, $compressed)) {
            throw new RuntimeException('No se pudo guardar el respaldo de la encuesta.');
        }

        return [
            'path' => $path,
            'sha256' => hash('sha256', $contents),
        ];
    }
}
