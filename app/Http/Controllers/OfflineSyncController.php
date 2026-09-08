<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfflineSyncRequest;
use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class OfflineSyncController extends Controller
{
    public function __construct(private OfflineSyncService $offlineSyncService)
    {
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/offline-sync",
     *     operationId="synchronizeOfflineSurveyBatch",
     *     summary="Sincronizar un lote capturado sin conexión",
     *     description="Contrato 1.0. batch_id hace idempotente el lote, client_participation_id y client_measurement_id evitan duplicados. Un reintento debe conservar exactamente el payload y los archivos. Cada elemento se procesa en su propia transacción; los conflictos no revierten los elementos correctos. Esta ruta sigue el mismo acceso público del formulario de campo.",
     *     tags={"Surveyed"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(mediaType="application/json", @OA\Schema(ref="#/components/schemas/OfflineSyncRequest")),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *                 required={"payload"},
     *
     *                 @OA\Property(property="payload", type="string", description="JSON serializado conforme a OfflineSyncRequest."),
     *                 @OA\Property(property="attachments", type="object", description="Archivos indexados por attachment_key.", additionalProperties=@OA\AdditionalProperties(type="string", format="binary"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Todos los elementos fueron sincronizados o el lote fue reproducido", @OA\JsonContent(ref="#/components/schemas/OfflineSyncResponse")),
     *     @OA\Response(response=207, description="Resultado por elemento: éxito parcial o todos fallidos", @OA\JsonContent(ref="#/components/schemas/OfflineSyncResponse")),
     *     @OA\Response(response=409, description="batch_id reutilizado con contenido distinto o lote todavía en proceso", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="El sobre del lote o sus archivos no cumplen el contrato", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function store(OfflineSyncRequest $request): JsonResponse
    {
        $payload = Arr::except($request->validated(), [
            'payload',
            'payload_invalid',
            'attachments',
        ]);
        $result = $this->offlineSyncService->synchronize(
            $payload,
            $request->file('attachments', []),
            $request->user()
        );

        return response()->json($result['payload'], $result['http_status']);
    }
}
