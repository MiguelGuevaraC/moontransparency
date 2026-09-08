<?php

namespace App\Http\Resources;

use App\Models\Surveyed;
use App\Services\CalculatorInputMapper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="CalculatorParticipation",
 *     type="object",
 *     required={"contract", "participation", "respondent", "household", "survey", "project", "recorded_days", "missing_days", "fields", "days", "calculator_input"},
 *
 *     @OA\Property(
 *         property="contract",
 *         type="object",
 *         @OA\Property(property="version", type="string", example="1.3"),
 *         @OA\Property(property="expected_days", type="integer", example=7),
 *         @OA\Property(property="missing_value", nullable=true, example=null),
 *         @OA\Property(
 *             property="units",
 *             type="object",
 *             @OA\Property(property="weight", type="string", example="kg"),
 *             @OA\Property(property="people", type="string", example="person"),
 *             @OA\Property(property="day", type="string", example="day")
 *         )
 *     ),
 *     @OA\Property(
 *         property="participation",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="status", type="string", enum={"BORRADOR", "FINALIZADA"}),
 *         @OA\Property(property="data_state", type="string", enum={"PARTIAL", "FINAL"}),
 *         @OA\Property(property="is_partial", type="boolean")
 *     ),
 *     @OA\Property(property="recorded_days", type="array", @OA\Items(type="integer"), example={1, 2}),
 *     @OA\Property(property="missing_days", type="array", @OA\Items(type="integer"), example={3, 4, 5, 6, 7}),
 *     @OA\Property(property="fields", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="calculator_input", type="object", description="Datos semánticos listos para llenar la calculadora KPT"),
 *     @OA\Property(
 *         property="days",
 *         type="array",
 *         minItems=1,
 *         maxItems=31,
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="day_number", type="integer", minimum=1, maximum=31),
 *             @OA\Property(property="recorded", type="boolean"),
 *             @OA\Property(property="measurement_id", type="integer", nullable=true),
 *             @OA\Property(property="values", type="object")
 *         )
 *     )
 * )
 */
