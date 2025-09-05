<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Survey;

class SurveyResource extends JsonResource
{
    public function toArray($request)
    {
        // --- Resolver POST (si soy PRE)
        $postSurveyObj = null;
        if ($this->survey_type === 'PRE') {
            // si la relación está cargada, usarla
            if ($this->relationLoaded('postSurvey') && $this->postSurvey) {
                $p = $this->postSurvey;
            } elseif (! empty($this->post_survey_id)) {
                // si no está cargada, traer la POST por id (una sola query puntual)
                $p = Survey::where('id', $this->post_survey_id)->whereNull('deleted_at')->first();
            } else {
                $p = null;
            }

            if ($p) {
                $postSurveyObj = [
                    'id' => $p->id,
                    'survey_name' => $p->survey_name,
                    'survey_type' => $p->survey_type,
                ];
            }
        }

        // --- Resolver PRE (si soy POST)
        $preSurveyObj = null;
        if ($this->survey_type === 'POST') {
            if ($this->relationLoaded('preSurvey') && $this->preSurvey) {
                $q = $this->preSurvey;
            } else {
                // buscar la PRE que tiene post_survey_id = this->id
                $q = Survey::where('post_survey_id', $this->id)->whereNull('deleted_at')->first();
            }

            if ($q) {
                $preSurveyObj = [
                    'id' => $q->id,
                    'survey_name' => $q->survey_name,
                    'survey_type' => $q->survey_type,
                ];
            }
        }

        // Siempre devolver ambos lados si existen (null cuando no)
        // Si la relación no aplica para el tipo actual, tratamos de resolver igualmente:
        // - Si no es PRE but there is a post_survey_id (rare), still show it.
        if ($postSurveyObj === null && $this->post_survey_id && $this->survey_type !== 'PRE') {
            $p2 = Survey::where('id', $this->post_survey_id)->whereNull('deleted_at')->first();
            if ($p2) {
                $postSurveyObj = [
                    'id' => $p2->id,
                    'survey_name' => $p2->survey_name,
                    'survey_type' => $p2->survey_type,
                ];
            }
        }

        if ($preSurveyObj === null && $this->survey_type !== 'POST') {
            $pre = Survey::where('post_survey_id', $this->id)->whereNull('deleted_at')->first();
            if ($pre) {
                $preSurveyObj = [
                    'id' => $pre->id,
                    'survey_name' => $pre->survey_name,
                    'survey_type' => $pre->survey_type,
                ];
            }
        }

        // is_complete: para PRE -> tiene POST, para POST -> tiene PRE
        $isComplete = false;
        if ($this->survey_type === 'PRE') {
            $isComplete = (bool) $postSurveyObj;
        } elseif ($this->survey_type === 'POST') {
            $isComplete = (bool) $preSurveyObj;
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
            'survey_link'       => $surveyLink,     // {id, survey_name, survey_type} o null

            // relaciones cargadas
            'survey_questions'  => \App\Http\Resources\SurveyQuestionResource::collection($this->whenLoaded('survey_questions')),
            'proyect'           => $this->whenLoaded('proyect', function () {
                                     return [
                                         'id' => $this->proyect->id,
                                         'name' => $this->proyect->name ?? null,
                                     ];
                                 }),

            'created_at'        => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'        => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
