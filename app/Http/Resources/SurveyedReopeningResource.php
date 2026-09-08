<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyedReopeningResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'previous_status' => $this->previous_status,
            'previous_completed_at' => $this->previous_completed_at,
            'reopened_by' => $this->reopened_by,
            'reopened_by_user' => $this->whenLoaded(
                'reopenedBy',
                fn () => $this->reopenedBy ? new UserResource($this->reopenedBy) : null
            ),
            'reopened_at' => $this->created_at,
        ];
    }
}
