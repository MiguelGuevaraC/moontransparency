<?php
namespace App\Http\Controllers;

use App\Http\Requests\ProyectRequest\IndexProyectRequest;
use App\Http\Requests\ProyectRequest\StoreProyectRequest;
use App\Http\Requests\ProyectRequest\UpdateProyectRequest;
use App\Http\Resources\ProyectResource;
use App\Http\Resources\Web\ProyectWebResource;
use App\Models\Proyect;
use App\Services\ProyectService;
use Illuminate\Http\Request;

class ProyectController extends Controller
{
    protected $proyectService;

    public function __construct(ProyectService $proyectService)
    {
        $this->proyectService = $proyectService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/proyect",
 *     summary="Obtener información de proyectos con filtros y ordenamiento",
 *     tags={"Proyect"},
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
 *     @OA\Response(response=200, description="Lista de proyectos", @OA\JsonContent(ref="#/components/schemas/Proyect")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexProyectRequest $request)
    {

        return $this->getFilteredResults(
            Proyect::class,
            $request,
            Proyect::filters,
            Proyect::sorts,
            ProyectResource::class
        );
    }


    /**
 * @OA\Get(
 *     path="/moontransparency/public/api/proyects-web",
 *     summary="Obtener información de proyectos con filtros y ordenamiento",
 *     tags={"Proyect"},
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
 *     @OA\Response(response=200, description="Lista de proyectos", @OA\JsonContent(ref="#/components/schemas/Proyect")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

 public function list_web(IndexProyectRequest $request)
 {

     return $this->getFilteredResults(
         Proyect::class,
         $request,
         Proyect::filters,
         Proyect::sorts,
         ProyectWebResource::class
     );
 }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/proyect/{id}",
 *     summary="Obtener detalles de un proyecto por ID",
 *     tags={"Proyect"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del proyecto", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Proyecto encontrado", @OA\JsonContent(ref="#/components/schemas/Proyect")),
 *     @OA\Response(response=404, description="Proyecto no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Proyecto no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->proyectService->getProyectById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Proyecto No Encontrado',
            ], 404);
        }

        return new ProyectResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/proyect",
 *     summary="Crear proyecto",
 *     tags={"Proyect"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ProyectRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Proyecto creado exitosamente", @OA\JsonContent(ref="#/components/schemas/Proyect")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreProyectRequest $request)
    {
        $rol = $this->proyectService->createProyect($request->validated());
        return new ProyectResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/proyect/{id}",
 *     summary="Actualizar un proyecto",
 *     tags={"Proyect"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ProyectRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Proyecto actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Proyect")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Proyecto no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Proyecto no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */
    public function update(UpdateProyectRequest $request, $id)
    {

        $validatedData = $request->validated();

        $rol = $this->proyectService->getProyectById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Proyecto No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->proyectService->updateProyect($rol, $validatedData);
        return new ProyectResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/proyect/{id}",
 *     summary="Eliminar un proyecto por ID",
 *     tags={"Proyect"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Proyecto eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Proyecto eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Proyecto no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $proyect = $this->proyectService->getProyectById($id);

        if (! $proyect) {
            return response()->json([
                'error' => 'Proyecto No Encontrado.',
            ], 404);
        }
        $proyect = $this->proyectService->destroyById($id);

        return response()->json([
            'message' => 'Proyect eliminado exitosamente',
        ], 200);
    }

}
