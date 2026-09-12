<?php

namespace App\Http\Controllers;

use App\Http\Resources\MenuResource;
use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/menu",
     *     operationId="listMenus",
     *     summary="Listar opciones administrables del menú",
     *     tags={"Roles y permisos"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(response=200, description="Menús activos ordenados", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Menu")))),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin el permiso roles.view")
     * )
     */
    public function index()
    {
        return MenuResource::collection(
            Menu::query()
                ->where('status', Menu::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->get()
        );
    }
}
