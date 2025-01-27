<?php

namespace App\Http\Controllers;

use App\Http\Requests\OdsRequest\IndexOdsRequest;
use App\Http\Resources\OdsResource;
use App\Models\Ods;
use App\Services\OdsService;
use Illuminate\Http\Request;

class OdsController extends Controller
{
    protected $proyectService;

    public function __construct(OdsService $proyectService)
    {
        $this->proyectService = $proyectService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/ods",
 *     summary="Obtener información de proyectos con filtros y ordenamiento",
 *     tags={"Ods"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de proyecto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", description="Filtrar por tipo de proyecto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="status", in="query", description="Filtrar por estado de proyecto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="start_date", in="query", description="Filtrar por fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="end_date", in="query", description="Filtrar por fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="description", in="query", description="Filtrar por descripción", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="budget_estimated", in="query", description="Filtrar por presupuesto estimado", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="nro_beneficiaries", in="query", description="Filtrar por número de beneficiarios", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="impact_initial", in="query", description="Filtrar por impacto inicial", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="impact_final", in="query", description="Filtrar por impacto final", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de proyectos", @OA\JsonContent(ref="#/components/schemas/Ods")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexOdsRequest $request)
    {

        return $this->getFilteredResults(
            Ods::class,
            $request,
            Ods::filters,
            Ods::sorts,
            OdsResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/ods/{id}",
 *     summary="Obtener detalles de un proyecto por ID",
 *     tags={"Ods"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del proyecto", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Odso encontrado", @OA\JsonContent(ref="#/components/schemas/Ods")),
 *     @OA\Response(response=404, description="Odso no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Odso no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->proyectService->getOdsById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Odso No Encontrado',
            ], 404);
        }

        return new OdsResource($rol);
    }

}
