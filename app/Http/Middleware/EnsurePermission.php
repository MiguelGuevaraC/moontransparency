<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (!$user->isActive()) {
            return response()->json(['message' => 'La cuenta de usuario está inactiva.'], 403);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'message' => 'No tiene permiso para realizar esta acción.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
