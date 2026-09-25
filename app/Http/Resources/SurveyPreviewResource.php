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
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_name", type="string", example="KPT línea base"),
 *     @OA\Property(property="status", type="string", example="INACTIVA"),
 *     @OA\Property(property="survey_questions", type="array", @OA\Items(ref="#/components/schemas/SurveyQuestion"))
 * )
 */
class SurveyPreviewResource extends SurveyResource
{
    public function toArray($request): array
    {
        return array_merge([
            'preview_mode' => true,
            'read_only' => true,
            'accepts_responses' => false,
        ], parent::toArray($request));
    }
}
