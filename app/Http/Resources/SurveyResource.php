<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SurveyQuestionResource;

class SurveyResource extends JsonResource
{
    public function toArray($request)
    {
        // --- POST cuando soy PRE (usando ?->)
        $postSurveyObj = null;
        if ($this->survey_type === 'PRE') {
            // Intentamos tomar la relación si existe (null-safe). Si no, usamos post_survey_id mínimo.
            $p = $this->postSurvey?->only(['id', 'survey_name', 'survey_type']) ?? null;

            if ($p) {
                $postSurveyObj = $p;
            } elseif (!empty($this->post_survey_id)) {
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
        if ($postSurveyObj === null && !empty($this->post_survey_id)) {
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
            'id'                => $this->id,
            'proyect_id'        => $this->proyect_id,
            'survey_name'       => $this->survey_name,
            'survey_type'       => $this->survey_type,
            'description'       => $this->description,
            'status'            => $this->status,

            // estado y links
            'is_complete'       => $isComplete,
            'survey_link'       => $surveyLink,     // {id, survey_name|null, survey_type|null} o null

            // relaciones (se accede con ?-> / fallback vacío si no hay nada)
            'survey_questions'  => $this->survey_questions ? SurveyQuestionResource::collection($this->survey_questions) : null,

            // proyect usando null-safe
            'proyect'           => $this->proyect ? [
                                        'id' => $this->proyect?->id,
                                        'name' => $this->proyect?->name ?? null,
                                    ] : null,

            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
