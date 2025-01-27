<?php
namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest\IndexActivityRequest;
use App\Http\Requests\ActivityRequest\StoreActivityRequest;
use App\Http\Requests\ActivityRequest\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\ActivityService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/activity",
 *     summary="Obtener información de Activitys con filtros y ordenamiento",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de Activity", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="start_date", in="query", description="Filtrar por fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="end_date", in="query", description="Filtrar por fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="project_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="objective", in="query", description="Filtrar por objetivo de la actividad", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="total_amount", in="query", description="Filtrar por monto total de la actividad", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="collected_amount", in="query", description="Filtrar por monto recolectado", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="status", in="query", description="Filtrar por estado de la actividad", required=false, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Lista de Activitys", @OA\JsonContent(ref="#/components/schemas/Activity")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */


    public function index(IndexActivityRequest $request)
    {

        return $this->getFilteredResults(
            Activity::class,
            $request,
            Activity::filters,
            Activity::sorts,
            ActivityResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Obtener detalles de un Activity por ID",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Activity", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación encontrado", @OA\JsonContent(ref="#/components/schemas/Activity")),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Donación no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->activityService->getActivityById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        return new ActivityResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/activity",
 *     summary="Crear Activity",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ActivityRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Activity")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="La validación falló.")))
 * )
 */
    public function store(StoreActivityRequest $request)
    {
        $rol = $this->activityService->createActivity($request->validated());
        return new ActivityResource($rol);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Actualizar un Activity",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
  *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ActivityRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Activity")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Datos inválidos"))),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateActivityRequest $request, $id)
    {

        $validatedData = $request->validated();

        if ($id == 1) {
            return response()->json([
                'message' => 'Esta Donación No puede ser Editado',
            ], 422);
        }
        $rol = $this->activityService->getActivityById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->activityService->updateActivity($rol, $validatedData);
        return new ActivityResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Eliminar un Activity por ID",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Donación eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $proyect = $this->activityService->getActivityById($id);

        if (! $proyect) {
            return response()->json([
                'error' => 'Donación No Encontrado.',
            ], 404);
        }
        $proyect = $this->activityService->destroyById($id);

        return response()->json([
            'message' => 'Activity eliminado exitosamente',
        ], 200);
    }

}
