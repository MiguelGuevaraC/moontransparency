<?php
namespace App\Http\Requests\ImagenesRequest;

use App\Http\Requests\IndexRequest;

class IndexImagenesRequest extends IndexRequest
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
            'name_table'=> 'nullable|string',
            'proyect_id'=> 'nullable|string',
            'survey_id'=> 'nullable|string',
            'donation_id'=> 'nullable|string',
            'ally_id'=> 'nullable|string',
            'user_created_id'=> 'nullable|string',
            'created_at'=> 'nullable|string',
        ];
    }
}
