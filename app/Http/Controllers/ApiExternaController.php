<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ApiExternaController extends Controller
{
    public function searchByDni(string $dni): JsonResponse
    {
        if (! preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['message' => 'El DNI debe contener 8 dígitos.'], 422);
        }

        return $this->search('dni', $dni);
    }

    public function searchByRuc(string $ruc): JsonResponse
    {
        if (! preg_match('/^\d{11}$/', $ruc)) {
            return response()->json(['message' => 'El RUC debe contener 11 dígitos.'], 422);
        }

        return $this->search('ruc', $ruc);
    }

    private function search(string $type, string $document): JsonResponse
    {
        $url = config("services.search_identity.$type.url");
        $token = config("services.search_identity.$type.token");

        if (! $url || ! $token) {
            return response()->json(['message' => 'El servicio externo no está configurado.'], 503);
        }

        try {
            $response = Http::acceptJson()->timeout(10)->get($url, [
                $type => $document,
                'fe' => 'N',
                'token' => $token,
            ]);
            $response->throw();

            return response()->json($response->json());
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'El servicio externo no está disponible.'], 502);
        }
    }
}
