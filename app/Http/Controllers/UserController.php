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

    public function index(IndexUserRequest $request)
    {
        return $this->getFilteredResults(
            User::query()->with('rol'),
            $request,
            User::filters,
            User::sorts,
            UserResource::class
        );
    }

    public function show(int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        return (new UserResource($user))->response()->setStatusCode(201);
    }

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

    public function activate(int $id)
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return new UserResource($this->userService->activate($user));
    }

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
