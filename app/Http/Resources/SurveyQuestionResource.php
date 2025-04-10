<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyQuestionRequest",
 *     type="object",
 *     required={"survey_id", "question_text", "question_type"},
 *     @OA\Property(property="survey_id", type="integer", example=101),
 *     @OA\Property(property="question_text", type="string", example="¿Qué fuentes de energía utiliza en su hogar?"),
 *     @OA\Property(property="question_type", type="string", example="multiple_choice")
 * )
 */

/**
 * @OA\Schema(
 *     schema="SurveyQuestion",
 *     title="SurveyQuestion",
 *     description="Modelo de SurveyQuestion",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_id", type="integer", example=101),
 *     @OA\Property(property="question_text", type="string", example="¿Qué fuentes de energía utiliza en su hogar?"),
 *     @OA\Property(property="question_type", type="string", example="multiple_choice"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
class SurveyQuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'            => $this->id ?? null,
            'survey_id'     => $this->survey_id ?? null,
            'question_text' => $this->question_text ?? null,
            'question_type' => $this->question_type ?? null,
            'survey_name' => $this?->survey?->survey_name ?? null,
            'survey_questions_options' => $this->survey_questions_options ?? [],
            'created_at'    => $this->created_at ?? null,
        ];
    }
}
