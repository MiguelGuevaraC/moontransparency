<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Permission_rolResource extends JsonResource
{
 /**
 * @OA\Schema(
 *     schema="PermissionRole",
 *     title="PermissionRole",
 *     description="PermissionRole model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name_permission", type="string", example="users"),
 *     @OA\Property(property="name_rol", type="string", example="Admin"),
 *     @OA\Property(property="rol_id", type="integer", example=1),
 *     @OA\Property(property="permission_id", type="integer", example=2),
 *     @OA\Property(property="rol", ref="#/components/schemas/Rol"),
 *     @OA\Property(property="permission", ref="#/components/schemas/Permission")
 * )
 */


    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name_permission' => $this->name_permission ?? null,
            'name_rol' => $this->name_rol ?? null,
            'rol_id' => $this->rol_id ?? null,
            'permission_id' => $this->rol_id ??null,
           
         
        ];
    }
}
