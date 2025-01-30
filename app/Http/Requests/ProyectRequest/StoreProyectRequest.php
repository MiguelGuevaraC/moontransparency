<?php
namespace App\Http\Requests\ProyectRequest;

use App\Http\Requests\StoreRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class StoreProyectRequest extends StoreRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name'              => 'required|string|max:255',
            'type'              => 'required|string|max:100',

            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'location'          => 'nullable|string|max:255',
            'images.*'          => 'nullable|file|mimes:jpeg,jpg,png,gif|max:2048',
            'description'       => 'nullable|string',
            'budget_estimated'  => 'required|numeric|min:0',
            'nro_beneficiaries' => 'required|integer|min:1',
            'impact_initial'    => 'nullable|string',
            'impact_final'      => 'nullable|string',

            'ods'               => 'required|array',                                 // Asegura que sea un arreglo
            'ods.*'             => 'required|integer|exists:ods,id,deleted_at,NULL', // Valida que cada ID exista y no esté eliminado
        ];
    }

    /**
     * Get the validation messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required'              => 'El nombre del proyecto es obligatorio.',
            'name.string'                => 'El nombre del proyecto debe ser una cadena de texto.',
            'name.max'                   => 'El nombre del proyecto no puede exceder los 255 caracteres.',
            'type.required'              => 'El tipo de proyecto es obligatorio.',

            'start_date.required'        => 'La fecha de inicio es obligatoria.',
            'start_date.date'            => 'La fecha de inicio debe ser una fecha válida.',
            'end_date.required'          => 'La fecha de fin es obligatoria.',
            'end_date.date'              => 'La fecha de fin debe ser una fecha válida.',
            'end_date.after_or_equal'    => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'budget_estimated.required'  => 'El presupuesto estimado es obligatorio.',
            'budget_estimated.numeric'   => 'El presupuesto debe ser un valor numérico.',
            'budget_estimated.min'       => 'El presupuesto debe ser al menos 0.',
            'nro_beneficiaries.required' => 'El número de beneficiarios es obligatorio.',
            'nro_beneficiaries.integer'  => 'El número de beneficiarios debe ser un valor entero.',
            'images.*.file'              => 'Cada archivo debe ser un archivo válido.',
            'images.*.mimes'             => 'Solo se permiten archivos de tipo: jpeg, jpg, png, gif.',
            'images.*.max'               => 'El archivo no debe exceder los 2 MB.',

            'ods.required'               => 'El campo Ods es obligatorio si se proporciona.',
            'ods.array'                  => 'El campo Ods debe ser un arreglo de IDs.',
            'ods.*.required'             => 'Cada ID de Ods es obligatorio.',
            'ods.*.integer'              => 'Cada ID de Ods debe ser un número entero.',
            'ods.*.exists'               => 'El ID de Ods proporcionado no existe o ha sido eliminado.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name'              => 'nombre del proyecto',
            'type'              => 'tipo del proyecto',
            'status'            => 'estado del proyecto',
            'start_date'        => 'fecha de inicio',
            'end_date'          => 'fecha de fin',
            'location'          => 'ubicación',
            'images'            => 'imágenes',
            'description'       => 'descripción',
            'budget_estimated'  => 'presupuesto estimado',
            'nro_beneficiaries' => 'número de beneficiarios',
            'impact_initial'    => 'impacto inicial',
            'impact_final'      => 'impacto final',
        ];
    }

}
