<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Maneja el proceso de inicio de sesión.
     *
     * @param  string  $email
     */
    public function login(string $username, string $password): array
    {
        // Busca al usuario por correo
        $user = User::with('rol.permissions')->where('username', $username)->first();

        // Si el usuario no existe, retornamos un error genérico sin dar pistas sobre la existencia
        if (! $user || ! $user->isActive() || ($user->rol && $user->rol->status !== 'Activo')) {
            return [
                'status' => false,
                'message' => 'Credenciales inválidas', // Mensaje más general
                'user' => null,
                'token' => null,
            ];
        }

        // Verifica si la contraseña es correcta
        if (! Hash::check($password, $user->password)) {
            return [
                'status' => false,
                'message' => 'Credenciales inválidas', // Mensaje más general
                'user' => null,
                'token' => null,
            ];
        }

        // Autentica al usuario
        Auth::login($user);

        // Crea un token de autenticación
        $token = $user->createToken('auth_token', ['expires' => now()->addHour()])->plainTextToken;

        // Oculta el campo de contraseña antes de retornar los datos
        $user->makeHidden('password');

        // Retorna la respuesta con el token y usuario
        return [
            'status' => true,
            'message' => 'Logueado Exitosamente',
            'token' => $token,
            'user' => $user,
        ];
    }

    public function authenticate(): array
    {
        $user = auth()->user()?->load('rol.permissions');
        // Llama al método login para realizar la autenticación
        return [
            'status' => $user !== null,
            'user' => $user,
            'person' => $user?->person,
            'message' => $user ? 'Autenticado' : 'No autenticado',
        ];
    }

    public function logout(): JsonResponse
    {
        try {
            // Verifica si el usuario está autenticado
            if (Auth::check()) {
                $accessToken = auth()->user()->currentAccessToken();

                // Verifica si existe un token de acceso
                if ($accessToken) {
                    // Establecer una nueva fecha de expiración (por ejemplo, 30 días a partir de ahora)
                    $accessToken->expires_at = Carbon::now();
                    $accessToken->save();
                }
            } else {
                return response()->json([
                    'message' => 'El usuario no está Autenticado.',
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }
        } catch (QueryException $e) {
            // Captura la excepción de la base de datos (por ejemplo, si hay un problema al eliminar el token)
            return response()->json([
                'message' => 'Ocurrio un error mientras cerraba sesión',
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Se cerró sesión Exitosamente',
        ]);
    }
}
