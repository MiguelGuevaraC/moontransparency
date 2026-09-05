<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyedMeasurementResource extends JsonResource
{
    public function toArray($request)
    {
        $responses = $this->surveyed_responses
            ->sortBy(function ($response) {
                return [
                    (float) ($response->survey_question?->order ?? PHP_INT_MAX),
                    (int) $response->id,
                ];
            })
            ->values();

        return [
            'id' => $this->id,
            'surveyed_id' => $this->surveyed_id,
            'day_number' => $this->day_number,
            'responses' => SurveyedResponseResource::collection($responses),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
