<?php
namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest\IndexPermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\PermissionService;

class PermissionController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/permission",
 *     summary="Obtener información con filtros y ordenamiento",
 *     tags={"Permission"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="type", in="query", description="Filtrar por tipo", required=false, @OA\Schema(type="string")),

 *     @OA\Parameter(name="status", in="query", description="Filtrar por estado", required=false, @OA\Schema(type="string")),

 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de Entornos", @OA\JsonContent(ref="#/components/schemas/Permission")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexPermissionRequest $request)
    {

        return $this->getFilteredResults(
            Permission::class,
            $request,
            Permission::filters,
            Permission::sorts,
            PermissionResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/permission/{id}",
     *     summary="Obtener detalles de un permission por ID",
     *     tags={"Permission"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID de Permission", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Permission encontrada", @OA\JsonContent(ref="#/components/schemas/Permission")),
     *     @OA\Response(response=404, description="Permission No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Permission No Encontrado")))
     * )
     */

    public function show($id)
    {

        $permission = $this->permissionService->getPermissionById($id);

        if (! $permission) {
            return response()->json([
                'error' => 'Permiso No Encontrado',
            ], 404);
        }

        return new PermissionResource($permission);
    }

}
