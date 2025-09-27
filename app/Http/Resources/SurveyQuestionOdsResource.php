<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyQuestionOdsResource",
 *     @OA\Property(property="survey_question_id", type="integer")
 *     @OA\Property(property="ods_id", type="integer")
 * )
 */
class SurveyQuestionOdsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id ?? null,
            'survey_question_id' => $this->survey_question_id ?? null,
            'survey_question_question_text' => $this->surveyQuestion?->question_text ?? null,
            
            'ods_id' => $this->ods_id ?? null,
            'ods_name' => $this->ods?->name ?? null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s')
        ];
    }
}