<?php

namespace App\Services;

use App\Models\SurveyQuestionOds;
use App\Models\Survey;
use Illuminate\Support\Facades\Log;
use Exception;

class SurveyQuestionOdsService
{
    /**
     * Obtener un SurveyQuestionOds por ID
     */
    public function getSurveyQuestionOdsById(int $id): SurveyQuestionOds|array|null
    {
        try {
            return SurveyQuestionOds::find($id);
        } catch (Exception $e) {
            Log::error("Error fetching SurveyQuestionOds by ID", [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return [
                'error' => $e->getMessage(),
                'data'  => ['id' => $id],
            ];
        }
    }

    /**
     * Crear un nuevo SurveyQuestionOds
     */
    public function createSurveyQuestionOds(array $data): SurveyQuestionOds|array|null
    {
        try {
            return SurveyQuestionOds::create($data);
        } catch (Exception $e) {
            Log::error("Error creating SurveyQuestionOds", [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
            return [
                'error' => $e->getMessage(),
                'data'  => $data,
            ];
        }
    }

    /**
     * Actualizar un SurveyQuestionOds existente
     */
    public function updateSurveyQuestionOds(SurveyQuestionOds $instance, array $data): SurveyQuestionOds|array|null
    {
        try {
            $instance->update(array_intersect_key($data, array_flip($instance->getFillable())));
            return $instance;
        } catch (Exception $e) {
            Log::error("Error updating SurveyQuestionOds", [
                'error' => $e->getMessage(),
                'id'    => $instance->id,
                'data'  => $data,
            ]);
            return [
                'error' => $e->getMessage(),
                'data'  => $data,
            ];
        }
    }

    /**
     * Eliminar un SurveyQuestionOds por ID
     */
    public function destroyById(int $id): bool|array
    {
        try {
            $item = SurveyQuestionOds::find($id);
            return $item ? $item->delete() : false;
        } catch (Exception $e) {
            Log::error("Error deleting SurveyQuestionOds by ID", [
                'error' => $e->getMessage(),
                'id'    => $id,
            ]);
            return [
                'error' => $e->getMessage(),
                'data'  => ['id' => $id],
            ];
        }
    }

    /**
     * Devuelve preguntas de una encuesta relacionadas a ciertos ODS con data lista para gráficos
     */
    public function getSurveyChartsByOds(int $surveyId, array $odsIds): array
    {
        try {
            $survey = Survey::with([
                'survey_questions.ods',
                'survey_questions.surveyed_responses.surveyed_responses_options'
            ])
                ->where('id', $surveyId)
                ->whereHas('survey_questions.ods', function ($q) use ($odsIds) {
                    $q->whereIn('ods_id', $odsIds);
                })
                ->firstOrFail();

            $questions = $survey->survey_questions
                ->filter(fn($q) => $q->ods->pluck('id')->intersect($odsIds)->isNotEmpty())
                ->map(function ($question) {
                    return [
                        'id'   => $question->id,
                        'text' => $question->question_text,
                        'type' => $question->question_type,
                        'chart'=> $this->buildChartData($question->question_type, $question->surveyed_responses),
                    ];
                })
                ->values();

            return [
                'survey_id'     => $survey->id,
                'survey_name'   => $survey->survey_name,
                'nro_questions' => $questions->count(),
                'questions'     => $questions,
            ];
        } catch (Exception $e) {
            Log::error("Error fetching survey charts by ODS", [
                'error'     => $e->getMessage(),
                'survey_id' => $surveyId,
                'ods_ids'   => $odsIds,
            ]);
            return [
                'error' => $e->getMessage(),
                'data'  => ['survey_id' => $surveyId, 'ods_ids' => $odsIds],
            ];
        }
    }

    /**
     * Construye los datos para gráficos según el tipo de pregunta
     */
    private function buildChartData(string $type, $responses): array|null
    {
        
        switch (strtoupper($type)) {
            case 'NUMERICO':
                return [
                    'avg' => (float) $responses->avg('response_text'),
                    'min' => (float) $responses->min('response_text'),
                    'max' => (float) $responses->max('response_text'),
                ];

            case 'CHECK':
                return $responses->flatMap->surveyed_responses_options
                    ->groupBy('survey_question_options_id')
                    ->map(fn($group) => $group->count())
                    ->toArray();

            case 'CORTO':
            case 'LARGO':
                return $responses->groupBy('response_text')
                    ->map(fn($group) => $group->count())
                    ->toArray();

            case 'FECHA':
                return $responses->groupBy(
                        fn($r) => \Carbon\Carbon::parse($r->response_text)->format('Y-m')
                    )
                    ->map(fn($group) => $group->count())
                    ->toArray();

            default:
                return null; // UBICACIÓN y FILE → no se grafican directamente
        }
    }
}
