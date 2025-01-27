<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProyectResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="Proyect",
 *     title="Proyecto",
 *     description="Modelo de Proyecto",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="name", type="string", example="Proyecto de Energía Renovable"),
 *     @OA\Property(property="type", type="string", example="Energía"),
 *     @OA\Property(property="status", type="string", example="Activo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="location", type="string", example="Zona rural, Perú"),
 *    @OA\Property(property="images", type="string", example="Imagen Ruta"),
 *     @OA\Property(property="description", type="string", example="Proyecto para implementar fuentes de energía renovable en comunidades rurales."),
 *     @OA\Property(property="budget_estimated", type="number", format="float", example="500000"),
 *     @OA\Property(property="nro_beneficiaries", type="integer", example="5000"),
 *     @OA\Property(property="impact_initial", type="string", example="0%"),
 *     @OA\Property(property="impact_final", type="string", example="50%"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-26T21:44:24")
 * )
 */
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'type' => $this->type,
        'status' => $this->status,
        'start_date' => $this->start_date,
        'end_date' => $this->end_date,
        'location' => $this->location,
        'images' => $this->images ? explode(',', $this->images) : [],  // Asumiendo que las imágenes están almacenadas como una cadena separada por comas
        'description' => $this->description,
        'budget_estimated' => $this->budget_estimated,
        'nro_beneficiaries' => $this->nro_beneficiaries,
        'impact_initial' => $this->impact_initial,
        'impact_final' => $this->impact_final,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
}


}