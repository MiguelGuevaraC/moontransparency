<?php
namespace App\Http\Requests\RespondentRequest;

use App\Http\Requests\IndexRequest;

class IndexRespondentRequest extends IndexRequest
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

            'number_document' => 'nullable|string',
            'names'           => 'nullable|string',
            'date_of_birth'   => 'nullable|string',
            'phone'           => 'nullable|string',
            'email'           => 'nullable|string',
            'genero'          => 'nullable|string',
            'sort'          => 'nullable|string',
            'direction'          => 'nullable|string',
        ];
    }
}
