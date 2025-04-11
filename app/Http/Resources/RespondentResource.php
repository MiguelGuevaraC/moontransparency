<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="RespondentRequest",
 *     type="object",
 *     required={"number_document", "names", "date_of_birth"},
 *     @OA\Property(property="number_document", type="string", example="12345678"),
 *     @OA\Property(property="names", type="string", example="Juan Pérez"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-20"),
 *     @OA\Property(property="phone", type="string", example="987654321"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="genero", type="string", example="masculino")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Respondent",
 *     title="Respondent",
 *     description="Modelo de Respondent",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="number_document", type="string", example="12345678"),
 *     @OA\Property(property="names", type="string", example="Juan Pérez"),
 *     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-20"),
 *     @OA\Property(property="phone", type="string", example="987654321"),
 *     @OA\Property(property="email", type="string", format="email", example="juan@example.com"),
 *     @OA\Property(property="genero", type="string", example="masculino"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24")
 * )
 */
class RespondentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id ?? null,
            'number_document' => $this->number_document ?? null,
            'names'           => $this->names ?? null,
            'date_of_birth'   => $this->date_of_birth ?? null,
            'phone'           => $this->phone ?? null,
            'email'           => $this->email ?? null,
            'genero'          => $this->genero ?? null,
            'surveyed_responses'          => $this->surveyed_responses ? SurveyedResponseResource::collection($this->surveyed_responses) : null,
            'created_at'      => $this->created_at ?? null,
        ];
    }
}
