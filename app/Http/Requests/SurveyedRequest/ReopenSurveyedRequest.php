<?php

namespace App\Http\Requests\SurveyedRequest;

use Illuminate\Foundation\Http\FormRequest;

class ReopenSurveyedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo de reapertura es obligatorio.',
            'reason.string' => 'El motivo de reapertura debe ser un texto.',
            'reason.max' => 'El motivo de reapertura no debe superar los 1000 caracteres.',
        ];
    }
}
