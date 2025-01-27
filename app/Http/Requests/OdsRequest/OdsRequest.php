<?php

namespace App\Http\Requests\OdsRequest;

use App\Http\Requests\IndexRequest;

class OdsRequest extends IndexRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [

            'code'=> 'nullable|string',
            'name'=> 'nullable|string',
            'description'=> 'nullable|string',

        ];
    }
}