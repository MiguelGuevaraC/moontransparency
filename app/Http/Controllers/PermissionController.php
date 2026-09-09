<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest\IndexPermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\PermissionService;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService)
    {
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/permission", operationId="listPermissions", summary="Listar permisos disponibles", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string")), @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Permisos", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Permission")))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.view"), @OA\Response(response=422, description="Filtros inválidos")
     * )
     */
    public function index(IndexPermissionRequest $request)
    {
        if (! $request->query->has('all')) {
            $request->query->set('all', 'true');
        }

        $query = $this->applyFilters(
            Permission::query(),
            $request,
            Permission::filters
        );
        $query = $this->applySorting($query, $request, Permission::sorts);

        if ($request->query('all') === 'true') {
            return PermissionResource::collection($query->get());
        }

        return $this->getFilteredResults(
            $query,
            $request,
            [],
            [],
            PermissionResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/permission/{id}", operationId="showPermission", summary="Consultar permiso", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Permiso", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Permission"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.view"), @OA\Response(response=404, description="Permiso no encontrado")
     * )
     */
    public function show(int $id)
    {
        $permission = $this->permissionService->getPermissionById($id);

        return $permission
            ? new PermissionResource($permission)
            : response()->json(['message' => 'Permiso no encontrado.'], 404);
    }
}
