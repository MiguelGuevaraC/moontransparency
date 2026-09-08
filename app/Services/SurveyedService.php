<?php

namespace App\Services;

use App\Models\Respondent;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedReopening;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SurveyedService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyedById(int $id): ?Surveyed
    {
        return Surveyed::with([
            'respondent',
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

    public function createSurveyed(array $data)
    {
        return DB::transaction(function () use ($data) {
            $person = Respondent::firstOrCreate(
                ['number_document' => $data['number_document']],
                $this->respondentData($data)
            );

            $authenticatedUserId = $this->authenticatedUserId();
            $surveyed = Surveyed::firstOrCreate(
                [
                    'respondent_id' => $person->id,
                    'survey_id' => $data['survey_id'],
                ],
                array_filter([
                    'status' => Surveyed::STATUS_DRAFT,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'created_by' => $authenticatedUserId,
                    'updated_by' => $authenticatedUserId,
                ], static fn ($value) => $value !== null)
            );

            $surveyedChanges = [];
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

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function updateSurveyedById(int $id, array $data): ?Surveyed
    {
        return DB::transaction(function () use ($id, $data) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (! $surveyed) {
                return null;
            }

            if ($surveyed->status === Surveyed::STATUS_FINALIZED) {
                throw new ConflictHttpException(
                    'La encuesta está finalizada y no puede modificarse como un borrador.'
                );
            }

            $person = $this->validateSurveyedIdentity($surveyed, $data);

            $person->fill($this->respondentData($data));
            $person->save();

            $surveyed->update(array_filter([
                'status' => Surveyed::STATUS_DRAFT,
                'updated_by' => $this->authenticatedUserId(),
            ], static fn ($value) => $value !== null) + $this->coordinateData($data));

            $measurement = $this->resolveMeasurement($surveyed, $data);
            $this->saveResponses($surveyed, $person, $data['responses'] ?? [], $measurement);

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function finalizeSurveyedById(int $id, array $data): ?Surveyed
    {
        return DB::transaction(function () use ($id, $data) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (! $surveyed) {
                return null;
            }

            if ($surveyed->status === Surveyed::STATUS_FINALIZED) {
                return $this->getSurveyedById($surveyed->id);
            }

            $person = $this->validateSurveyedIdentity($surveyed, $data);

            $person->fill($this->respondentData($data));
            $person->save();

            $measurement = $this->resolveMeasurement($surveyed, $data);
            $this->saveResponses($surveyed, $person, $data['responses'] ?? [], $measurement);
            $surveyed->update($this->coordinateData($data));
            $this->validateRequiredCoordinates($surveyed);
            $this->validateRequiredResponses($surveyed);

            $surveyed->update([
                'status' => Surveyed::STATUS_FINALIZED,
                'completed_at' => now(),
            ] + array_filter([
                'updated_by' => $this->authenticatedUserId(),
            ], static fn ($value) => $value !== null));

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

    private function resolveMeasurement(Surveyed $surveyed, array $data): ?SurveyedMeasurement
    {
        $explicitDay = isset($data['day_number']) ? (int) $data['day_number'] : null;
        $derivedDays = [];

        foreach ($data['responses'] ?? [] as $index => $response) {
            $question = SurveyQuestion::whereKey($response['survey_question_id'] ?? null)
                ->where('survey_id', $surveyed->survey_id)
                ->first();

            if (! $question || Str::lower(Str::ascii(trim((string) $question->question_text))) !== 'dia de medicion') {
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

            if ($day === false || $day < 1 || $day > 7) {
                throw ValidationException::withMessages([
                    "responses.$index.survey_question_option_id" => 'La opción seleccionada no representa un día entre 1 y 7.',
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
                $answer->response_text = $response['response_text'];
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

            if ($questionType === 'OPCIONES') {
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
        $requiresLocation = $surveyed->survey()
            ->where('survey_name', GeobosquesSurveyConfigurator::SURVEY_NAME)
            ->whereHas('survey_questions', function ($query) {
                $query->where('question_type', 'UBICACION')
                    ->where('is_required', true);
            })
            ->exists();

        if ($requiresLocation && ($surveyed->latitude === null || $surveyed->longitude === null)) {
            throw ValidationException::withMessages([
                'coordinates' => 'La latitud y la longitud son obligatorias para finalizar la encuesta GeoBosques.',
            ]);
        }
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

    private function authenticatedUserId(): ?int
    {
        $id = auth('sanctum')->id() ?? auth()->id();

        return $id !== null ? (int) $id : null;
    }
}
