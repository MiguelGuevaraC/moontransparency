<?php
namespace App\Http\Requests\ActivityRequest;

use App\Http\Requests\IndexRequest;

class IndexActivityRequest extends IndexRequest
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
            'name'=>'nullable|string',
            'start_date'=>'nullable|string',
            'end_date'=>'nullable|string',
            'proyect_id'=>'nullable|string',
            'objective'=>'nullable|string',
            'total_amount'=>'nullable|string',
            'collected_amount'=>'nullable|string',
            'status'=>'nullable|string',
        ];
    }
}
