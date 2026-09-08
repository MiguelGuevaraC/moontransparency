<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\IndexUserRequest;
use App\Http\Requests\UserRequest\StoreUserRequest;
use App\Http\Requests\UserRequest\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/user", operationId="listUsers", summary="Listar usuarios", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="names", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="username", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="number_document", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string", enum={"Activo", "Inactivo"})),
     *     @OA\Parameter(name="rol_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Usuarios", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sin el permiso users.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Filtros inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function index(IndexUserRequest $request)
    {
        return $this->getFilteredResults(
            User::query()->with('rol.permissions'),
            $request,
            User::filters,
            User::sorts,
            UserResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/user/{id}", operationId="showUser", summary="Consultar usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Usuario", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sin el permiso users.view", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Usuario no encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource($user);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/user", operationId="createUser", summary="Crear usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserInput")),
     *     @OA\Response(response=201, description="Usuario creado", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sin el permiso users.create", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Datos inválidos o duplicados", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/user/{id}", operationId="updateUser", summary="Editar usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserUpdateInput")),
     *     @OA\Response(response=200, description="Usuario actualizado", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Sin el permiso users.update", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Usuario no encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Datos inválidos o duplicados", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource(
            $this->userService->updateUser(
                $user,
                $request->validated(),
                (int) $request->user()->id
            )
        );
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/user/{id}/activate", operationId="activateUser", summary="Activar usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Usuario activo", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso users.update"), @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function activate(int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource($this->userService->activate($user));
    }

    /**
     * @OA\Patch(
     *     path="/moontransparency/public/api/user/{id}/deactivate", operationId="deactivateUser", summary="Desactivar usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Usuario inactivo", @OA\JsonContent(type="object", @OA\Property(property="data", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso users.update"), @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function deactivate(Request $request, int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource(
            $this->userService->deactivate($user, (int) $request->user()->id)
        );
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/user/{id}", operationId="deleteUser", summary="Eliminar lógicamente un usuario", tags={"Usuarios"}, security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Response(response=200, description="Usuario eliminado lógicamente", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="No autenticado"), @OA\Response(response=403, description="Sin el permiso users.delete"), @OA\Response(response=404, description="Usuario no encontrado")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $this->userService->destroy($user, (int) $request->user()->id);

        return response()->json(['message' => 'Usuario eliminado lógicamente.']);
    }
}
