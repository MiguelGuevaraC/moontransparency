<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicSurveyResource extends JsonResource
{
    public function toArray($request): array
    {
        $supportsDailyMeasurements = $this->calculatorKind() !== null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'project' => $this->proyect ? [
                'id' => $this->proyect->id,
                'name' => $this->proyect->name,
            ] : null,
            'name' => $this->survey_name,
            'type' => $this->survey_type,
            'description' => $this->description,
            'status' => $this->status,
            'display_order' => $this->display_order,
            'requires_coordinates' => (bool) $this->requires_coordinates,
            'supports_daily_measurements' => $supportsDailyMeasurements,
            'expected_days' => $supportsDailyMeasurements
                ? $this->expectedDays()
                : null,
            'questions_count' => (int) $this->survey_questions_count,
            'detail_endpoint' => url('/api/survey-show/'.$this->id),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
