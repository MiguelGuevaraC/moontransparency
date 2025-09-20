<?php

namespace App\Http\Requests\TercerosRequest;

use App\Http\Requests\StoreRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SearchRucRequest extends StoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'required|numeric|digits:11',
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'El RUC es obligatorio',
            'search.numeric' => 'El RUC debe contener solo números',
            'search.digits' => 'El RUC debe tener exactamente 8 dígitos',
        ];
    }


}
