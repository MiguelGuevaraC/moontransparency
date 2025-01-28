<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

 /**
 * @OA\Schema(
 *     schema="DonationRequest",
 *     type="object",
 *     required={"project_id", "activity_id", "date_donation", "ally_id", "details", "contribution_type", "amount"},
 *     @OA\Property(property="project_id", type="integer", example="101"),
 *     @OA\Property(property="activity_id", type="integer", example="2001"),
 *     @OA\Property(property="date_donation", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="ally_id", type="integer", example="3001"),
 *     @OA\Property(property="details", type="string", example="Implementación de energía renovable en zonas rurales."),
 *     @OA\Property(property="contribution_type", type="string", example="Energía"),
 *     @OA\Property(property="amount", type="number", format="float", example="500000"),
 *     @OA\Property(property="evidence", type="string", example="Ruta/a/imagen.jpg", description="Ruta del archivo de evidencia"),
 * )
 */
class DonationResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="Donation",
 *     title="Donation",
 *     description="Modelo de Donation",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="project_id", type="integer", example="101"),
 *     @OA\Property(property="activity_id", type="integer", example="2001"),
 *     @OA\Property(property="date_donation", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="ally_id", type="integer", example="3001"),
 *     @OA\Property(property="details", type="string", example="Implementación de energía renovable en zonas rurales."),
 *     @OA\Property(property="contribution_type", type="string", example="Energía"),
 *     @OA\Property(property="amount", type="number", format="float", example="500000"),
 *     @OA\Property(property="evidence", type="string", example="Ruta/a/imagen.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */




    public function toArray($request)
    {
        return [
            'id'                => $this->id ?? null,
            'project_id'        => $this->project_id ?? null,
            'activity_id'       => $this->activity_id ?? null,
            'date_donation'     => $this->date_donation ?? null,
            'ally_id'           => $this->ally_id ?? null,
            'details'           => $this->details ?? null,
            'contribution_type' => $this->contribution_type ?? null,
            'amount'            => $this->amount ?? null,
            'evidence'          => $this->evidence ?? null,
            'created_at'        => $this->created_at,
        ];
    }

}
