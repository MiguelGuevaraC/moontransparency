<?php
namespace App\Http\Controllers;

use App\Http\Requests\ActivityRequest\IndexActivityRequest;
use App\Http\Requests\ActivityRequest\StoreActivityRequest;
use App\Http\Requests\ActivityRequest\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\Web\ActivityWebResource;
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
 *     @OA\Parameter(name="proyect_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="integer")),
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
     *     path="/moontransparency/public/api/activity-web",
     *     summary="Obtener información de Activitys con filtros y ordenamiento",
     *     tags={"Activity"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de Activity", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date", in="query", description="Filtrar por fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", description="Filtrar por fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="proyect_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="objective", in="query", description="Filtrar por objetivo de la actividad", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="total_amount", in="query", description="Filtrar por monto total de la actividad", required=false, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="collected_amount", in="query", description="Filtrar por monto recolectado", required=false, @OA\Schema(type="number", format="float")),
     *     @OA\Parameter(name="status", in="query", description="Filtrar por estado de la actividad", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Lista de Activitys", @OA\JsonContent(ref="#/components/schemas/Activity")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
     * )
     */

    public function list_web(IndexActivityRequest $request)
    {

        return $this->getFilteredResults(
            Activity::class,
            $request,
            Activity::filters,
            Activity::sorts,
            ActivityWebResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Obtener detalles de un Actividad por ID",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Activity", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Actividad encontrada", @OA\JsonContent(ref="#/components/schemas/Activity")),
 *     @OA\Response(response=404, description="Actividad no encontrada", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Actividad no encontrada")))
 * )
 */

    public function show($id)
    {

        $activity = $this->activityService->getActivityById($id);

        if (! $activity) {
            return response()->json([
                'error' => 'Actividad No Encontrada',
            ], 404);
        }

        return new ActivityResource($activity);
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
 *     @OA\Response(
 *         response=200,
 *         description="Actividad creada exitosamente",
 *         @OA\JsonContent(ref="#/components/schemas/Activity")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error de validación")
 *         )
 *     )
 * )
 */

    public function store(StoreActivityRequest $request)
    {
        $data                     = $request->validated();
        $data['collected_amount'] = "0";
        $activity                 = $this->activityService->createActivity($data);
        return new ActivityResource($activity);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Actualizar un Activity",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ActivityRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Actividad actualizada exitosamente",
 *         @OA\JsonContent(ref="#/components/schemas/Activity")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error de validación")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Actividad no encontrada",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Actividad no encontrada")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error interno del servidor")
 *         )
 *     )
 * )
 */

    public function update(UpdateActivityRequest $request, $id)
    {

        $validatedData = $request->validated();

        $activity = $this->activityService->getActivityById($id);
        if (! $activity) {
            return response()->json([
                'error' => 'Actividad No Encontrada',
            ], 404);
        }

        $updatedCompany = $this->activityService->updateActivity($activity, $validatedData);
        return new ActivityResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/activity/{id}",
 *     summary="Eliminar un Actividadpor ID",
 *     tags={"Activity"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Actividad eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Actividad eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Actividad no encontrada"))),

 * )
 */

    public function destroy($id)
    {

        $proyect = $this->activityService->getActivityById($id);

        if (! $proyect) {
            return response()->json([
                'error' => 'Actividad No Encontrada.',
            ], 404);
        }
        $proyect = $this->activityService->destroyById($id);

        return response()->json([
            'message' => 'Actividadeliminado exitosamente',
        ], 200);
    }

}
