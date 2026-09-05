<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (!$user->isActive()) {
            return response()->json(['message' => 'La cuenta de usuario está inactiva.'], 403);
        }

        if (!$user->isAdministrator()) {
            return response()->json(['message' => 'Solo un administrador puede gestionar usuarios.'], 403);
        }

        return $next($request);
    }
}
