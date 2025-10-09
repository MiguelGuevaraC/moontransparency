<?php

namespace App\Services;

use App\Models\SurveyQuestionOds;
use App\Models\Survey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
                'id' => $id,
            ]);
            return [
                'error' => $e->getMessage(),
                'data' => ['id' => $id],
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
                'data' => $data,
            ]);
            return [
                'error' => $e->getMessage(),
                'data' => $data,
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
                'id' => $instance->id,
                'data' => $data,
            ]);
            return [
                'error' => $e->getMessage(),
                'data' => $data,
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
                'id' => $id,
            ]);
            return [
                'error' => $e->getMessage(),
                'data' => ['id' => $id],
            ];
        }
    }

    /**
     * Devuelve preguntas de una encuesta relacionadas a ciertos ODS con data lista para gráficos
     */
  public function getSurveyCharts(
    int $surveyId,
    array $odsIds = [],
    bool $groupByEje = false
): array {
    try {
        // 1️⃣ Buscar encuesta
        $survey = Survey::with([
            'survey_questions.ods',
            'survey_questions.survey_questions_options',
            'survey_questions.surveyed_responses.surveyed_responses_options'
        ])->find($surveyId);

        if (!$survey) {
            return [
                'error' => "La encuesta con ID {$surveyId} no existe",
                'data' => ['survey_id' => $surveyId],
            ];
        }

        // 2️⃣ Filtrar preguntas por ODS (si se envían)
        $questionsCollection = empty($odsIds)
            ? $survey->survey_questions
            : $survey->survey_questions->filter(
                fn($q) => $q->ods->pluck('id')->intersect($odsIds)->isNotEmpty()
            );

        // 3️⃣ Si no hay preguntas
        if ($questionsCollection->isEmpty()) {
            return [
                'survey_id' => $survey->id,
                'survey_name' => $survey->survey_name,
                'nro_questions' => 0,
                'questions' => [],
                'message' => empty($odsIds)
                    ? 'Encuesta encontrada pero no tiene preguntas registradas'
                    : 'Encuesta encontrada pero sin preguntas vinculadas a este ODS',
            ];
        }

        // 4️⃣ Si se agrupa por EJE
        if ($groupByEje) {
            $grouped = $questionsCollection
                ->groupBy(fn($q) => $q->eje ?? 'SIN EJE')
                ->map(function ($group, $key) {
                    return [
                        'eje' => $key,
                        'questions' => $group->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'text' => $q->question_text,
                                'type' => $q->question_type,
                                'eje' => $q->eje,
                                'ods' => $q->ods->map(fn($ods) => [
                                    'id' => $ods->id,
                                    'name' => $ods->name,
                                    'code' => $ods->code ?? null,
                                ])->values(),
                                'chart' => $this->buildChartData(
                                    $q->question_type,
                                    $q->surveyed_responses,
                                    $q
                                ),
                            ];
                        })->values(),
                    ];
                })->values();

            return [
                'survey_id' => $survey->id,
                'survey_name' => $survey->survey_name,
                'nro_questions' => $survey->survey_questions->count(), // total global
                'questions' => $grouped,
                'message' => null,
            ];
        }

        // 5️⃣ Si es por ODS (plano, sin agrupar)
        $questions = $questionsCollection->map(function ($q) {
            return [
                'id' => $q->id,
                'text' => $q->question_text,
                'type' => $q->question_type,
                'eje' => $q->eje,
                'ods' => $q->ods->map(fn($ods) => [
                    'id' => $ods->id,
                    'name' => $ods->name,
                    'code' => $ods->code ?? null,
                ])->values(),
                'chart' => $this->buildChartData(
                    $q->question_type,
                    $q->surveyed_responses,
                    $q
                ),
            ];
        })->values();

        return [
            'survey_id' => $survey->id,
            'survey_name' => $survey->survey_name,
            'nro_questions' => $questions->count(),
            'questions' => $questions,
            'message' => null,
        ];
    } catch (Exception $e) {
        Log::error("charts_error", [
            'identifier' => now()->format('Ymd-His') . '-' . uniqid(),
            'error' => $e->getMessage(),
            'data' => ['survey_id' => $surveyId, 'ods_ids' => $odsIds, 'groupByEje' => $groupByEje],
        ]);

        return [
            'error' => $e->getMessage(),
            'data' => ['survey_id' => $surveyId],
        ];
    }
}



    /**
     * Construye los datos para gráficos según el tipo de pregunta
     */
    private function buildChartData(string $type, $responses, $question = null): ?array
    {
        switch (strtoupper($type)) {
            case 'NUMERICO':
                return collect([
                    'Promedio' => (float) $responses->avg('response_text'),
                    'Mínimo' => (float) $responses->min('response_text'),
                    'Máximo' => (float) $responses->max('response_text'),
                ])->map(fn($v, $k) => ['label' => $k, 'value' => $v])
                    ->values()
                    ->toArray();
            case 'FILE':
                return [
                    [
                        'label' => 'Cantidad Archivos Subidos',
                        'value' => $responses->whereNotNull('file_path')->count(),
                    ]
                ];


            case 'FECHA':
                return $responses
                    ->groupBy(fn($r) => \Carbon\Carbon::parse($r->response_text)->format('Y-m'))
                    ->map(fn($g, $date) => ['label' => $date, 'value' => $g->count()])
                    ->values()
                    ->toArray();

            case 'OPCIONES':
            case 'CHECK':
                $counts = $responses->flatMap->surveyed_responses_options
                    ->groupBy('survey_question_options_id')
                    ->map->count();

                return $question
                    ? $question->survey_questions_options->map(
                        fn($opt) => [
                            'label' => $opt->description,
                            'value' => $counts->get($opt->id, 0),
                        ]
                    )->values()->toArray()
                    : [];

            case 'CORTO':
            case 'LIBRE':
            case 'UBICACION':
            case 'LARGO':
            default:
                $counts = $responses
                    ->groupBy(fn($r) => Str::lower(trim($r->response_text ?? '')))
                    ->map->count()
                    ->sortDesc();

                $labels = $counts->keys()->take(4)->map(fn($t) => $t ?: '(vacío)');
                $values = $counts->take(4)->values();

                if ($counts->count() > 4) {
                    $labels = $labels->concat(['Otros']);
                    $values = $values->concat([$counts->slice(4)->sum()]);
                }

                return $labels
                    ->map(fn($label, $i) => [
                        'label' => ucfirst($label), // capitalizamos para mostrar bonito
                        'value' => $values[$i]
                    ])
                    ->values()
                    ->toArray();
        }
    }


}
