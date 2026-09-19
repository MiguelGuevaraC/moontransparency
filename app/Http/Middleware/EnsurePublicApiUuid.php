<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePublicApiUuid
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('app.uuid', '');
        $provided = (string) $request->header('UUID', '');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'UUID de acceso público inválido.',
            ], 401);
        }

        return $next($request);
    }
}
