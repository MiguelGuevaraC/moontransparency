<?php
namespace App\Http\Controllers;

use App\Http\Requests\IndicatorRequest\IndexIndicatorRequest;
use App\Http\Requests\IndicatorRequest\StoreIndicatorRequest;
use App\Http\Requests\IndicatorRequest\UpdateIndicatorRequest;
use App\Http\Resources\IndicatorResource;
use App\Models\Indicator;
use App\Services\IndicatorService;
use Illuminate\Http\Request;

class IndicatorController extends Controller
{
    protected $activityService;

    public function __construct(IndicatorService $activityService)
    {
        $this->activityService = $activityService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/Indicator",
 *     summary="Obtener información de Indicators con filtros y ordenamiento",
 *     tags={"Indicator"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de Indicator", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="project_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="indicator_name", in="query", description="Filtrar por nombre del indicador", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="target_value", in="query", description="Filtrar por valor objetivo", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="progress_value", in="query", description="Filtrar por valor de progreso", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="unit", in="query", description="Filtrar por unidad del indicador", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de Indicators", @OA\JsonContent(ref="#/components/schemas/Indicator")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexIndicatorRequest $request)
    {

        return $this->getFilteredResults(
            Indicator::class,
            $request,
            Indicator::filters,
            Indicator::sorts,
            IndicatorResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/Indicator/{id}",
 *     summary="Obtener detalles de un Indicator por ID",
 *     tags={"Indicator"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Indicator", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación encontrado", @OA\JsonContent(ref="#/components/schemas/Indicator")),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Donación no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->activityService->getIndicatorById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        return new IndicatorResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/Indicator",
 *     summary="Crear Indicator",
 *     tags={"Indicator"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/IndicatorRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Indicator")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="La validación falló.")))
 * )
 */
    public function store(StoreIndicatorRequest $request)
    {
        $rol = $this->activityService->createIndicator($request->validated());
        return new IndicatorResource($rol);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/Indicator/{id}",
 *     summary="Actualizar un Indicator",
 *     tags={"Indicator"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
  *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/IndicatorRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Indicator")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Datos inválidos"))),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateIndicatorRequest $request, $id)
    {

        $validatedData = $request->validated();

        if ($id == 1) {
            return response()->json([
                'message' => 'Esta Donación No puede ser Editado',
            ], 422);
        }
        $rol = $this->activityService->getIndicatorById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->activityService->updateIndicator($rol, $validatedData);
        return new IndicatorResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/Indicator/{id}",
 *     summary="Eliminar un Indicator por ID",
 *     tags={"Indicator"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Donación eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $activity = $this->activityService->getIndicatorById($id);

        if (! $activity) {
            return response()->json([
                'error' => 'Donación No Encontrado.',
            ], 404);
        }
        $activity = $this->activityService->destroyById($id);

        return response()->json([
            'message' => 'Indicator eliminado exitosamente',
        ], 200);
    }

}
