<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="Permission",
     *     title="Permission",
     *     description="Permission model",
     *     @OA\Property( property="id", type="integer", example="1" ),
     *     @OA\Property( property="name", type="string", example="users" ),
     *     @OA\Property( property="type", type="string", example="Tipo-01" ),
     *     @OA\Property( property="status", type="string", example="Activo" ),
     *     @OA\Property(property="person_id",type="integer",description="Person Id", example="1"),

     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
