<?php

namespace App\Http\Requests;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Co2CalculatorEmbedLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'min:1', 'exists:proyects,id'],
            'baseline_survey_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('surveys', 'id')->where(fn ($query) => $query
                    ->where('status', Survey::STATUS_ACTIVE)
                    ->whereNull('deleted_at')),
            ],
            'monitoring_survey_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('surveys', 'id')->where(fn ($query) => $query
                    ->where('status', Survey::STATUS_ACTIVE)
                    ->whereNull('deleted_at')),
            ],
            'household_ids' => ['nullable', 'array', 'max:'.config('co2.sample_limit', 20)],
            'household_ids.*' => ['integer', 'distinct', 'exists:households,id'],
            'sample_limit' => ['nullable', 'integer', 'min:1', 'max:'.config('co2.sample_limit', 20)],
        ];
    }
}
