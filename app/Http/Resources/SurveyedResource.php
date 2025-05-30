<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @OA\Schema(
 *     schema="SurveyRequest",
 *     type="object",
 *     required={"proyect_id", "survey_name", "description"},
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="survey_name", type="string", example="Encuesta de Energía Renovable"),
 *     @OA\Property(property="description", type="string", example="Encuesta para evaluar el uso de energía renovable en zonas rurales."),
 * )
 */

class SurveyedResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="Survey",
 *     title="Survey",
 *     description="Modelo de Survey",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="survey_name", type="string", example="Encuesta de Energía Renovable"),
 *     @OA\Property(property="description", type="string", example="Encuesta para evaluar el uso de energía renovable en zonas rurales."),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
public function toArray($request)
{
    return [
        'id'               => $this->id ?? null,
        'respondent_id'=> $this->respondent_id ?? null,
        'respondent_names'=> $this->respondent?->names ?? null,
        'proyect_name'=> $this?->survey?->proyect?->name ?? null,
        'survey_id'=> $this->survey_id ?? null,
        'survey'=> $this->survey ?? null,
        'surveyed_responses'=> $this->surveyed_responses ? SurveyedResponseResource::collection($this->surveyed_responses) : null,
        'created_at'       => $this->created_at,
    ];
}



}
