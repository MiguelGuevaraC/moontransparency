<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyQuestionOptionRequest",
 *     type="object",
 *     required={"survey_question_id", "description"},
 *     @OA\Property(property="survey_question_id", type="integer", example=12),
 *     @OA\Property(property="description", type="string", example="Energía solar")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SurveyQuestionOption",
 *     title="SurveyQuestionOption",
 *     description="Modelo de SurveyQuestionOption",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_question_id", type="integer", example=12),
 *     @OA\Property(property="description", type="string", example="Energía solar"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
class SurveyQuestionOptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                            => $this->id ?? null,
            'survey_question_id'            => $this->survey_question_id ?? null,
            'survey_question_question_text' => $this->surveyQuestion->question_text ?? null,
            'survey_name'                   => $this->surveyQuestion->survey?->survey_name ?? null,
            'description'                   => $this->description ?? null,
            'created_at'                    => $this->created_at ?? null,
        ];
    }
}
