<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Rol",
 *     type="object",
 *     required={"id", "name", "status"},
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Administrador"),
 *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"}),
 *     @OA\Property(property="permissions", type="array", @OA\Items(ref="#/components/schemas/Permission")),
 *     @OA\Property(property="permission_codes", type="array", @OA\Items(type="string"), example={"users.view", "participations.reopen"}),
 *     @OA\Property(property="menus", type="array", @OA\Items(ref="#/components/schemas/Menu")),
 *     @OA\Property(property="menu_codes", type="array", @OA\Items(type="string"), example={"users", "survey_history", "calculator"})
 * )
 */
class RolResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'permission_codes' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('route')->filter()->values()
            ),
            'menus' => MenuResource::collection($this->whenLoaded('menus')),
            'menu_codes' => $this->whenLoaded(
                'menus',
                fn () => $this->menus->pluck('code')->values()
            ),
        ];
    }
}
