<?php

namespace App\Http\Controllers\Terceros;

use App\Http\Controllers\Controller;
use App\Http\Requests\TercerosRequest\SearchDniRequest;
use App\Http\Requests\TercerosRequest\SearchRucRequest;
use App\Http\Resources\SearchDniResource;
use App\Http\Resources\SearchRucResource;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    /**
     * @OA\Tag(name="Search")
     */

    public function search_dni(SearchDniRequest $request)
    {
        $dni = $request->search;
        $url = config('services.search_identity.dni.url');
        $token = config('services.search_identity.dni.token');

        try {
            $data = $this->callExternalApi($url, ['dni' => $dni, 'fe' => 'N', 'token' => $token]);
            return response()->json([
                'success' => true,
                'message' => 'Información encontrada exitosamente',
                'data' => new SearchDniResource($data)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar DNI',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search_ruc(SearchRucRequest $request)
    {
        $ruc = $request->search;
        $url = config('services.search_identity.ruc.url');
        $token = config('services.search_identity.ruc.token');

        try {
            $data = $this->callExternalApi($url, ['ruc' => $ruc, 'fe' => 'N', 'token' => $token]);
            return response()->json([
                'success' => true,
                'message' => 'Información encontrada exitosamente',
                'data' => new SearchRucResource($data)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar RUC',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /** Llamada a API externa */
    private function callExternalApi(string $url, array $params)
    {
        $response = Http::get($url, $params);
        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Error al comunicarse con el servicio externo');
    }
}
