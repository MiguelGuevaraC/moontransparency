<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyCleanupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'confirmation' => [
                'required',
                'string',
                Rule::in([$this->expectedConfirmation()]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo de limpieza es obligatorio.',
            'reason.min' => 'El motivo de limpieza debe contener al menos 10 caracteres.',
            'confirmation.in' => 'La confirmación no coincide con la encuesta que se intenta limpiar.',
        ];
    }

    public function expectedConfirmation(): string
    {
        return 'LIMPIAR ENCUESTA '.(int) $this->route('id');
    }
}
