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
 *     summary="Obtener información de ODS con filtros y ordenamiento",
 *     tags={"Ods"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de ODS", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="description", in="query", description="Filtrar por descripción", required=false, @OA\Schema(type="string")),
  *     @OA\Parameter(name="color", in="query", description="Color Ods", required=false, @OA\Schema(type="string")),
   *     @OA\Parameter(name="code", in="query", description="Codigo Ods", required=false, @OA\Schema(type="string")),
 
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de ODS", @OA\JsonContent(ref="#/components/schemas/Ods")),
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
 *     summary="Obtener detalles de un ODS por ID",
 *     tags={"Ods"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del ODS", required=true, @OA\Schema(type="integer", example=1)),
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
