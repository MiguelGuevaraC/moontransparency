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
     *     @OA\Property(property="name", type="string", example="Ver usuarios"),
     *     @OA\Property(property="code", type="string", example="users.view"),
     *     @OA\Property(property="route", type="string", example="users.view"),
     *     @OA\Property(property="type", type="string", example="USUARIOS"),
     *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"}),

     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'code' => $this->route ?? null,
            'route' => $this->route ?? null,
            'type' => $this->type ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
