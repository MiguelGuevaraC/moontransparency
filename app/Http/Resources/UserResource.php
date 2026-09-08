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
     *     @OA\Property(property="type_document", type="string", nullable=true),
     *     @OA\Property(property="number_document", type="string"),
     *     @OA\Property(property="names", type="string"),
     *     @OA\Property(property="username", type="string"),
     *     @OA\Property(property="email", type="string", format="email", nullable=true, example="miguel@gmail.com"),
     *     @OA\Property(property="status", type="string", enum={"Activo", "Inactivo"}),
     *     @OA\Property(property="rol_id", type="integer", description="Rol Id", example=1),
     *     @OA\Property(property="rol", ref="#/components/schemas/Rol"),
     *     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true),
     *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true)
     * )
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type_document' => $this->type_document,
            'number_document' => $this->number_document,
            'names' => $this->names,
            'name' => $this->names,
            'username' => $this->username,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'rol_id' => $this->rol_id,
            'rol' => $this->whenLoaded('rol', fn () => $this->rol ? new RolResource($this->rol) : null),
            'permissions' => $this->when(
                $this->relationLoaded('rol') && $this->rol?->relationLoaded('permissions'),
                fn () => $this->rol->permissions->pluck('route')->filter()->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

    }
}
