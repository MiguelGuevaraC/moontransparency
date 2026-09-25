<?php

namespace App\Services;

use App\Models\SurveyQuestion;
use Illuminate\Validation\ValidationException;

class SurveyResponseValueValidator
{
    public function normalize(SurveyQuestion $question, mixed $value, string $field = 'response_text'): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (! is_scalar($value)) {
            throw ValidationException::withMessages([
                $field => 'La respuesta debe ser un valor simple.',
            ]);
        }

        $value = trim((string) $value);
        $fieldType = strtoupper(trim((string) $question->type_field));

        if (in_array($fieldType, SurveyQuestion::INTEGER_FIELD_TYPES, true)) {
            if (! preg_match('/^[+-]?\d+$/', $value)) {
                throw ValidationException::withMessages([
                    $field => 'La respuesta debe ser un número entero.',
                ]);
            }

            return $value;
        }

        if ($fieldType === SurveyQuestion::FIELD_TYPE_DECIMAL) {
            $normalized = str_replace(',', '.', $value);

            if (! preg_match('/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/', $normalized)) {
                throw ValidationException::withMessages([
                    $field => 'La respuesta debe ser un número decimal válido.',
                ]);
            }

            return $this->canonicalDecimal($normalized);
        }

        if (mb_strlen($value) > 1000) {
            throw ValidationException::withMessages([
                $field => 'La respuesta no debe exceder los 1000 caracteres.',
            ]);
        }

        return $value;
    }

    private function canonicalDecimal(string $value): string
    {
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, null);
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;

        if ($fraction === null) {
            return ($negative && $integer !== '0' ? '-' : '').$integer;
        }

        $fraction = rtrim($fraction, '0');

        return ($negative && ($integer !== '0' || $fraction !== '') ? '-' : '')
            .$integer
            .($fraction === '' ? '' : '.'.$fraction);
    }
}