class CalculatorParticipationResource extends JsonResource
{
    public function toArray($request)
    {
        $status = $this->status ?? Surveyed::STATUS_DRAFT;
        $questions = $this->survey->survey_questions
            ->sortBy(function ($question) {
                return [(float) ($question->order ?? PHP_INT_MAX), (int) $question->id];
            })
            ->values();
        $fields = $questions->map(fn ($question) => $this->fieldDefinition($question));
        $legacyHouseholdIdentifier = $this->legacyHouseholdIdentifier($fields);
        $expectedDays = $this->survey?->expectedDays() ?? config('surveying.default_expected_days', 7);
        $measurements = $this->measurements->keyBy('day_number');
        $recordedDays = $measurements->keys()
            ->map(static fn ($day) => (int) $day)
            ->filter(static fn (int $day) => $day >= 1 && $day <= $expectedDays)
            ->sort()
            ->values();
        $missingDays = collect(range(1, $expectedDays))->diff($recordedDays)->values();
        $days = collect(range(1, $expectedDays))->map(function (int $day) use ($fields, $measurements) {
            $measurement = $measurements->get($day);
            $answers = $measurement
                ? $measurement->surveyed_responses->keyBy('survey_question_id')
                : collect();
            $values = $fields->mapWithKeys(function (array $field) use ($answers) {
                $answer = $answers->get($field['question_id']);

                return [$field['key'] => $this->answerValue($answer, $field)];
            });

            return [
                'day_number' => $day,
                'recorded' => $measurement !== null,
                'measurement_id' => $measurement?->id,
                'recorded_at' => $measurement?->created_at?->toIso8601String(),
                'values' => $values,
            ];
        });
        $calculatorInput = app(CalculatorInputMapper::class)->map(
            $fields,
            $days
        );

        return [
            'contract' => [
                'version' => config('surveying.calculator.contract_version', '1.3'),
                'expected_days' => $expectedDays,
                'missing_value' => null,
                'units' => [
                    'weight' => 'kg',
                    'people' => 'person',
                    'day' => 'day',
                ],
                'missing_data_rules' => [
                    'missing_day' => 'El día se entrega con recorded=false, measurement_id=null y valores null.',
                    'missing_answer' => 'El campo se entrega con value=null y validation_status=missing.',
                    'invalid_number' => 'El campo se entrega con value=null, conserva raw_value y usa validation_status=invalid.',
                ],
                'unit_policy' => [
                    'canonical_weight_unit' => config('surveying.calculator.canonical_weight_unit', 'kg'),
                    'accepted_weight_units' => config('surveying.calculator.supported_weight_units', ['kg', 'g']),
                    'grams_to_kilograms' => 0.001,
                    'raw_value_preserved' => true,
                ],
            ],
            'participation' => [
                'id' => $this->id,
                'status' => $status,
                'data_state' => $status === Surveyed::STATUS_FINALIZED ? 'FINAL' : 'PARTIAL',
                'is_partial' => $status !== Surveyed::STATUS_FINALIZED,
                'completed_at' => $this->completed_at?->toIso8601String(),
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
            'respondent' => [
                'id' => $this->respondent?->id,
                'number_document' => $this->respondent?->number_document,
                'names' => $this->respondent?->names,
            ],
            'household' => [
                'id' => $this->household?->id,
                'code' => $this->household?->code,
                'identifier' => $this->household?->code ?? $legacyHouseholdIdentifier,
                'source' => $this->household
                    ? 'HOUSEHOLD'
                    : ($legacyHouseholdIdentifier ? 'LEGACY_RESPONSE' : null),
            ],
            'survey' => [
                'id' => $this->survey?->id,
                'name' => $this->survey?->survey_name,
                'type' => $this->survey?->survey_type,
            ],
            'project' => [
                'id' => $this->survey?->proyect?->id,
                'name' => $this->survey?->proyect?->name,
            ],
            'recorded_days' => $recordedDays,
            'missing_days' => $missingDays,
            'fields' => $fields,
            'days' => $days,
            'calculator_input' => $calculatorInput,
        ];
    }

    private function fieldDefinition($question): array
    {
        $valueType = $question->calculator_value_type ?: $this->valueType($question);
        $sourceUnit = $question->calculator_unit ?: null;
        $unit = $this->canonicalUnit($question->calculator_key, $sourceUnit);

        return [
            'key' => 'question_'.$question->id,
            'calculator_key' => $question->calculator_key,
            'code' => Str::of($question->question_text)
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString(),
            'question_id' => $question->id,
            'label' => $question->question_text,
            'order' => $question->order !== null ? (float) $question->order : null,
            'value_type' => $valueType,
            'unit' => $unit,
            'source_unit' => $sourceUnit,
            'conversion_factor' => $this->conversionFactor($sourceUnit, $unit),
            'required' => (bool) $question->is_required,
        ];
    }

    private function valueType($question): string
    {
        $questionType = Str::upper((string) $question->question_type);
        $fieldType = Str::upper((string) $question->type_field);

        if ($questionType === 'OPCIONES') {
            return 'options';
        }

        if ($questionType === 'FILE') {
            return 'file';
        }

        if ($questionType === 'UBICACION') {
            return 'location';
        }

        if (in_array($fieldType, ['NUMERICO', 'NUMERO', 'NUMBER'], true)) {
            return 'number';
        }

        if ($fieldType === 'FECHA') {
            return 'date';
        }

        return 'string';
    }

    private function canonicalUnit(?string $calculatorKey, ?string $sourceUnit): ?string
    {
        if ($calculatorKey && Str::endsWith($calculatorKey, '_kg')) {
            return config('surveying.calculator.canonical_weight_unit', 'kg');
        }

        return $sourceUnit;
    }

    private function conversionFactor(?string $sourceUnit, ?string $unit): float
    {
        return $sourceUnit === 'g' && $unit === 'kg' ? 0.001 : 1.0;
    }

    private function answerValue($answer, array $field): array
    {
        if (! $answer) {
            return $this->missingAnswer($field);
        }

        $rawValue = $answer->response_text;

        if ($field['value_type'] === 'options') {
            $value = $answer->surveyed_responses_options
                ->map(fn ($selected) => $selected->survey_question_options?->description)
                ->filter(static fn ($option) => $option !== null && $option !== '')
                ->values()
                ->all();

            return [
                'question_id' => $field['question_id'],
                'value' => $value ?: null,
                'raw_value' => null,
                'unit' => $field['unit'],
                'source_unit' => $field['source_unit'],
                'conversion_factor' => $field['conversion_factor'],
                'validation_status' => $value ? 'valid' : 'missing',
            ];
        }

        if ($field['value_type'] === 'file') {
            $value = $answer->file_path ? url(Storage::url($answer->file_path)) : null;

            return [
                'question_id' => $field['question_id'],
                'value' => $value,
                'raw_value' => null,
                'unit' => $field['unit'],
                'source_unit' => $field['source_unit'],
                'conversion_factor' => $field['conversion_factor'],
                'validation_status' => $value ? 'valid' : 'missing',
            ];
        }

        $missing = $rawValue === null || trim((string) $rawValue) === '';

        if ($field['value_type'] === 'number') {
            $valid = ! $missing && is_numeric($rawValue);
            $value = $valid ? ($rawValue + 0) * $field['conversion_factor'] : null;

            return [
                'question_id' => $field['question_id'],
                'value' => $value,
                'raw_value' => $rawValue,
                'unit' => $field['unit'],
                'source_unit' => $field['source_unit'],
                'conversion_factor' => $field['conversion_factor'],
                'validation_status' => $missing ? 'missing' : ($valid ? 'valid' : 'invalid'),
            ];
        }

        return [
            'question_id' => $field['question_id'],
            'value' => $missing ? null : $rawValue,
            'raw_value' => $rawValue,
            'unit' => $field['unit'],
            'source_unit' => $field['source_unit'],
            'conversion_factor' => $field['conversion_factor'],
            'validation_status' => $missing ? 'missing' : 'valid',
        ];
    }

    private function missingAnswer(array $field): array
    {
        return [
            'question_id' => $field['question_id'],
            'value' => null,
            'raw_value' => null,
            'unit' => $field['unit'],
            'source_unit' => $field['source_unit'],
            'conversion_factor' => $field['conversion_factor'],
            'validation_status' => 'missing',
        ];
    }

    private function legacyHouseholdIdentifier($fields): ?string
    {
        $field = $fields->firstWhere('calculator_key', 'household.identifier');

        if (! $field) {
            return null;
        }

        $answer = $this->surveyed_responses
            ->sortBy(fn ($response) => (int) ($response->measurement?->day_number ?? PHP_INT_MAX))
            ->firstWhere('survey_question_id', $field['question_id']);

        return $answer && trim((string) $answer->response_text) !== ''
            ? (string) $answer->response_text
            : null;
    }
}
