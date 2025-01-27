<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Ods",
 *     title="ODS",
 *     description="Modelo de ODS",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="code", type="string", example="1"),
 *     @OA\Property(property="name", type="string", example="Fin de la Pobreza"),
 *     @OA\Property(property="description", type="string", example="Erradicar la pobreza en todas sus formas y en todo el mundo."),
 *     @OA\Property(property="color", type="string", example="#D32F2F"),  
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-26T21:44:24")
 * )
 */


class OdsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,  // Código del ODS
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,  // Color asociado al ODS
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
