<?php

namespace App\Http\Resources;

use App\Models\Surveyed;
use App\Services\GeobosquesMapService;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyRequest",
 *     type="object",
 *     required={"proyect_id", "survey_name", "description"},
 *
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="survey_name", type="string", example="Encuesta de Energía Renovable"),
 *     @OA\Property(property="description", type="string", example="Encuesta para evaluar el uso de energía renovable en zonas rurales."),
 *     @OA\Property(property="latitude", type="number", format="double", nullable=true, minimum=-90, maximum=90, example=-6.39454),
 *     @OA\Property(property="longitude", type="number", format="double", nullable=true, minimum=-180, maximum=180, example=-79.822403),
 * )
 */
class SurveyedResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="Survey",
     *     title="Survey",
     *     description="Modelo de Survey",
     *
     *     @OA\Property(property="id", type="integer", example="1"),
     *     @OA\Property(property="proyect_id", type="integer", example="101"),
     *     @OA\Property(property="survey_name", type="string", example="Encuesta de Energía Renovable"),
     *     @OA\Property(property="description", type="string", example="Encuesta para evaluar el uso de energía renovable en zonas rurales."),
     *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
     * )
     */
    public function toArray($request)
    {
        $status = $this->status ?? Surveyed::STATUS_DRAFT;
        $geobosquesMap = app(GeobosquesMapService::class)->build(
            $this->latitude,
            $this->longitude
        );
        $orderedResponses = $this->surveyed_responses
            ? $this->surveyed_responses
                ->sortBy(function ($response) {
                    return [
                        (int) ($response->measurement?->day_number ?? 0),
                        (float) ($response->survey_question?->order ?? PHP_INT_MAX),
                        (int) $response->id,
                    ];
                })
                ->values()
            : collect();

        return [
            'id' => $this->id ?? null,
            'respondent_id' => $this->respondent_id ?? null,
            'respondent_names' => $this->respondent?->names ?? null,
            'proyect_name' => $this?->survey?->proyect?->name ?? null,
            'survey_id' => $this->survey_id ?? null,
            'status' => $status,
            'can_edit' => $status === Surveyed::STATUS_DRAFT,
            'completed_at' => $this->completed_at,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'geobosques_map' => $geobosquesMap,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_by_user' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? new UserResource($this->createdBy) : null),
            'updated_by_user' => $this->whenLoaded('updatedBy', fn () => $this->updatedBy ? new UserResource($this->updatedBy) : null),
            'survey' => $this->survey ?? null,
            'respondent' => $this->respondent ? new RespondentResource($this->respondent) : null,
            'surveyed_responses' => SurveyedResponseResource::collection($orderedResponses),
            'measurements' => SurveyedMeasurementResource::collection($this->measurements),
            'reopenings' => SurveyedReopeningResource::collection($this->whenLoaded('reopenings')),
            'created_at' => $this->created_at,
        ];
    }
}
