<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        ];
    }
}
