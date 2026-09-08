<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolRequest\IndexRolRequest;
use App\Http\Requests\RolRequest\StoreRolRequest;
use App\Http\Requests\RolRequest\UpdateAccessRequest;
use App\Http\Requests\RolRequest\UpdateRolRequest;
use App\Http\Resources\RolResource;
use App\Models\Permission;
use App\Models\Rol;
use App\Services\RolService;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    public function __construct(private RolService $rolService)
    {
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/rol", operationId="listRoles", summary="Listar roles", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="name", in="query", required=false, @OA\Schema(type="string")), @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"Activo", "Inactivo"})),
     *     @OA\Response(response=200, description="Roles", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Rol")))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.view"), @OA\Response(response=422, description="Filtros inválidos")
     * )
     */
    public function index(IndexRolRequest $request)
    {
        return $this->getFilteredResults(
            Rol::query()->with('permissions'),
            $request,
            Rol::filters,
            Rol::sorts,
            RolResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/rol/{id}", operationId="showRole", summary="Consultar rol y permisos", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Rol", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.view"), @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function show(int $id)
    {
        $rol = $this->rolService->getRolById($id);

        return $rol
            ? new RolResource($rol)
            : response()->json(['message' => 'Rol no encontrado.'], 404);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/rol", operationId="createRole", summary="Crear rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleInput")), @OA\Response(response=201, description="Rol creado", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.create"), @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function store(StoreRolRequest $request)
    {
        return (new RolResource($this->rolService->createRol($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/rol/{id}", operationId="updateRole", summary="Editar rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleInput")), @OA\Response(response=200, description="Rol actualizado", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.update"), @OA\Response(response=404, description="Rol no encontrado"), @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function update(UpdateRolRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->updateRol($rol, $request->validated()));
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/rol/{id}/activate", operationId="activateRole", summary="Activar rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Rol activo", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.update"), @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function activate(int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->activate($rol));
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/rol/{id}/deactivate", operationId="deactivateRole", summary="Desactivar rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Rol inactivo", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.deactivate"), @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function deactivate(int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->deactivate($rol));
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/rol/{id}", operationId="deleteRole", summary="Eliminar lógicamente un rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Rol eliminado"),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.delete"), @OA\Response(response=404, description="Rol no encontrado")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        $this->rolService->destroy($rol);

        return response()->json(['message' => 'Rol eliminado lógicamente.']);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/rol/{id}/setaccess", operationId="replaceRolePermissions", summary="Reemplazar todos los permisos de un rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleAccessInput")), @OA\Response(response=200, description="Permisos reemplazados", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Falta permiso para asignar o revocar"), @OA\Response(response=404, description="Rol no encontrado"), @OA\Response(response=422, description="Permisos inválidos")
     * )
     */
    public function setAccess(UpdateAccessRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->setAccess($request->validated('access'), $rol));
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/rol/{id}/permissions", operationId="assignRolePermissions", summary="Asignar permisos a un rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/RoleAccessInput")), @OA\Response(response=200, description="Permisos asignados", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.assign_permissions"), @OA\Response(response=404, description="Rol no encontrado"), @OA\Response(response=422, description="Permisos inválidos")
     * )
     */
    public function assignPermissions(UpdateAccessRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->assignPermissions($request->validated('access'), $rol));
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/rol/{id}/permissions/{permissionId}", operationId="revokeRolePermission", summary="Revocar un permiso de un rol", tags={"Roles y permisos"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Parameter(name="permissionId", in="path", required=true, @OA\Schema(type="integer", minimum=1)), @OA\Response(response=200, description="Permiso revocado", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/Rol"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso roles.revoke_permissions"), @OA\Response(response=404, description="Rol o permiso no encontrado")
     * )
     */
    public function revokePermission(int $id, int $permissionId)
    {
        $rol = $this->rolService->getRolById($id);
        $permission = Permission::find($permissionId);
        if (!$rol || !$permission) {
            return response()->json(['message' => 'Rol o permiso no encontrado.'], 404);
        }

        return new RolResource($this->rolService->revokePermission($rol, $permission));
    }
}
