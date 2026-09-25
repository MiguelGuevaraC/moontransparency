<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedReopening;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SurveyedService
{
    protected $commonService;

    public function __construct(
        CommonService $commonService,
        private SurveyResponseValueValidator $responseValueValidator,
        private SurveyAlertService $surveyAlertService
    )
    {
        $this->commonService = $commonService;
    }

    public function getSurveyedById(int $id, ?User $actor = null): ?Surveyed
    {
        return Surveyed::query()
            ->visibleTo($actor)
            ->with([
            'respondent',
            'household',
            'createdBy.rol',
            'updatedBy.rol',
            'reopenings.reopenedBy.rol',
            'survey.proyect',
            'survey.survey_questions.survey_questions_options',
            'surveyed_responses.survey_question.survey_questions_options',
            'surveyed_responses.surveyed_responses_options.survey_question_options',
            'surveyed_responses.measurement',
            'measurements.surveyed_responses.survey_question.survey_questions_options',
            'measurements.surveyed_responses.surveyed_responses_options.survey_question_options',
            'measurements.surveyed_responses.measurement',
            ])->find($id);
    }

    public function createSurveyed(array $data, ?User $actor = null)
    {
        $actor ??= auth('sanctum')->user() ?? auth()->user();

        return DB::transaction(function () use ($data, $actor) {
            $data = $this->synchronizeHouseholdCode($data);
            $person = Respondent::firstOrCreate(
                ['number_document' => $data['number_document']],
                $this->respondentData($data)
            );
            $person = Respondent::lockForUpdate()->findOrFail($person->id);

            $authenticatedUserId = $actor?->id ?? $this->authenticatedUserId();
            $participationKey = [
                'respondent_id' => $person->id,
                'survey_id' => $data['survey_id'],
            ];
            $surveyed = Surveyed::where($participationKey)->lockForUpdate()->first();
            $action = $surveyed ? 'UPDATED' : 'CREATED';
            if ($surveyed) {
                $this->assertEditableBy($surveyed, $actor);
            }
            if (! $surveyed) {
                $surveyed = Surveyed::create($participationKey + array_filter([
                    'status' => Surveyed::STATUS_DRAFT,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'created_by' => $authenticatedUserId,
                    'updated_by' => $authenticatedUserId,
                ], static fn ($value) => $value !== null));
            }

            $surveyedChanges = [];
            $household = $this->resolveHousehold($surveyed, $person, $data);
            $surveyedChanges['household_id'] = $household?->id;
            if (! $surveyed->status) {
                $surveyedChanges['status'] = Surveyed::STATUS_DRAFT;
            }
            if ($authenticatedUserId !== null) {
                $surveyedChanges['updated_by'] = $authenticatedUserId;
                if (! $surveyed->created_by) {
                    $surveyedChanges['created_by'] = $authenticatedUserId;
                }
            }
            $surveyedChanges += $this->coordinateData($data);
            if ($surveyedChanges) {
                $surveyed->update($surveyedChanges);
            }

            $measurement = $this->resolveMeasurement($surveyed, $data);
            $this->saveResponses($surveyed, $person, $data['responses'] ?? [], $measurement);
            $this->synchronizeHouseholdResponses($surveyed, $person, $household);
            $this->synchronizeCoordinates($surveyed, $data);

            $this->surveyAlertService->notifyAdministrators($surveyed, $actor, $action);

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function updateSurveyedById(int $id, array $data, ?User $actor = null): ?Surveyed
    {
        $actor ??= auth('sanctum')->user() ?? auth()->user();

        return DB::transaction(function () use ($id, $data, $actor) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (! $surveyed) {
                return null;
            }

            $this->assertEditableBy($surveyed, $actor);

            if ($surveyed->status === Surveyed::STATUS_FINALIZED) {
                throw new ConflictHttpException(
                    'La encuesta está finalizada y no puede modificarse como un borrador.'
                );
            }

            $data = $this->synchronizeHouseholdCode($data);

            $person = $this->validateSurveyedIdentity($surveyed, $data);

            $person->fill($this->respondentData($data));
            $person->save();

            $household = $this->resolveHousehold($surveyed, $person, $data);

            $surveyed->update(array_filter([
                'status' => Surveyed::STATUS_DRAFT,
                'household_id' => $household?->id,
                'updated_by' => $actor?->id ?? $this->authenticatedUserId(),
            ], static fn ($value) => $value !== null) + $this->coordinateData($data));

            $measurement = $this->resolveMeasurement($surveyed, $data);
            $this->saveResponses($surveyed, $person, $data['responses'] ?? [], $measurement);
            $this->synchronizeHouseholdResponses($surveyed, $person, $household);
            $this->synchronizeCoordinates($surveyed, $data);

            $this->surveyAlertService->notifyAdministrators($surveyed, $actor, 'UPDATED');

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function finalizeSurveyedById(int $id, array $data, ?User $actor = null): ?Surveyed
    {
        $actor ??= auth('sanctum')->user() ?? auth()->user();

        return DB::transaction(function () use ($id, $data, $actor) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (! $surveyed) {
                return null;
            }

            $this->assertEditableBy($surveyed, $actor);

            if ($surveyed->status === Surveyed::STATUS_FINALIZED) {
                return $this->getSurveyedById($surveyed->id);
            }

            $data = $this->synchronizeHouseholdCode($data);

            $person = $this->validateSurveyedIdentity($surveyed, $data);

            $person->fill($this->respondentData($data));
            $person->save();

            $household = $this->resolveHousehold($surveyed, $person, $data);

            $measurement = $this->resolveMeasurement($surveyed, $data);
            $this->saveResponses($surveyed, $person, $data['responses'] ?? [], $measurement);
            $this->synchronizeHouseholdResponses($surveyed, $person, $household);
            $surveyed->update(['household_id' => $household?->id] + $this->coordinateData($data));
            $this->synchronizeCoordinates($surveyed, $data);
            $this->validateRequiredCoordinates($surveyed);
            $this->validateRequiredResponses($surveyed);
            $this->validateHouseholdSelection($surveyed);

            $surveyed->update([
                'status' => Surveyed::STATUS_FINALIZED,
                'completed_at' => now(),
            ] + array_filter([
                'updated_by' => $actor?->id ?? $this->authenticatedUserId(),
            ], static fn ($value) => $value !== null));

            $this->surveyAlertService->notifyAdministrators($surveyed, $actor, 'FINALIZED');

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function reopenSurveyedById(int $id, string $reason, User $actor): ?Surveyed
    {
        return DB::transaction(function () use ($id, $reason, $actor) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (! $surveyed) {
                return null;
            }

            if ($surveyed->status !== Surveyed::STATUS_FINALIZED) {
                throw new ConflictHttpException(
                    'Solo se puede reabrir una encuesta que se encuentre FINALIZADA.'
                );
            }

            SurveyedReopening::create([
                'surveyed_id' => $surveyed->id,
                'previous_status' => $surveyed->status,
                'previous_completed_at' => $surveyed->completed_at,
                'reason' => trim($reason),
                'reopened_by' => $actor->id,
            ]);

            $surveyed->update([
                'status' => Surveyed::STATUS_DRAFT,
                'completed_at' => null,
                'updated_by' => $actor->id,
            ]);

            return $this->getSurveyedById($surveyed->id);
        });
    }

    private function validateSurveyedIdentity(Surveyed $surveyed, array $data): Respondent
    {
        if ((int) $surveyed->survey_id !== (int) $data['survey_id']) {
            throw ValidationException::withMessages([
                'survey_id' => 'La encuesta enviada no corresponde al registro que se intenta actualizar.',
            ]);
        }

        $person = Respondent::find($surveyed->respondent_id);

        if (! $person || $person->number_document !== $data['number_document']) {
            throw ValidationException::withMessages([
                'number_document' => 'El encuestado enviado no corresponde al registro que se intenta actualizar.',
            ]);
        }

        return $person;
    }

    private function respondentData(array $data): array
    {
        return array_filter([
            'names' => $data['names'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'genero' => $data['genero'] ?? null,
        ], static fn ($value) => $value !== null);
    }

    private function resolveHousehold(Surveyed $surveyed, Respondent $person, array $data): ?Household
    {
        $survey = Survey::with('preSurvey')->findOrFail($surveyed->survey_id);
        $currentHousehold = $surveyed->household_id
            ? Household::find($surveyed->household_id)
            : null;
        $requestedCode = isset($data['household_code'])
            ? trim((string) $data['household_code'])
            : null;

        if ($this->usesHouseholdIdentifier($survey)) {
            if ($survey->survey_type === 'POST') {
                return $this->resolveMonitoringHousehold(
                    $survey,
                    $surveyed,
                    $currentHousehold,
                    $requestedCode
                );
            }

            return $this->resolveBaselineHousehold(
                $surveyed,
                $currentHousehold,
                $requestedCode
            );
        }

        if ($requestedCode !== null && $requestedCode !== '') {
            $requestedHousehold = $this->householdByCode($requestedCode);

            if (! $requestedHousehold) {
                throw ValidationException::withMessages([
                    'household_code' => 'El ID del hogar indicado no existe.',
                ]);
            }

            if ($currentHousehold && $currentHousehold->id !== $requestedHousehold->id) {
                throw ValidationException::withMessages([
                    'household_code' => 'La participación ya pertenece a otro hogar y no puede reasignarse.',
                ]);
            }

            return $requestedHousehold;
        }

        if ($currentHousehold) {
            return $currentHousehold;
        }

        $previousHouseholdId = Surveyed::where('respondent_id', $person->id)
            ->where('id', '<>', $surveyed->id)
            ->whereNotNull('household_id')
            ->orderByDesc('id')
            ->value('household_id');
        $previousHousehold = $previousHouseholdId
            ? Household::find($previousHouseholdId)
            : null;

        if ($previousHousehold) {
            return $previousHousehold;
        }

        $household = Household::create([
            'code' => 'PENDING-'.(string) Str::uuid(),
        ]);
        $household->update([
            'code' => Household::formatCode($household->id),
        ]);

        return $household;
    }

    private function synchronizeHouseholdCode(array $data): array
    {
        $surveyId = $data['survey_id'] ?? null;
        if (! $surveyId) {
            return $data;
        }

        $questionIds = SurveyQuestion::where('survey_id', $surveyId)
            ->where('calculator_key', 'household.identifier')
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (! $questionIds) {
            return $data;
        }

        $topLevelCode = array_key_exists('household_code', $data)
            ? $this->normalizeHouseholdCode($data['household_code'])
            : null;
        $responseCodes = collect($data['responses'] ?? [])
            ->filter(fn (array $response) => in_array(
                (int) ($response['survey_question_id'] ?? 0),
                $questionIds,
                true
            ))
            ->map(fn (array $response) => $this->normalizeHouseholdCode(
                $response['response_text'] ?? null
            ))
            ->filter(static fn ($code) => $code !== null)
            ->unique()
            ->values();

        if (($topLevelCode !== null && mb_strlen($topLevelCode) > 64)
            || $responseCodes->contains(fn (string $code) => mb_strlen($code) > 64)) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar no debe superar los 64 caracteres.',
            ]);
        }

        if ($responseCodes->count() > 1) {
            throw ValidationException::withMessages([
                'household_code' => 'Las respuestas contienen más de un ID de hogar.',
            ]);
        }

        $responseCode = $responseCodes->first();
        if ($topLevelCode !== null && $responseCode !== null && $topLevelCode !== $responseCode) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar no coincide con la respuesta de la encuesta.',
            ]);
        }

        $code = $topLevelCode ?? $responseCode;
        if ($code !== null) {
            $data['household_code'] = $code;
            foreach ($data['responses'] ?? [] as $index => $response) {
                if (in_array((int) ($response['survey_question_id'] ?? 0), $questionIds, true)) {
                    $data['responses'][$index]['response_text'] = $code;
                }
            }
        }

        return $data;
    }

    private function resolveBaselineHousehold(
        Surveyed $surveyed,
        ?Household $currentHousehold,
        ?string $requestedCode
    ): ?Household
    {
        if ($requestedCode === null) {
            return $currentHousehold;
        }

        $existing = $this->householdByCode($requestedCode);
        if ($existing && $existing->id !== $currentHousehold?->id) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar ya está registrado y debe ser único globalmente.',
            ]);
        }

        if ($currentHousehold) {
            if ($currentHousehold->code !== $requestedCode) {
                try {
                    $currentHousehold->update(['code' => $requestedCode]);
                } catch (QueryException) {
                    throw ValidationException::withMessages([
                        'household_code' => 'El ID del hogar ya está registrado y debe ser único globalmente.',
                    ]);
                }
            }

            return $currentHousehold->fresh();
        }

        try {
            return Household::create(['code' => $requestedCode]);
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar ya está registrado y debe ser único globalmente.',
            ]);
        }
    }

    private function resolveMonitoringHousehold(
        Survey $survey,
        Surveyed $surveyed,
        ?Household $currentHousehold,
        ?string $requestedCode
    ): ?Household
    {
        if ($requestedCode === null && ! $currentHousehold) {
            return null;
        }

        $preSurvey = $survey->preSurvey;
        if (! $preSurvey) {
            throw ValidationException::withMessages([
                'household_code' => 'La encuesta de monitoreo no tiene una encuesta de línea base vinculada.',
            ]);
        }

        $household = $requestedCode !== null
            ? $this->householdByCode($requestedCode, true)
            : Household::whereKey($currentHousehold->id)->lockForUpdate()->first();
        $isEligible = $household && Surveyed::where('survey_id', $preSurvey->id)
            ->where('status', Surveyed::STATUS_FINALIZED)
            ->where('household_id', $household->id)
            ->exists();

        if (! $isEligible) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar no pertenece a una línea base finalizada vinculada.',
            ]);
        }

        $alreadyUsed = Surveyed::where('survey_id', $survey->id)
            ->where('household_id', $household->id)
            ->where('id', '<>', $surveyed->id)
            ->exists();

        if ($alreadyUsed) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar ya fue utilizado en esta encuesta de monitoreo.',
            ]);
        }

        return $household;
    }

    private function validateHouseholdSelection(Surveyed $surveyed): void
    {
        $survey = Survey::find($surveyed->survey_id);
        if ($survey && $this->usesHouseholdIdentifier($survey) && ! $surveyed->household_id) {
            throw ValidationException::withMessages([
                'household_code' => 'El ID del hogar es obligatorio para finalizar la encuesta.',
            ]);
        }
    }

    private function synchronizeHouseholdResponses(
        Surveyed $surveyed,
        Respondent $person,
        ?Household $household
    ): void
    {
        if (! $household) {
            return;
        }

        $questions = SurveyQuestion::where('survey_id', $surveyed->survey_id)
            ->where('calculator_key', 'household.identifier')
            ->get();

        foreach ($questions as $question) {
            $answers = SurveyedResponse::where('surveyed_id', $surveyed->id)
                ->where('survey_question_id', $question->id)
                ->get();

            if ($answers->isEmpty()) {
                SurveyedResponse::create([
                    'respondent_id' => $person->id,
                    'surveyed_id' => $surveyed->id,
                    'survey_question_id' => $question->id,
                    'response_text' => $household->code,
                ]);

                continue;
            }

            foreach ($answers as $answer) {
                $answer->update(['response_text' => $household->code]);
            }
        }
    }

    private function usesHouseholdIdentifier(Survey $survey): bool
    {
        return $survey->survey_questions()
            ->where('calculator_key', 'household.identifier')
            ->exists();
    }

    private function householdByCode(string $code, bool $lock = false): ?Household
    {
        $query = Household::whereRaw('LOWER(code) = ?', [mb_strtolower($code)]);

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function normalizeHouseholdCode(mixed $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $normalized = trim((string) $code);

        return $normalized === '' ? null : $normalized;
    }

    private function resolveMeasurement(Surveyed $surveyed, array $data): ?SurveyedMeasurement
    {
        $explicitDay = isset($data['day_number']) ? (int) $data['day_number'] : null;
        $derivedDays = [];

        foreach ($data['responses'] ?? [] as $index => $response) {
            $question = SurveyQuestion::whereKey($response['survey_question_id'] ?? null)
                ->where('survey_id', $surveyed->survey_id)
                ->first();

            if (! $question || $question->calculator_key !== 'measurement.day') {
                continue;
            }

            $optionIds = array_values(array_unique(array_map(
                'intval',
                $response['survey_question_option_id'] ?? []
            )));

            if (count($optionIds) !== 1) {
                throw ValidationException::withMessages([
                    "responses.$index.survey_question_option_id" => 'Debe seleccionar exactamente un día de medición.',
                ]);
            }

            $description = SurveyQuestionOption::whereKey($optionIds[0])
                ->where('survey_question_id', $question->id)
                ->value('description');
            $day = filter_var($description, FILTER_VALIDATE_INT);

            $expectedDays = $surveyed->survey?->expectedDays() ?? config('surveying.default_expected_days', 7);

            if ($day === false || $day < 1 || $day > $expectedDays) {
                throw ValidationException::withMessages([
                    "responses.$index.survey_question_option_id" => "La opción seleccionada no representa un día entre 1 y $expectedDays.",
                ]);
            }

            $derivedDays[] = (int) $day;
        }

        $derivedDays = array_values(array_unique($derivedDays));

        if (count($derivedDays) > 1) {
            throw ValidationException::withMessages([
                'day_number' => 'Las respuestas contienen más de un día de medición.',
            ]);
        }

        $derivedDay = $derivedDays[0] ?? null;

        if ($explicitDay !== null && $derivedDay !== null && $explicitDay !== $derivedDay) {
            throw ValidationException::withMessages([
                'day_number' => 'El día enviado no coincide con la opción de Día de medición.',
            ]);
        }

        $dayNumber = $explicitDay ?? $derivedDay;

        if ($dayNumber === null) {
            return null;
        }

        $measurement = SurveyedMeasurement::withTrashed()->firstOrNew([
            'surveyed_id' => $surveyed->id,
            'day_number' => $dayNumber,
        ]);

        if ($measurement->exists && $measurement->trashed()) {
            $measurement->restore();
        }

        if (! $measurement->exists) {
            $measurement->save();
        }

        SurveyedResponse::where('surveyed_id', $surveyed->id)
            ->whereNull('surveyed_measurement_id')
            ->update(['surveyed_measurement_id' => $measurement->id]);

        return $measurement;
    }

    private function saveResponses(
        Surveyed $surveyed,
        Respondent $person,
        array $responses,
        ?SurveyedMeasurement $measurement = null
    ): void {
        foreach ($responses as $index => $response) {
            $question = SurveyQuestion::whereKey($response['survey_question_id'])
                ->where('survey_id', $surveyed->survey_id)
                ->first();

            if (! $question) {
                throw ValidationException::withMessages([
                    "responses.$index.survey_question_id" => 'La pregunta no pertenece a la encuesta seleccionada.',
                ]);
            }

            $answerKeys = [
                'respondent_id' => $person->id,
                'surveyed_id' => $surveyed->id,
                'surveyed_measurement_id' => $measurement?->id,
                'survey_question_id' => $question->id,
            ];
            $answerQuery = SurveyedResponse::withTrashed()->where($answerKeys);
            $answer = (clone $answerQuery)->whereNull('deleted_at')->first()
                ?? $answerQuery->first()
                ?? new SurveyedResponse($answerKeys);

            if ($answer->exists) {
                SurveyedResponse::where($answerKeys)
                    ->where('id', '<>', $answer->id)
                    ->delete();
            }

            if (array_key_exists('response_text', $response)) {
                $answer->response_text = $this->responseValueValidator->normalize(
                    $question,
                    $response['response_text'],
                    "responses.$index.response_text"
                );
            }

            if (
                $question->question_type === 'FILE'
                && isset($response['file'])
                && $response['file'] instanceof UploadedFile
            ) {
                $answer->file_path = $response['file']->store('survey_files/', 'public');
            }

            $answer->respondent_id = $person->id;
            $answer->surveyed_id = $surveyed->id;
            $answer->surveyed_measurement_id = $measurement?->id;
            $answer->survey_question_id = $question->id;

            if ($answer->trashed()) {
                $answer->restore();
            }

            $answer->save();

            if (
                $question->question_type === 'OPCIONES'
                && array_key_exists('survey_question_option_id', $response)
            ) {
                $this->syncResponseOptions(
                    $answer,
                    $surveyed,
                    $person,
                    $question,
                    $response['survey_question_option_id'] ?? [],
                    $index
                );
            }
        }
    }

    private function validateRequiredResponses(Surveyed $surveyed): void
    {
        $requiredQuestions = SurveyQuestion::where('survey_id', $surveyed->survey_id)
            ->where('is_required', true)
            ->get();

        if ($requiredQuestions->isEmpty()) {
            return;
        }

        $answers = SurveyedResponse::where('surveyed_id', $surveyed->id)
            ->whereIn('survey_question_id', $requiredQuestions->pluck('id'))
            ->with('surveyed_responses_options')
            ->get()
            ->keyBy('survey_question_id');

        $errors = [];

        foreach ($requiredQuestions as $question) {
            $answer = $answers->get($question->id);
            $questionType = strtoupper((string) $question->question_type);

            if ($question->calculator_key === 'location.latitude') {
                $isAnswered = $surveyed->latitude !== null;
            } elseif ($question->calculator_key === 'location.longitude') {
                $isAnswered = $surveyed->longitude !== null;
            } elseif ($questionType === 'OPCIONES') {
                $isAnswered = $answer && $answer->surveyed_responses_options->isNotEmpty();
            } elseif ($questionType === 'FILE') {
                $isAnswered = $answer && filled($answer->file_path);
            } else {
                $isAnswered = $answer && filled($answer->response_text);
            }

            if (! $isAnswered) {
                $errors['responses.'.$question->id] = sprintf(
                    'La pregunta obligatoria %s no tiene una respuesta válida.',
                    $question->id
                );
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function validateRequiredCoordinates(Surveyed $surveyed): void
    {
        $requiresLocation = $surveyed->survey()->where('requires_coordinates', true)->exists();

        if ($requiresLocation && ($surveyed->latitude === null || $surveyed->longitude === null)) {
            throw ValidationException::withMessages([
                'coordinates' => 'La latitud y la longitud son obligatorias para finalizar la encuesta GeoBosques.',
            ]);
        }
    }

    private function synchronizeCoordinates(Surveyed $surveyed, array $data): void
    {
        if (! $surveyed->survey()->where('requires_coordinates', true)->exists()) {
            return;
        }

        $questions = SurveyQuestion::where('survey_id', $surveyed->survey_id)
            ->whereIn('calculator_key', ['location.latitude', 'location.longitude'])
            ->get()
            ->keyBy('calculator_key');

        if ($questions->isEmpty()) {
            return;
        }

        $answers = SurveyedResponse::where('surveyed_id', $surveyed->id)
            ->whereIn('survey_question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('survey_question_id');

        $latitude = array_key_exists('latitude', $data)
            ? $data['latitude']
            : $answers->get($questions->get('location.latitude')?->id)?->response_text;
        $longitude = array_key_exists('longitude', $data)
            ? $data['longitude']
            : $answers->get($questions->get('location.longitude')?->id)?->response_text;

        $latitude = filled($latitude) ? $latitude : null;
        $longitude = filled($longitude) ? $longitude : null;

        if ($latitude === null && $longitude === null) {
            return;
        }

        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                'coordinates' => 'La latitud y la longitud deben enviarse juntas.',
            ]);
        }

        $errors = [];
        if (! is_numeric($latitude) || (float) $latitude < -90 || (float) $latitude > 90) {
            $errors['latitude'] = 'La latitud debe ser numérica y estar entre -90 y 90.';
        }
        if (! is_numeric($longitude) || (float) $longitude < -180 || (float) $longitude > 180) {
            $errors['longitude'] = 'La longitud debe ser numérica y estar entre -180 y 180.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $surveyed->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    private function coordinateData(array $data): array
    {
        $coordinates = [];

        if (array_key_exists('latitude', $data)) {
            $coordinates['latitude'] = $data['latitude'];
        }

        if (array_key_exists('longitude', $data)) {
            $coordinates['longitude'] = $data['longitude'];
        }

        return $coordinates;
    }

    private function syncResponseOptions(
        SurveyedResponse $answer,
        Surveyed $surveyed,
        Respondent $person,
        SurveyQuestion $question,
        array $optionIds,
        int $responseIndex
    ): void {
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));

        $validOptionIds = SurveyQuestionOption::where('survey_question_id', $question->id)
            ->whereIn('id', $optionIds)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        if (count($validOptionIds) !== count($optionIds)) {
            throw ValidationException::withMessages([
                "responses.$responseIndex.survey_question_option_id" => 'Una opción no pertenece a la pregunta seleccionada.',
            ]);
        }

        $optionQuery = SurveyedResponseOption::where('surveyed_response_id', $answer->id);

        if ($validOptionIds) {
            $optionQuery->whereNotIn('survey_question_options_id', $validOptionIds)->delete();
        } else {
            $optionQuery->delete();
        }

        foreach ($validOptionIds as $optionId) {
            $optionKeys = [
                'surveyed_response_id' => $answer->id,
                'survey_question_options_id' => $optionId,
            ];
            $selectedOptionQuery = SurveyedResponseOption::withTrashed()->where($optionKeys);
            $selectedOption = (clone $selectedOptionQuery)->whereNull('deleted_at')->first()
                ?? $selectedOptionQuery->first()
                ?? new SurveyedResponseOption($optionKeys);

            if ($selectedOption->exists) {
                SurveyedResponseOption::where($optionKeys)
                    ->where('id', '<>', $selectedOption->id)
                    ->delete();
            }

            $selectedOption->respondent_id = $person->id;
            $selectedOption->surveyed_id = $surveyed->id;

            if ($selectedOption->trashed()) {
                $selectedOption->restore();
            }

            $selectedOption->save();
        }
    }

    public function destroyById($id)
    {
        return Surveyed::find($id)?->delete() ?? false;
    }

    private function assertEditableBy(Surveyed $surveyed, ?User $actor): void
    {
        if (! $surveyed->isEditableBy($actor)) {
            throw new AccessDeniedHttpException(
                'No puede modificar una participación creada por otro encuestador.'
            );
        }
    }

    private function authenticatedUserId(): ?int
    {
        $id = auth('sanctum')->id() ?? auth()->id();

        return $id !== null ? (int) $id : null;
    }
}
