<?php
namespace App\Http\Resources\Web;

use Illuminate\Http\Resources\Json\JsonResource;


class ActivityWebResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="ActivityWeb",
 *     title="Activity",
 *     description="Modelo de Activity",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="name", type="string", example="Proyecto de Energía Renovable"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="objective", type="string", example="Implementar fuentes de energía renovable."),
 *     @OA\Property(property="total_amount", type="number", format="float", example="500000"),
 *     @OA\Property(property="collected_amount", type="number", format="float", example="200000"),
 *     @OA\Property(property="status", type="string", example="Activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
    public function toArray($request)
    {
        return [
            'id'               => $this->id ?? null,
            'name'             => $this->name ?? null,
            'start_date'       => $this->start_date ?? null,
            'end_date'         => $this->end_date ?? null,
            'proyect_id'       => $this->proyect_id ?? null,
            'objective'        => $this->objective ?? null,
            'total_amount'     => $this->total_amount ?? null,
            'collected_amount' => $this->collected_amount ?? null,
            'status'           => $this->status ?? null,
            'created_at'       => $this->created_at,
        ];
    }

}
