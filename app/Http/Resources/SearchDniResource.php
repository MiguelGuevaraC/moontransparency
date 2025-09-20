<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *   schema="SearchDni",
 *   title="SearchDni",
 *   description="Datos obtenidos de la búsqueda por DNI",
 *   @OA\Property(property="number_document", type="string", example="12345678"),
 *   @OA\Property(property="names", type="string", example="Juan Carlos"),
 *   @OA\Property(property="father_surname", type="string", example="Perez"),
 *   @OA\Property(property="mother_surname", type="string", example="Gomez"),
 *   @OA\Property(property="birthday", type="string", format="date", example="1990-05-20"),
 *   @OA\Property(property="ubigeo", type="string", example="150101")
 * )
 */
class SearchDniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'number_document' => $this['dni'] ?? null,
            'names'           => $this['nombres'] ?? null,
            'father_surname'  => $this['apepat'] ?? null,
            'mother_surname'  => $this['apemat'] ?? null,
            'birthday'        => $this['fecnac'] ?? null,
            'ubigeo'          => $this['ubigeo'] ?? null,
        ];
    }
}
