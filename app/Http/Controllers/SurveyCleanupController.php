<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyCleanupRequest;
use App\Services\SurveyCleanupService;

class SurveyCleanupController extends Controller
{
    public function __construct(private SurveyCleanupService $surveyCleanupService)
    {
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/survey/{id}/clean-participations",
     *     operationId="cleanSurveyParticipations",
     *     summary="Respaldar y limpiar las participaciones de una encuesta",
     *     description="Operación destructiva separada de Inactivar. Genera un respaldo JSON comprimido y una auditoría antes de eliminar participaciones, mediciones, respuestas y referencias de sincronización. Los archivos adjuntos se conservan. Por defecto solo el Administrador posee surveys.clean_participations.",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SurveyCleanupInput")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Respaldo y limpieza completados",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/SurveyCleanupResult")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="No es administrador o no tiene surveys.clean_participations", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Encuesta no encontrada", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=409, description="La encuesta no tiene participaciones para limpiar", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Motivo o confirmación inválidos", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
    public function store(SurveyCleanupRequest $request, int $id)
    {
        $actor = $request->user();
        if (! $actor || ! $actor->isAdministrator()) {
            return response()->json([
                'message' => 'Solo un administrador puede limpiar los datos de una encuesta.',
            ], 403);
        }

        $audit = $this->surveyCleanupService->clean(
            $id,
            $actor,
            $request->validated('reason'),
            $request->validated('confirmation')
        );
        if (! $audit) {
            return response()->json(['message' => 'Encuesta no encontrada.'], 404);
        }

        return response()->json([
            'message' => 'Las participaciones fueron respaldadas y limpiadas.',
            'data' => [
                'audit_id' => $audit->id,
                'survey_id' => $audit->survey_id,
                'deleted_counts' => $audit->deleted_counts,
                'backup_reference' => $audit->backup_path,
                'backup_sha256' => $audit->backup_sha256,
                'performed_by' => $audit->performed_by,
                'performed_at' => $audit->created_at?->toIso8601String(),
            ],
        ]);
    }
}
