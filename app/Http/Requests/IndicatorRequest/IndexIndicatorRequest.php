<?php
namespace App\Http\Requests\IndicatorRequest;

use App\Http\Requests\IndexRequest;

class IndexIndicatorRequest extends IndexRequest
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
            'project_id'     => 'nullable|string',
            'indicator_name' => 'nullable|string',
            'target_value'   => 'nullable|string',
            'progress_value' => 'nullable|string',
            'unit'           => 'nullable|string',
        ];
    }
}
