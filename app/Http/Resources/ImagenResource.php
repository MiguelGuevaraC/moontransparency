<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Imagenes",
 *     type="object",
 *     title="Imagen",
 *     description="Modelo de una imagen",
 *     @OA\Property(property="id", type="integer", description="ID de la imagen"),
 *     @OA\Property(property="name_table", type="string", description="Nombre de la tabla a la que pertenece la imagen"),
 *     @OA\Property(property="name_image", type="string", nullable=true, description="Nombre de la imagen"),
 *     @OA\Property(property="route", type="string", nullable=true, description="Ruta de la imagen"),
 *     @OA\Property(property="proyect_id", type="integer", nullable=true, description="ID del proyecto asociado"),
 *     @OA\Property(property="ally_id", type="integer", nullable=true, description="ID del aliado asociado"),
 *     @OA\Property(property="survey_id", type="integer", nullable=true, description="ID de la encuesta asociada"),
 *     @OA\Property(property="donation_id", type="integer", nullable=true, description="ID de la donación asociada"),
 *     @OA\Property(property="user_created_id", type="integer", nullable=true, description="ID del usuario que creó la imagen"),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, description="Fecha de creación de la imagen")
 * )
 */
class ImagenResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id'              => $this->id ?? null,
            'name_table'      => $this->name_table ?? null,
            'name_image'      => $this->name_image ?? null,
            'route'           => $this->route ?? null,
            'proyect_id'      => $this->proyect_id ?? null,
            'ally_id'         => $this->ally_id ?? null,
            'survey_id'       => $this->survey_id ?? null,
            'donation_id'     => $this->donation_id ?? null,
            'user_created_id' => $this->user_created_id ?? null,
            'created_at'      => $this->created_at ?? null,
        ];

    }
}
