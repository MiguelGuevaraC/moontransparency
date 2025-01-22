<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\IndexUserRequest;
use App\Http\Requests\UserRequest\StoreUserRequest;
use App\Http\Requests\UserRequest\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;

class UserController extends Controller
{

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/user",
     *     summary="Obtener información con filtros y ordenamiento",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="username", in="query", description="Filtrar por username", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de usuario", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="person.name", in="query", description="Filtrar por nombre de la persona", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="person.business_name", in="query", description="Filtrar por nombre de la empresa", required=false, @OA\Schema(type="string")),


     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200,description="Lista de Empresas",@OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
     * )
     */

    public function index(IndexUserRequest $request)
    {

        return $this->getFilteredResults(
            User::class,
            $request,
            User::filters,
            User::sorts,
            UserResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/user/{id}",
     *     summary="Obtener detalles de un usuario por ID",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID del Usuario", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Usuario encontrado", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=404, description="Usuario No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Usuario No Encontrado")))
     * )
     */

    public function show($id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json([
                'error' => 'Usuario No Encontrado',
            ], 404);
        }

        return new UserResource($user);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/user",
     *     summary="Crear usuario",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"username", "password", "number_document"},
     *             @OA\Property(property="username", type="string", example="juan.perez@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="type_document", type="string", example="DNI"),
     *             @OA\Property(property="type_person", type="string", example="natural"),
     *             @OA\Property(property="names", type="string", example="Juan Pérez"),
     *             @OA\Property(property="father_surname", type="string", example="Pérez"),
     *             @OA\Property(property="mother_surname", type="string", example="Gómez"),
     *             @OA\Property(property="business_name", type="string", example="Pérez Enterprises"),
     *             @OA\Property(property="address", type="string", example="Calle Ficticia 123"),
     *             @OA\Property(property="phone", type="string", example="+51987654321"),
     *             @OA\Property(property="email", type="string", example="juan.perez@example.com"),
     *             @OA\Property(property="number_document", type="string", example="12345678"),
     *             @OA\Property(property="ocupation", type="string", example="Trabajador")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuario creado exitosamente", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="La persona ya tiene un usuario asociado.")))
     * )
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());
        return new UserResource($user);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/user/{id}",
     *     summary="Actualizar usuario",
     *     tags={"User"},
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
     *             required={"username", "password", "number_document"},
     *             @OA\Property(property="username", type="string", example="juan.perez@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="type_document", type="string", example="DNI"),
     *             @OA\Property(property="type_person", type="string", example="natural"),
     *             @OA\Property(property="names", type="string", example="Juan Pérez"),
     *             @OA\Property(property="father_surname", type="string", example="Pérez"),
     *             @OA\Property(property="mother_surname", type="string", example="Gómez"),
     *             @OA\Property(property="business_name", type="string", example="Pérez Enterprises"),
     *             @OA\Property(property="address", type="string", example="Calle Ficticia 123"),
     *             @OA\Property(property="phone", type="string", example="+51987654321"),
     *             @OA\Property(property="email", type="string", example="juan.perez@example.com"),
     *             @OA\Property(property="number_document", type="string", example="12345678"),
     *             @OA\Property(property="ocupation", type="string", example="Trabajador")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Usuario actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/User")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Datos inválidos")))
     * )
     */

    public function update(UpdateUserRequest $request, $id)
    {

        $validatedData = $request->validated();
        if($id==1){
            return response()->json([
                'message' => 'Este Usuario No puede ser Editado',
            ], 422);
        }
        $user = $this->userService->getUserById($id);
        if (!$user) {
            return response()->json([
                'error' => 'Usuario No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->userService->updateUser($user, $validatedData);
        return new UserResource($updatedCompany);
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/user/{id}",
     *     summary="Eliminar persona por ID",
     *     tags={"User"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID de Usuario", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Usuario eliminado exitosamente", @OA\JsonContent(type="object", @OA\Property(property="message", type="string", example="persona eliminada exitosamente"))),
     *     @OA\Response(response=404, description="Usuario No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Usuario No Encontrado")))
     * )
     */

    public function destroy($id)
    {
        if($id==1){
            return response()->json([
                'message' => 'Este Usuario No puede ser Eliminado',
            ], 422);
        }
        $deleted = $this->userService->destroyById($id);

        if (!$deleted) {
            return response()->json([
                'error' => 'Usuario No Encontrado o .',
            ], 404);
        }

        return response()->json([
            'message' => 'Persona eliminada exitosamente',
        ], 200);
    }

}
