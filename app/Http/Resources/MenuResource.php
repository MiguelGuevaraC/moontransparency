<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Menu",
 *     type="object",
 *     required={"id", "code", "name", "path", "sort_order", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=13),
 *     @OA\Property(property="code", type="string", example="calculator"),
 *     @OA\Property(property="name", type="string", example="Calculadora"),
 *     @OA\Property(property="path", type="string", example="/calculadora"),
 *     @OA\Property(property="icon", type="string", nullable=true, example="calculate"),
 *     @OA\Property(property="sort_order", type="integer", example=130),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"})
 * )
 */
class MenuResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'path' => $this->path,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
        ];
    }
}
