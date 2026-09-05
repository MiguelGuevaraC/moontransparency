<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function getUserById(int $id): ?User
    {
        return User::with('rol')->find($id);
    }

    public function createUser(array $data): User
    {
        $data['status'] = $data['status'] ?? User::STATUS_ACTIVE;

        return DB::transaction(function () use ($data) {
            return User::create($data)->load('rol');
        });
    }

    public function updateUser(User $user, array $data, int $currentUserId): User
    {
        return DB::transaction(function () use ($user, $data, $currentUserId) {
            if (array_key_exists('password', $data) && blank($data['password'])) {
                unset($data['password']);
            }
            if (($data['status'] ?? null) === User::STATUS_INACTIVE) {
                $this->ensureDifferentUser($user, $currentUserId, 'desactivar');
            }

            $user->fill($data);
            $user->save();

            if (!$user->isActive()) {
                $user->tokens()->delete();
            }

            return $user->load('rol');
        });
    }

    public function activate(User $user): User
    {
        $user->update(['status' => User::STATUS_ACTIVE]);

        return $user->load('rol');
    }

    public function deactivate(User $user, int $currentUserId): User
    {
        $this->ensureDifferentUser($user, $currentUserId, 'desactivar');

        $user->update(['status' => User::STATUS_INACTIVE]);
        $user->tokens()->delete();

        return $user->load('rol');
    }

    public function destroy(User $user, int $currentUserId): void
    {
        $this->ensureDifferentUser($user, $currentUserId, 'eliminar');

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->delete();
        });
    }

    private function ensureDifferentUser(User $user, int $currentUserId, string $action): void
    {
        if ($user->id === $currentUserId) {
            throw ValidationException::withMessages([
                'user' => "Un administrador no puede $action su propia cuenta.",
            ]);
        }
    }
}
