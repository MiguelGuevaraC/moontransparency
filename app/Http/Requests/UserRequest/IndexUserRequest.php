<?php
namespace App\Http\Requests\UserRequest;

use App\Http\Requests\IndexRequest;

class IndexUserRequest extends IndexRequest
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

            'type_document'   => 'nullable|string',
            'number_document' => 'nullable|string',
            'names'           => 'nullable|string',
            'username'        => 'nullable|string',
            'address'         => 'nullable|string',
            'phone'           => 'nullable|string',
            'email'           => 'nullable|string',

        ];
    }
}
