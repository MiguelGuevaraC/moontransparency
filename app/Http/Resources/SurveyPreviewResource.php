<?php

namespace App\Http\Resources;

/**
 * @OA\Schema(
 *     schema="SurveyPreview",
 *     title="Vista previa de encuesta",
 *     description="Encuesta completa para representación visual sin captura de respuestas.",
 *
 *     @OA\Property(property="preview_mode", type="boolean", example=true),
 *     @OA\Property(property="read_only", type="boolean", example=true),
 *     @OA\Property(property="accepts_responses", type="boolean", example=false),
 *     @OA\Property(property="iframe_url", type="string", format="uri", example="https://api.example.com/encuestas/1/vista-previa?expires=...&signature=..."),
 *     @OA\Property(property="viewer_url", type="string", format="uri"),
 *     @OA\Property(property="expires_at", type="string", format="date-time"),
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_name", type="string", example="KPT línea base"),
 *     @OA\Property(property="status", type="string", example="INACTIVA"),
 *     @OA\Property(property="survey_questions", type="array", @OA\Items(ref="#/components/schemas/SurveyQuestion"))
 * )
 */
class SurveyPreviewResource extends SurveyResource
{
    public function __construct(
        $resource,
        private ?string $iframeUrl = null,
        private ?string $expiresAt = null
    ) {
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return array_merge([
            'preview_mode' => true,
            'read_only' => true,
            'accepts_responses' => false,
            'iframe_url' => $this->iframeUrl,
            'viewer_url' => $this->iframeUrl,
            'expires_at' => $this->expiresAt,
        ], parent::toArray($request));
    }
}
