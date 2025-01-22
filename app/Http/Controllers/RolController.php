<?php

namespace App\Http\Controllers;

use App\Http\Requests\RolRequest\IndexRolRequest;
use App\Http\Requests\RolRequest\StoreRolRequest;
use App\Http\Requests\RolRequest\UpdateAccessRequest;
use App\Http\Requests\RolRequest\UpdateRolRequest;
use App\Http\Resources\RolResource;
use App\Models\Rol;
use App\Services\RolService;

class RolController extends Controller
{

    protected $rolService;

    public function __construct(RolService $rolService)
    {
        $this->rolService = $rolService;
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/rol",
     *     summary="Obtener información con filtros y ordenamiento",
     *     tags={"Rol"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de la categoria", required=false, @OA\Schema(type="string")),

     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200,description="Lista de Empresas",@OA\JsonContent(ref="#/components/schemas/Rol")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
     * )
     */

    public function index(IndexRolRequest $request)
    {

        return $this->getFilteredResults(
            Rol::class,
            $request,
            Rol::filters,
            Rol::sorts,
            RolResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/rol/{id}",
     *     summary="Obtener detalles de un rol por ID",
     *     tags={"Rol"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID de Rol", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Rol encontrada", @OA\JsonContent(ref="#/components/schemas/Rol")),
     *     @OA\Response(response=404, description="Rol No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Rol No Encontrado")))
     * )
     */

    public function show($id)
    {

        $rol = $this->rolService->getRolById($id);
      
        if (!$rol) {
            return response()->json([
                'error' => 'Rol No Encontrado',
            ], 404);
        }

        return new RolResource($rol);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/rol",
     *     summary="Crear rol",
     *     tags={"Rol"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Trabajador"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rol creado exitosamente", @OA\JsonContent(ref="#/components/schemas/Rol")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="La persona ya tiene un rol asociado.")))
     * )
     */
    public function store(StoreRolRequest $request)
    {
        $rol = $this->rolService->createRol($request->validated());
        return new RolResource($rol);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/rol/{id}",
     *     summary="Actualizar rol",
     *     tags={"Rol"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Trabajador"),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Rol actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Rol")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Datos inválidos")))
     * )
     */

    public function update(UpdateRolRequest $request, $id)
    {

        $validatedData = $request->validated();

        if($id==1){
            return response()->json([
                'message' => 'Este Rol No puede ser Editado',
            ], 422);
        }
        $rol = $this->rolService->getRolById($id);
        if (!$rol) {
            return response()->json([
                'error' => 'Rol No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->rolService->updateRol($rol, $validatedData);
        return new RolResource($updatedCompany);
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/rol/{id}",
     *     summary="Eliminar persona por ID",
     *     tags={"Rol"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID de Rol", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Rol eliminado exitosamente", @OA\JsonContent(type="object", @OA\Property(property="message", type="string", example="persona eliminada exitosamente"))),
     *     @OA\Response(response=404, description="Rol No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Rol No Encontrado")))
     * )
     */

    public function destroy($id)
    {
        if($id==1){
            return response()->json([
                'message' => 'Este Rol No puede ser Eliminado',
            ], 422);
        }
        $deleted = $this->rolService->destroyById($id);

        if (!$deleted) {
            return response()->json([
                'error' => 'Rol No Encontrado.',
            ], 404);
        }

        return response()->json([
            'message' => 'Rol eliminado exitosamente',
        ], 200);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/rol/{typeUserId}/setaccess",
     *     tags={"Rol"},
     *     summary="Set access to type of User",
     *     description="Set access permissions for a specific type of user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="typeUserId",
     *         in="path",
     *         required=true,
     *         description="ID of the type of user",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Options menu in array format",
     *         @OA\JsonContent(
     *             required={"access"},
     *             @OA\Property(
     *                 property="access",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3},
     *                 description="List of permission IDs to assign"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Permissions assigned successfully."),
     *             @OA\Property(property="role", type="object", description="Updated role object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error."),
     *             @OA\Property(property="errors", type="object", description="Object containing validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Role not found.")
     *         )
     *     )
     * )
     */
    public function setAccess(UpdateAccessRequest $request, $id)
    {

        $validatedData = $request->validated();
     

        $rol = $this->rolService->getRolById($id);
       
        if (!$rol) {
            return response()->json([
                'error' => 'Rol No Encontrado',
            ], 404);
        }
        $rol = $this->rolService->setAccess($request->access ?? [], $rol);

        return new RolResource($rol);
    }

}
