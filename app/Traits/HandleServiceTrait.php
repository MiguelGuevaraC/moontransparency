<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait HandleServiceTrait
{
    protected function handleService(
        callable $action,
        array $payload = [],
        ?string $successMessage = null,
        int $status = 200,
        string $logAction = 'generic_service_error',
        ?string $resource = null
    ) {
        try {
            $result = $action();

            // 🔹 Si ya es JsonResponse, devolver tal cual
            if ($result instanceof JsonResponse) {
                return $result;
            }

            //  Error controlado en el servicio (devuelve ['error' => ...])
            if (is_array($result) && isset($result['error'])) {
                $identifier = Carbon::now()->format('Ymd-His') . '-' . uniqid();

                Log::error("{$logAction} - Error controlado", [
                    'identifier' => $identifier,
                    'error'      => $result['error'],
                    'data'       => $result['data'] ?? $payload,
                ]);

                return response()->json([
                    'success'    => false,
                    'message'    => "Ocurrió un inconveniente al procesar la operación. 
                        Por favor, intente nuevamente más tarde. 
                        Si el problema persiste, comparta el identificador {$identifier} con el área de soporte.",
                ], 500);
            }

            // No hubo resultado (null, false o vacío)
            if (!$result) {
                $identifier = Carbon::now()->format('Ymd-His') . '-' . uniqid();

                Log::warning("{$logAction} - Operación sin resultado", [
                    'identifier' => $identifier,
                    'data'       => $payload,
                ]);

                return response()->json([
                    'success'    => false,
                    'message'    => "Error: la operación no produjo resultado. 
                        Identificador {$identifier}.",
                ], 500);
            }

            // Si hay recurso JsonResource
            if ($resource && class_exists($resource) && is_subclass_of($resource, JsonResource::class)) {
                if ($result instanceof \Illuminate\Support\Collection || is_array($result)) {
                    $result = $resource::collection($result);
                } else {
                    $result = new $resource($result);
                }
            }

            return $successMessage
                ? response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'data'    => $result,
                ], $status)
                : $result;

        } catch (\Throwable $e) {
            // Error inesperado (excepción no controlada)
            $identifier = Carbon::now()->format('Ymd-His') . '-' . uniqid();

            Log::critical("{$logAction} - Excepción no controlada", [
                'identifier' => $identifier,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => collect($e->getTrace())->take(5)->toArray(),
                'data'       => $payload,
            ]);

            return response()->json([
                'success'    => false,
                'message'    => "Error inesperado en el servidor. 
                    Identificador {$identifier}.",
            ], 500);
        }
    }
}
