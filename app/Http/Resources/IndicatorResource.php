<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @OA\Schema(
 *     schema="IndicatorRequest",
 *     type="object",
 *     required={"proyect_id", "indicator_name", "target_value", "progress_value", "unit"},
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="indicator_name", type="string", example="Energía Renovable"),
 *     @OA\Property(property="target_value", type="number", format="float", example="1000000"),
 *     @OA\Property(property="progress_value", type="number", format="float", example="500000"),
 *     @OA\Property(property="unit", type="string", example="KWh"),
 * )
 */

class IndicatorResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="Indicator",
 *     title="Indicator",
 *     description="Modelo de Indicator",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="proyect_id", type="integer", example="101"),
 *     @OA\Property(property="indicator_name", type="string", example="Energía Renovable"),
 *     @OA\Property(property="target_value", type="number", format="float", example="1000000"),
 *     @OA\Property(property="progress_value", type="number", format="float", example="500000"),
 *     @OA\Property(property="unit", type="string", example="KWh"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
public function toArray($request)
{

    return [
        'id'               => $this->id ?? null,
        'proyect_id'       => $this->proyect_id ?? null,
        'project'       => $this->project ?? null,
        'indicator_name'   => $this->indicator_name ?? null,
        'target_value'     => $this->target_value ?? null,
        'progress_value'   => $this->progress_value ?? null,
        'unit'             => $this->unit ?? null,
        'created_at'       => $this->created_at,
    ];
}


}
