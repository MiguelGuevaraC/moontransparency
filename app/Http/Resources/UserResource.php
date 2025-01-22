<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="User",
     *     title="User",
     *     description="User model",
     *     @OA\Property( property="id", type="integer", example="1" ),
     *     @OA\Property( property="email", type="string", example="miguel@gmail.com" ),

     *     @OA\Property(property="rol_id",type="integer",description="Rol Id", example="1"),
     *     @OA\Property(property="rol", ref="#/components/schemas/Rol")
     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Sin Nombre',
            'username' => $this->username ?? 'Sin Correo',
            'rol_id' => $this->rol_id ?? 'Sin Tipo Usuario ID',
            'rol' => $this->rol ? new RolResource($this->rol) : 'Sin Tipo Usuario',
        ];

    }
}
