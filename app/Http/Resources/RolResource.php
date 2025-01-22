<?php

namespace App\Http\Resources;

use App\Models\Rol;
use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="Rol",
     *     title="Rol",
     *     description="Rol model",
     *     @OA\Property( property="id", type="integer", example="1" ),
     *     @OA\Property( property="name", type="string", example="Cajero" ),

     *     @OA\Property(property="person_id",type="integer",description="Person Id", example="1"),
     * @OA\Property(property="permissions", type="array", description="Lista de permisos asociados al rol",
     *     @OA\Items(ref="#/components/schemas/Permission")
     * )
     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Sin Nombre',
            'status' => $this->status ?? 'Sin Estado',
            'permissions' => $this->permissions->isEmpty()
                ? []  // Si no hay permisos, devuelve un array vacío
                : PermissionResource::collection($this->permissions),  // Usamos el recurso para formatear los permisos
        ];
    }
    
    
}
