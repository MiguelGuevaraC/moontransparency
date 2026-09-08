<?php

namespace App\Http\Requests\SurveyedRequest;

use App\Http\Requests\StoreRequest;
use App\Models\Household;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use Illuminate\Validation\Validator;

class StoreSurveyedRequest extends StoreRequest
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
            'number_document' => 'required|string|max:20',
            'names' => 'required|string|max:1000',
            'date_of_birth' => 'nullable|date',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'genero' => 'nullable|string|max:255',
            'household_code' => ['nullable', 'string', 'max:64', 'regex:'.Household::codePattern(), 'exists:households,code'],

            'survey_id' => 'required|integer|exists:surveys,id',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'day_number' => 'nullable|integer|between:1,7',
            'responses' => 'sometimes|array',
            'responses.*.survey_question_id' => 'required|integer|exists:survey_questions,id',
            'responses.*.survey_question_option_id' => 'nullable|array',
            'responses.*.survey_question_option_id.*' => 'integer|exists:survey_question_options,id',
            'responses.*.response_text' => 'nullable|string|max:1000',
            'responses.*.file' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,mp4,zip', // ajustar tipos/size según necesidad

        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $numberDocument = $this->input('number_document');
            $surveyId = $this->input('survey_id');

            // Buscar la persona por número de documento
            $person = Respondent::where('number_document', $numberDocument)->first();

            if ($person) {
                // Verificar si ya existe un registro de encuesta en el mismo proyecto
                $survey = Survey::find($surveyId);

                if ($survey && $survey->proyect_id) {
                    $exists = Surveyed::where('respondent_id', $person->id)
                        /*->whereHas('survey', function ($q) use ($survey) {
                            $q->where('proyect_id', $survey->proyect_id);
                        })*/
                        ->where('survey_id', $survey->id)
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add('number_document', 'Esta persona ya tiene un registro para esta encuesta. Continúe usando el registro existente.');
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
            'number_document.required' => 'El número de documento es obligatorio.',
            'number_document.string' => 'El número de documento debe ser una cadena de texto.',
            'number_document.max' => 'El número de documento no debe exceder los 20 caracteres.',

            'names.required' => 'El nombre es obligatorio.',
            'names.string' => 'El nombre debe ser una cadena de texto.',
            'names.max' => 'El nombre no debe exceder los 1000 caracteres.',

            'date_of_birth.date' => 'La fecha de nacimiento debe tener un formato válido.',

            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max' => 'El teléfono no debe exceder los 255 caracteres.',

            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max' => 'El correo electrónico no debe exceder los 255 caracteres.',

            'genero.string' => 'El género debe ser una cadena de texto.',
            'genero.max' => 'El género no debe exceder los 255 caracteres.',

            'household_code.string' => 'El ID del hogar debe ser un texto.',
            'household_code.max' => 'El ID del hogar no debe superar los 64 caracteres.',
            'household_code.regex' => 'El ID del hogar debe tener el formato '.Household::formatCode(1).'.',
            'household_code.exists' => 'El ID del hogar indicado no existe.',

            'survey_id.required' => 'El campo survey_id es obligatorio.',
            'survey_id.integer' => 'El campo survey_id debe ser un número entero.',
            'survey_id.exists' => 'El survey_id no existe en la base de datos.',

            'latitude.required_with' => 'La latitud y la longitud deben enviarse juntas.',
            'latitude.numeric' => 'La latitud debe ser un número.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90.',
            'longitude.required_with' => 'La latitud y la longitud deben enviarse juntas.',
            'longitude.numeric' => 'La longitud debe ser un número.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180.',

            'day_number.integer' => 'El día de medición debe ser un número entero.',
            'day_number.between' => 'El día de medición debe estar entre 1 y 7.',

            'responses.array' => 'Las respuestas deben enviarse como un arreglo.',

            'responses.*.survey_question_id.required' => 'La pregunta es obligatoria.',
            'responses.*.survey_question_id.integer' => 'El ID de la pregunta debe ser un número entero.',
            'responses.*.survey_question_id.exists' => 'La pregunta seleccionada no existe.',

            'responses.*.survey_question_option_id.array' => 'Las opciones seleccionadas deben ser un arreglo.',
            'responses.*.survey_question_option_id.*.integer' => 'El ID de la opción debe ser un número entero.',
            'responses.*.survey_question_option_id.*.exists' => 'Una de las opciones seleccionadas no existe.',

            'responses.*.response_text.string' => 'La respuesta de texto debe ser una cadena.',
            'responses.*.response_text.max' => 'La respuesta de texto no debe exceder los 1000 caracteres.',

            'responses.*.file.file' => 'El archivo debe ser un archivo válido.',
            'responses.*.file.max' => 'El archivo no debe exceder los 5 MB.',
            'responses.*.file.mimes' => 'Tipos permitidos: jpg, jpeg, png, pdf, doc, docx, xlsx, mp4, zip.',
        ];
    }
}
