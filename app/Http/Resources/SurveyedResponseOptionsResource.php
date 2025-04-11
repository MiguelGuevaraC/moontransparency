<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class SurveyedResponseOptionsResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="SurveyedResponseOptions",
 *     title="SurveyedResponse",
 *     description="Modelo de respuesta de un encuestado",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="response_text", type="string", nullable=true, example="Sí, uso paneles solares."),
 *     @OA\Property(property="survey_question_id", type="integer", example=5, description="ID de la pregunta respondida"),
 *     @OA\Property(property="surveyed_id", type="integer", example=10, description="ID del registro del encuestado"),
 *     @OA\Property(property="respondent_id", type="integer", nullable=true, example=3, description="ID de la persona encuestada"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */


public function toArray($request)
{
    return [
        'id'               => $this->id ?? null,

        'surveyed_response_id'=> $this->id ?? null,
        'survey_question_options_id'=> $this->id ?? null,
        'survey_question_options'=> $this->survey_question_options ?? null,
        'surveyed_id'=> $this->id ?? null,
        'respondent_id'=> $this->id ?? null,

        'created_at'       => $this->created_at,
    ];
}



}
