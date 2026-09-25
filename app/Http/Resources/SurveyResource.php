<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    public function toArray($request)
    {
        $requiresCoordinates = (bool) $this->requires_coordinates;
        // --- POST cuando soy PRE (usando ?->)
        $postSurveyObj = null;
        if ($this->survey_type === 'PRE') {
            // Intentamos tomar la relación si existe (null-safe). Si no, usamos post_survey_id mínimo.
            $p = $this->postSurvey?->only(['id', 'survey_name', 'survey_type']) ?? null;

            if ($p) {
                $postSurveyObj = $p;
            } elseif (! empty($this->post_survey_id)) {
                $postSurveyObj = [
                    'id' => (int) $this->post_survey_id,
                    'survey_name' => null,
                    'survey_type' => null,
                ];
            }
        }

        // --- PRE cuando soy POST (usando ?->)
        $preSurveyObj = null;
        if ($this->survey_type === 'POST') {
            $q = $this->preSurvey?->only(['id', 'survey_name', 'survey_type']) ?? null;
            if ($q) {
                $preSurveyObj = $q;
            }
        }

        // --- Casos generales (mostrar enlace mínimo si existe id)
        if ($postSurveyObj === null && ! empty($this->post_survey_id)) {
            $postSurveyObj = [
                'id' => (int) $this->post_survey_id,
                'survey_name' => null,
                'survey_type' => null,
            ];
        }

        if ($preSurveyObj === null && $this->survey_type !== 'POST') {
            // No hacemos consultas extra: si la relación existe la tomamos con ?->, si no, dejamos null.
            $pre = $this->preSurvey?->only(['id', 'survey_name', 'survey_type']) ?? null;
            if ($pre) {
                $preSurveyObj = $pre;
            }
        }

        // is_complete:
        if ($this->survey_type === 'PRE') {
            $isComplete = (bool) ($postSurveyObj);
        } elseif ($this->survey_type === 'POST') {
            // Si la relación preSurvey está presente el null-safe devolverá el id; si no, será null
            $isComplete = (bool) $this->preSurvey?->id;
        } else {
            $isComplete = false;
        }

        // survey_link: si soy POST muestro PRE; si soy PRE muestro POST
        $surveyLink = null;
        if ($this->survey_type === 'POST') {
            $surveyLink = $preSurveyObj;
        } elseif ($this->survey_type === 'PRE') {
            $surveyLink = $postSurveyObj;
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'proyect_id' => $this->proyect_id,
            'survey_name' => $this->survey_name,
            'survey_type' => $this->survey_type,
            'kind' => $this->calculatorKind(),
            'description' => $this->description,
            'status' => $this->status,
            'display_order' => $this->display_order,
            'requires_coordinates' => $requiresCoordinates,
            'coordinate_capture' => $requiresCoordinates
                ? $this->coordinateCapture()
                : null,
            'expected_days' => $this->expectedDays(),
            'household_identifier' => $this->householdIdentifierConfiguration(),

            // estado y links
            'is_complete' => $isComplete,
            'survey_link' => $surveyLink,     // {id, survey_name|null, survey_type|null} o null

            // relaciones (se accede con ?-> / fallback vacío si no hay nada)
            'survey_questions' => $this->survey_questions ? SurveyQuestionResource::collection($this->survey_questions) : null,

            // proyect usando null-safe
            'proyect' => $this->proyect ? [
                'id' => $this->proyect?->id,
                'name' => $this->proyect?->name ?? null,
            ] : null,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function coordinateCapture(): array
    {
        $capture = config('geobosques.coordinate_capture');
        $questionsByKey = $this->survey_questions
            ->whereNotNull('calculator_key')
            ->keyBy('calculator_key');

        $capture['fields'] = collect($capture['fields'])
            ->map(function (array $field) use ($questionsByKey) {
                $field['survey_question_id'] = $questionsByKey
                    ->get($field['calculator_key'])?->id;

                return $field;
            })
            ->values()
            ->all();

        return $capture;
    }

    private function householdIdentifierConfiguration(): ?array
    {
        $question = $this->survey_questions
            ->firstWhere('calculator_key', 'household.identifier');

        if (! $question) {
            return null;
        }

        $isMonitoring = $this->survey_type === 'POST';
        $preSurvey = $isMonitoring ? $this->preSurvey : null;

        return [
            'survey_question_id' => $question->id,
            'required' => true,
            'mode' => $isMonitoring ? 'SEARCHABLE_SELECT' : 'FREE_TEXT',
            'uniqueness' => 'GLOBAL',
            'max_length' => 64,
            'linked_pre_survey' => $preSurvey ? [
                'id' => $preSurvey->id,
                'survey_name' => $preSurvey->survey_name,
            ] : null,
            'options_endpoint' => $isMonitoring
                ? url('/api/survey-show/'.$this->id.'/household-options')
                : null,
            'authenticated_options_endpoint' => $isMonitoring
                ? url('/api/survey/'.$this->id.'/household-options')
                : null,
        ];
    }
}
