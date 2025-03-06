<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ProyectRequest",
 *     type="object",
 *     required={"name", "type", "status", "start_date", "end_date", "location", "budget_estimated", "nro_beneficiaries"},
 *     @OA\Property(property="name", type="string", example="Proyecto de Energía Renovable"),
 *     @OA\Property(property="type", type="string", example="Energía"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="location", type="string", pattern="^\(-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?\)$", example="(-77.032, -12.045)", description="Coordenadas GPS en formato (longitud, latitud)"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="string", format="binary"), description="Imágenes del proyecto"),
 *     @OA\Property(property="description", type="string", example="Proyecto para implementar fuentes de energía renovable en comunidades rurales."),
 *     @OA\Property(property="budget_estimated", type="number", format="float", example="500000"),
 *     @OA\Property(property="nro_beneficiaries", type="integer", example="5000"),
 *     @OA\Property(property="impact_initial", type="string", example="0"),
 *     @OA\Property(property="impact_final", type="string", example="50"),
 *     @OA\Property(property="ods", type="array", @OA\Items(type="integer"), description="IDs de los Ods relacionados al proyecto")
 * )
 */


class ProyectResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="Proyect",
 *     title="Proyecto",
 *     description="Modelo de Proyecto",
 *     @OA\Property(property="id", type="integer", example="1"),
 *     @OA\Property(property="name", type="string", example="Proyecto de Energía Renovable"),
 *     @OA\Property(property="type", type="string", example="Energía"),
 *     @OA\Property(property="status", type="string", example="Activo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
 *     @OA\Property(property="location", type="string", example="Zona rural, Perú"),
 *     @OA\Property(property="images", type="string", example="Imagen Ruta"),
 *     @OA\Property(property="description", type="string", example="Proyecto para implementar fuentes de energía renovable en comunidades rurales."),
 *     @OA\Property(property="budget_estimated", type="number", format="float", example="500000"),
 *     @OA\Property(property="nro_beneficiaries", type="integer", example="5000"),
 *     @OA\Property(property="impact_initial", type="string", example="0"),
 *     @OA\Property(property="impact_final", type="string", example="50"),
 *     @OA\Property(
 *         property="ods",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Ods")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24")
 * )
 */

    public function toArray($request)
    {
        return [
            'id'                => $this->id ?? null,
            'name'              => $this->name ?? null,
            'type'              => $this->type ?? null,
            'status'            => $this->status ?? null,
            'start_date'        => $this->start_date ?? null,
            'end_date'          => $this->end_date ?? null,
            'location'          => $this->location ?? null,
            'images' => $this->images ? explode(',', $this->images) : 
            ($this->imagestable ? ImagenResource::collection($this->imagestable) : []),
            
            'description'       => $this->description ?? null,
            'budget_estimated'  => $this->budget_estimated ?? null,
            'nro_beneficiaries' => $this->nro_beneficiaries ?? null,
            'impact_initial'    => $this->impact_initial ?? null,
            'impact_final'      => $this->impact_final ?? null,
            'ods'               => $this->ods ? OdsResource::collection($this->ods) : null,
            'created_at'        => $this->created_at,
        ];
    }

}
