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

    public function show(int $id)
    {
        $rol = $this->rolService->getRolById($id);

        return $rol
            ? new RolResource($rol)
            : response()->json(['message' => 'Rol no encontrado.'], 404);
    }

    public function store(StoreRolRequest $request)
    {
        return (new RolResource($this->rolService->createRol($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateRolRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->updateRol($rol, $request->validated()));
    }

    public function activate(int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->activate($rol));
    }

    public function deactivate(int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->deactivate($rol));
    }

    public function destroy(int $id): JsonResponse
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        $this->rolService->destroy($rol);

        return response()->json(['message' => 'Rol eliminado lógicamente.']);
    }

    public function setAccess(UpdateAccessRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->setAccess($request->validated('access'), $rol));
    }

    public function assignPermissions(UpdateAccessRequest $request, int $id)
    {
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json(['message' => 'Rol no encontrado.'], 404);
        }

        return new RolResource($this->rolService->assignPermissions($request->validated('access'), $rol));
    }

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
