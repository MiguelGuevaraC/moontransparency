<?php
namespace App\Http\Requests\AllyRequest;

use App\Http\Requests\IndexRequest;

class IndexAllyRequest extends IndexRequest
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
            'ruc_dni'            => 'nullable|string',
            'first_name'         => 'nullable|string',
            'last_name'          => 'nullable|string',
            'business_name'      => 'nullable|string',
            'description'        => 'nullable|string',
            'link'               => 'nullable|string',

            'phone'              => 'nullable|string',
            'email'              => 'nullable|string',
            'area_of_interest'   => 'nullable|string',
            'participation_type' => 'nullable|string',
            'date_start'         => 'nullable|start',
        ];
    }
}
