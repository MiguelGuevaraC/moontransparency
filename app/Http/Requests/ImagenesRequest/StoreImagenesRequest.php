<?php
namespace App\Http\Requests\ImagenesRequest;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\Schema(
 *     schema="ImagenesRequest",
 *     required={"name_table", "images"},
 *     @OA\Property(property="name_table", type="string", description="Nombre de la tabla", example="allies", enum={"proyects", "allies", "donations", "surveys"}),
 *     @OA\Property(property="proyect_id", type="integer", nullable=true, description="ID del proyecto (requerido si name_table='proyects')", example=1),
 *     @OA\Property(property="ally_id", type="integer", nullable=true, description="ID del aliado (requerido si name_table='allies')", example=2),
 *     @OA\Property(property="donation_id", type="integer", nullable=true, description="ID de la donación (requerido si name_table='donations')", example=3),
 *     @OA\Property(property="survey_id", type="integer", nullable=true, description="ID de la encuesta (requerido si name_table='surveys')", example=4),
 *     @OA\Property(property="images", type="array", description="Lista de imágenes (mínimo 1)", @OA\Items(type="object", 
 *         @OA\Property(property="file", type="string", format="binary", description="Archivo a subir"),
 *         @OA\Property(property="name", type="string", nullable=true, description="Nombre del archivo", example="documento.pdf")
 *     ))
 * )
 */
class StoreImagenesRequest extends StoreRequest
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
    public function rules()
    {
        return [
            'name_table'    => 'required|string|in:proyects,allies,donations,surveys',
            'proyect_id'    => 'required_if:name_table,proyects|nullable|exists:proyects,id',
            'ally_id'       => 'required_if:name_table,allies|nullable|exists:allies,id',
            'donation_id'   => 'required_if:name_table,donations|nullable|exists:donations,id',
            'survey_id'     => 'required_if:name_table,surveys|nullable|exists:surveys,id',
            'images'        => 'required|array|min:1',
            'images.*.file' => [
                'required_without:images.*.name',
                'file',
                'mimes:jpeg,jpg,png,gif,pdf,doc,docx,xls,xlsx',
                'max:4096',
            ],
            'images.*.name' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name_table.required'            => 'El campo name_table es obligatorio.',
            'name_table.string'              => 'El campo name_table debe ser una cadena de texto.',
            'name_table.in'                  => 'El campo name_table debe ser uno de los siguientes valores: proyects, allies, donations, surveys.',

            'proyect_id.required_if'         => 'El campo proyect_id es obligatorio cuando name_table es "proyects".',
            'proyect_id.exists'              => 'El proyecto seleccionado no es válido.',

            'ally_id.required_if'            => 'El campo ally_id es obligatorio cuando name_table es "allies".',
            'ally_id.exists'                 => 'El aliado seleccionado no es válido.',

            'donation_id.required_if'        => 'El campo donation_id es obligatorio cuando name_table es "donations".',
            'donation_id.exists'             => 'La donación seleccionada no es válida.',

            'survey_id.required_if'          => 'El campo survey_id es obligatorio cuando name_table es "surveys".',
            'survey_id.exists'               => 'La encuesta seleccionada no es válida.',

            'images.required'                => 'Debe subir al menos un archivo.',
            'images.array'                   => 'El campo imágenes debe ser un arreglo.',
            'images.min'                     => 'Debe subir al menos un archivo.',

            'images.*.file.required_without' => 'Debe proporcionar un archivo si no se especifica un nombre.',
            'images.*.file'                  => 'El archivo subido no es válido.',
            'images.*.file.mimes'            => 'El archivo debe ser de tipo: jpeg, jpg, png, gif, pdf, doc, docx, xls o xlsx.',
            'images.*.file.max'              => 'El archivo no debe superar los 4MB.',

            'images.*.name.string'           => 'El nombre del archivo debe ser una cadena de texto.',
            'images.*.name.max'              => 'El nombre del archivo no debe superar los 255 caracteres.',
        ];
    }

}
