<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Ally",
 *     title="Ally",
 *     description="Modelo de Ally",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="ruc_dni", type="string", example="123456789"),
 *     @OA\Property(property="first_name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="business_name", type="string", example="Empresa XYZ"),
 *     @OA\Property(property="description", type="string", example="Description about ally"),
 *     @OA\Property(property="link", type="string", example="ally.com"),
 *     @OA\Property(property="date_start", type="date", example="2025-01-01"),
 *     @OA\Property(property="phone", type="string", example="987654321"),
 *     @OA\Property(property="email", type="string", example="juan.perez@example.com"),
 *     @OA\Property(property="images", type="string", example="image1.jpg,image2.jpg"),
 *     @OA\Property(property="area_of_interest", type="string", example="Tecnología"),
 *     @OA\Property(property="participation_type", type="string", example="Expositor"),
 * )
 */
class AllyResource extends JsonResource
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
            'id'                 => $this->id ?? null,
            'ruc_dni'            => $this->ruc_dni ?? null,
            'first_name'         => $this->first_name ?? null,
            'last_name'          => $this->last_name ?? null,
            'business_name'      => $this->business_name ?? null,

            'description'        => $this->description ?? null,
            'link'               => $this->link ?? null,
            'date_start'         => $this->date_start ?? null,
            'phone'              => $this->phone ?? null,
            'email'              => $this->email ?? null,
            'images'             => $this->imagestable ? ImagenResource::collection($this->imagestable) : [],
            'area_of_interest'   => $this->area_of_interest ?? null,
            'participation_type' => $this->participation_type ?? null,
        ];
    }
}
