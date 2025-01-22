<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthenticationRequest\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationController extends Controller
{

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/logout",
     *     tags={"Authentication"},
     *     summary="Logout",
     *     description="Log out user.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful logout",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="An error occurred while trying to log out. Please try again later.")
     *         )
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        return $this->authService->logout();
    }
    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     description="Authenticate user and generate access token",
     * security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *         @OA\JsonContent(
     *             required={"username", "password", "branchOffice_id"},
     *             @OA\Property(property="username", type="string", example="admin"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),

     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="token", type="string", description="token del usuario"),
     *             @OA\Property(
     *             property="user",
     *             type="object",
     *             description="User",
     *             ref="#/components/schemas/User"
     *          ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Message Response"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="User not found or password incorrect",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     ),
     *       @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */

    public function login(LoginRequest $request): JsonResponse
    {

        try {

            $data = $request->only(['username', 'password']);
            // Llama al servicio de autenticación
            $authData = $this->authService->login($request->username, $request->password);

            // Verifica si el usuario es null
            if (!$authData['user']) {
                return response()->json([
                    'error' => $authData['message'],
                ], 422);
            }

            // Retorna la respuesta con el token y el recurso del usuario
            return response()->json([
                'token' => $authData['token'],
                'user' => new UserResource($authData['user']),
                'message' => $authData['message'],
            ]);
        } catch (\Exception $e) {
            // Captura cualquier excepción y retorna el mensaje de error
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/authenticate",
     *     summary="Get Profile user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     description="Get user",
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="Bearer token"
     *             ),
     *             @OA\Property(
     *             property="user",
     *             type="object",
     *             description="User",
     *             ref="#/components/schemas/User"
     *              ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 description="Message Response"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The given data was invalid.")
     *         )
     *     ),
     *        @OA\Response(
     *         response=401,
     *         description="Unauthenticated.",
     *         @OA\JsonContent(
     *             @OA\Property(property="msg", type="string", example="Unauthenticated.")
     *         )
     *     ),
     * )
     */

    public function authenticate(Request $request)
    {
        // Llama al servicio de autenticación
        $result = $this->authService->authenticate();

        // Si la autenticación falla, devuelve el mensaje de error
        if (!$result['status']) {
            return response()->json(['error' => $result['message']], 422);
        }
        $token = $request->bearerToken();

        // Si la autenticación es exitosa, devuelve el token, el usuario y la persona
        return response()->json([
            'token' => $token,
            'user' => new UserResource($result['user']),
            'message' => 'Autenticado',
        ]);
    }

}
