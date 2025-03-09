<?php
namespace App\Http\Resources\Web;

use App\Http\Resources\ImagenResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AllyWeb",
 *     title="Ally",
 *     description="Modelo de Ally",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="ruc_dni", type="string", example="123456789"),
 *     @OA\Property(property="first_name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="business_name", type="string", example="Empresa XYZ"),
 *     @OA\Property(property="phone", type="string", example="987654321"),
 *     @OA\Property(property="email", type="string", example="juan.perez@example.com"),
 *     @OA\Property(property="images", type="string", example="image1.jpg,image2.jpg"),
 *     @OA\Property(property="area_of_interest", type="string", example="Tecnología"),
 *     @OA\Property(property="participation_type", type="string", example="Expositor"),
 * )
 */
class AllyWebResource extends JsonResource
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
            'phone'              => $this->phone ?? null,
            'email'              => $this->email ?? null,
            'images'             => $this->images ? explode(',', $this->images) :
            ($this->imagestable ? ImagenResource::collection($this->imagestable) : []),
            'area_of_interest'   => $this->area_of_interest ?? null,
            'participation_type' => $this->participation_type ?? null,
        ];
    }
}
