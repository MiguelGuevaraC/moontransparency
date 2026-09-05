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

    public function index(IndexPermissionRequest $request)
    {
        return $this->getFilteredResults(
            Permission::query(),
            $request,
            Permission::filters,
            Permission::sorts,
            PermissionResource::class
        );
    }

    public function show(int $id)
    {
        $permission = $this->permissionService->getPermissionById($id);

        return $permission
            ? new PermissionResource($permission)
            : response()->json(['message' => 'Permiso no encontrado.'], 404);
    }
}
