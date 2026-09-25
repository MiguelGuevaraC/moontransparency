<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SurveyQuestionRequest",
 *     type="object",
 *     required={"survey_id", "question_text", "question_type"},
 *
 *     @OA\Property(property="survey_id", type="integer", example=101),
 *     @OA\Property(property="question_text", type="string", example="¿Qué fuentes de energía utiliza en su hogar?"),
 *     @OA\Property(property="instrument_key", type="string", nullable=true, example="monitoring.moon_only.remaining"),
 *     @OA\Property(property="calculator_key", type="string", nullable=true, example="baseline.initial_wood_kg"),
 *     @OA\Property(property="calculator_value_type", type="string", nullable=true, enum={"string", "number", "options", "file", "location", "date", "time"}),
 *     @OA\Property(property="calculator_unit", type="string", nullable=true, enum={"kg", "g", "person", "day", "km", "degree"}),
 *     @OA\Property(property="question_type", type="string", enum={"LIBRE", "OPCIONES", "UBICACION", "FILE"}),
 *     @OA\Property(property="type_field", type="string", enum={"NUMERICO", "DECIMAL", "FECHA", "LARGO", "CORTO"}, example="DECIMAL"),
 *     @OA\Property(property="response_scope", type="string", enum={"PARTICIPATION", "MEASUREMENT"}),
 *     @OA\Property(property="applicable_days", type="array", nullable=true, @OA\Items(type="integer")),
 *     @OA\Property(property="scenario", type="string", nullable=true, enum={"MONITORING_COMBINED", "MONITORING_MOON_ONLY"})
 * )
 *
 * @OA\Schema(
 *     schema="SurveyQuestion",
 *     title="SurveyQuestion",
 *     description="Modelo de SurveyQuestion",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="survey_id", type="integer", example=101),
 *     @OA\Property(property="question_text", type="string", example="¿Qué fuentes de energía utiliza en su hogar?"),
 *     @OA\Property(property="instrument_key", type="string", nullable=true),
 *     @OA\Property(property="question_type", type="string", example="LIBRE"),
 *     @OA\Property(property="type_field", type="string", example="DECIMAL"),
 *     @OA\Property(property="accepts_decimals", type="boolean", example=true),
 *     @OA\Property(property="response_scope", type="string", enum={"PARTICIPATION", "MEASUREMENT"}),
 *     @OA\Property(property="applicable_days", type="array", nullable=true, @OA\Items(type="integer")),
 *     @OA\Property(property="scenario", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-26T21:44:24"),
 * )
 */
class SurveyQuestionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id ?? null,
            'survey_id' => $this->survey_id ?? null,
            'question_text' => $this->question_text ?? null,
            'instrument_key' => $this->instrument_key,
            'calculator_key' => $this->calculator_key,
            'calculator_value_type' => $this->calculator_value_type,
            'calculator_unit' => $this->calculator_unit,
            'question_type' => $this->question_type ?? null,
            'type_field' => $this->type_field ?? null,
            'accepts_decimals' => strtoupper((string) $this->type_field) === \App\Models\SurveyQuestion::FIELD_TYPE_DECIMAL,
            'response_scope' => $this->effectiveResponseScope(),
            'applicable_days' => $this->applicable_days,
            'scenario' => $this->scenario,
            'section' => $this->section_key ? [
                'key' => $this->section_key,
                'title' => $this->section_title,
            ] : null,

            'eje' => $this->eje ?? null,
            'justification' => $this->justification ?? null,

            'survey_name' => $this?->survey?->survey_name ?? null,
            'order' => $this->order ?? null,
            'ods' => $this->ods ?? null,
            'is_required' => $this->is_required ?? null,
            'survey_questions_options' => $this->survey_questions_options ?? [],
            'created_at' => $this->created_at ?? null,
        ];
    }
}
