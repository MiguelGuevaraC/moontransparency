<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class Co2CalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'min:1', 'exists:proyects,id'],
            'baseline_survey_id' => ['required', 'integer', 'min:1', 'exists:surveys,id'],
            'monitoring_survey_id' => ['nullable', 'integer', 'min:1', 'exists:surveys,id'],
            'household_ids' => ['nullable', 'array', 'max:'.config('co2.sample_limit', 20)],
            'household_ids.*' => ['integer', 'distinct', 'exists:households,id'],
            'sample_limit' => ['nullable', 'integer', 'min:1', 'max:'.config('co2.sample_limit', 20)],
            'parameters' => ['nullable', 'array'],
            'parameters.monitoring_year' => ['nullable', 'integer', 'between:2000,2100'],
            'parameters.years' => ['nullable', 'numeric', 'gt:0', 'max:50'],
            'parameters.operational_groups' => ['nullable', 'array', 'min:1', 'max:20'],
            'parameters.operational_groups.*.quantity' => ['required_with:parameters.operational_groups', 'numeric', 'min:0'],
            'parameters.operational_groups.*.operational' => ['required_with:parameters.operational_groups', 'boolean'],
            'parameters.operational_groups.*.months' => ['required_with:parameters.operational_groups', 'numeric', 'between:0,12'],
            'parameters.usage_cap' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.usage_groups' => ['nullable', 'array', 'min:1', 'max:20'],
            'parameters.usage_groups.*.quantity' => ['required_with:parameters.usage_groups', 'numeric', 'min:0'],
            'parameters.usage_groups.*.percentage' => ['required_with:parameters.usage_groups', 'numeric', 'between:0,1'],
            'parameters.downward_adjustment_factor' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.number_of_stoves' => ['nullable', 'numeric', 'min:0'],
            'parameters.manufacturing_emission_t_per_stove' => ['nullable', 'numeric', 'min:0'],
            'parameters.stove_lifetime_years' => ['nullable', 'numeric', 'gt:0'],
            'parameters.destruction_evidence' => ['nullable', 'boolean'],
            'parameters.market_leakage_percentage' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.monitoring_method' => ['nullable', 'string', 'in:MANUAL,SENSORS'],
            'parameters.hawthorne_factor' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.per_capita_cap_t_year' => ['nullable', 'numeric', 'gt:0'],
            'parameters.net_calorific_value_tj_t' => ['nullable', 'numeric', 'gt:0'],
            'parameters.co2_emission_factor_t_tj' => ['nullable', 'numeric', 'gt:0'],
            'parameters.non_renewable_biomass_fraction' => ['nullable', 'numeric', 'between:0,1'],
            'parameters.non_co2_emission_factor_t_tj' => ['nullable', 'numeric', 'min:0'],
            'parameters.adult_equivalent' => ['nullable', 'array'],
            'parameters.adult_equivalent.children_0_14' => ['nullable', 'numeric', 'gt:0'],
            'parameters.adult_equivalent.women_over_14' => ['nullable', 'numeric', 'gt:0'],
            'parameters.adult_equivalent.men_15_59' => ['nullable', 'numeric', 'gt:0'],
            'parameters.adult_equivalent.men_over_59' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
