<?php
namespace App\Services;

use App\Models\Respondent;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'survey.proyect',
            'surveyed_responses.survey_question.survey_questions_options',
            'surveyed_responses.surveyed_responses_options.survey_question_options',
        ])->find($id);
    }

    public function createSurveyed(array $data)
    {
        return DB::transaction(function () use ($data) {
            $person = Respondent::firstOrCreate(
                ['number_document' => $data['number_document']],
                $this->respondentData($data)
            );

            $surveyed = Surveyed::firstOrCreate(
                [
                    'respondent_id' => $person->id,
                    'survey_id' => $data['survey_id'],
                ],
                ['status' => Surveyed::STATUS_DRAFT]
            );

            if (!$surveyed->status) {
                $surveyed->update(['status' => Surveyed::STATUS_DRAFT]);
            }

            $this->saveResponses($surveyed, $person, $data['responses'] ?? []);

            return $this->getSurveyedById($surveyed->id);
        });
    }

    public function updateSurveyedById(int $id, array $data): ?Surveyed
    {
        return DB::transaction(function () use ($id, $data) {
            $surveyed = Surveyed::lockForUpdate()->find($id);

            if (!$surveyed) {
                return null;
            }

            if ((int) $surveyed->survey_id !== (int) $data['survey_id']) {
                throw ValidationException::withMessages([
                    'survey_id' => 'La encuesta enviada no corresponde al registro que se intenta actualizar.',
                ]);
            }

            $person = Respondent::find($surveyed->respondent_id);

            if (!$person || $person->number_document !== $data['number_document']) {
                throw ValidationException::withMessages([
                    'number_document' => 'El encuestado enviado no corresponde al registro que se intenta actualizar.',
                ]);
            }

            $person->fill($this->respondentData($data));
            $person->save();

            $surveyed->update(['status' => Surveyed::STATUS_DRAFT]);

            $this->saveResponses($surveyed, $person, $data['responses'] ?? []);

            return $this->getSurveyedById($surveyed->id);
        });
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

    private function saveResponses(Surveyed $surveyed, Respondent $person, array $responses): void
    {
        foreach ($responses as $index => $response) {
            $question = SurveyQuestion::whereKey($response['survey_question_id'])
                ->where('survey_id', $surveyed->survey_id)
                ->first();

            if (!$question) {
                throw ValidationException::withMessages([
                    "responses.$index.survey_question_id" => 'La pregunta no pertenece a la encuesta seleccionada.',
                ]);
            }

            $answerKeys = [
                'respondent_id' => $person->id,
                'surveyed_id' => $surveyed->id,
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

}
