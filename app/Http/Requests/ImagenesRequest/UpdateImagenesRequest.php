<?php
namespace App\Http\Requests\ImagenesRequest;

use App\Http\Requests\UpdateRequest;

class UpdateImagenesRequest extends UpdateRequest
{

    public function rules()
    {
        return [
            'name_image'    => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'name_table.required'    => 'El campo name_table es obligatorio.',
            'name_image.string'      => 'El campo name_table debe ser una cadena de texto.',
        ];
    }

}
