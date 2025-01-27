<?php
namespace App\Http\Requests\ProyectRequest;

use App\Http\Requests\IndexRequest;

class IndexProyectRequest extends IndexRequest
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
            'name'              => 'nullable|string',
            'type'              => 'nullable|string',
            'status'            => 'nullable|string',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'description'       => 'nullable|string',
            'budget_estimated'  => 'nullable|string',
            'nro_beneficiaries' => 'nullable|string',
            'impact_initial'    => 'nullable|string',
            'impact_final'      => 'nullable|string',

        ];
    }

    public function messages(): array
    {
        return [
            'name.string'              => 'El nombre debe ser una cadena de texto.',
            'type.string'              => 'El tipo debe ser una cadena de texto.',
            'status.string'            => 'El estado debe ser una cadena de texto.',
            'start_date.date'          => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.date'            => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal'  => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'description.string'       => 'La descripción debe ser una cadena de texto.',
            'budget_estimated.string'  => 'El presupuesto estimado debe ser una cadena de texto.',
            'nro_beneficiaries.string' => 'El número de beneficiarios debe ser una cadena de texto.',
            'impact_initial.string'    => 'El impacto inicial debe ser una cadena de texto.',
            'impact_final.string'      => 'El impacto final debe ser una cadena de texto.',
        ];
    }

}
