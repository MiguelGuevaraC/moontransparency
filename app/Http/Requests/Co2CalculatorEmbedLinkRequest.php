<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Co2CalculatorEmbedLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['prohibited'],
            'baseline_survey_id' => [
                'prohibited',
            ],
            'monitoring_survey_id' => [
                'prohibited',
            ],
            'household_ids' => ['nullable', 'array', 'max:'.config('co2.sample_limit', 20)],
            'household_ids.*' => ['integer', 'distinct', 'exists:households,id'],
            'sample_limit' => ['nullable', 'integer', 'min:1', 'max:'.config('co2.sample_limit', 20)],
        ];
    }
}
