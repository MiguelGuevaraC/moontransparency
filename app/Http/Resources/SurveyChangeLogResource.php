<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyChangeLog",
 *     type="object",
 *     required={"id", "survey_id", "action", "entity_type", "description", "changes", "created_at"},
 *
 *     @OA\Property(property="id", type="integer", example=15),
 *     @OA\Property(property="survey_id", type="integer", example=4),
 *     @OA\Property(property="action", type="string", enum={"CREATED", "UPDATED", "DELETED"}),
 *     @OA\Property(property="entity_type", type="string", enum={"SURVEY", "QUESTION", "OPTION"}),
 *     @OA\Property(property="entity_id", type="integer", nullable=true),
 *     @OA\Property(property="description", type="string", example="Se modificó una pregunta."),
 *     @OA\Property(property="changes", type="object", description="Campos modificados indexados por nombre, cada uno con old y new."),
 *     @OA\Property(property="user", type="object", nullable=true),
 *     @OA\Property(property="ip_address", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class SurveyChangeLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'survey_id' => $this->survey_id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'description' => $this->description,
            'changes' => $this->changes,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->names,
                'username' => $this->user->username,
            ] : null,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
