<?php
namespace App\Http\Requests\SurveyedRequest;

use App\Http\Requests\StoreRequest;
use App\Models\Respondent;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Validation\Rule;
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
            'names'           => 'required|string|max:1000',
            'date_of_birth'   => 'nullable|date',
            'phone'           => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255',
            'genero'          => 'nullable|string|max:255',
    
            'survey_id' => 'required|integer|exists:surveys,id',
            'responses' => 'required|array',
            'responses.*.survey_question_id' => 'required|integer|exists:survey_questions,id',
            'responses.*.survey_question_option_id' => 'nullable|array',
            'responses.*.survey_question_option_id.*' => 'integer|exists:survey_question_options,id',
            'responses.*.response_text' => 'nullable|string|max:1000',
        ];
    }
    
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $responses = $this->input('responses', []);
            foreach ($responses as $index => $response) {
                if (empty($response['survey_question_id'])) {
                    continue;
                }
    
                $question = SurveyQuestion::find($response['survey_question_id']);
    
                if (!$question) {
                    continue; // ya será rechazado por el exists
                }
    
                if ($question->question_type === 'LIBRE' && empty($response['response_text'])) {
                    $validator->errors()->add("responses.$index.response_text", "La respuesta en texto es obligatoria para preguntas de tipo LIBRE.");
                }
    
                if ($question->question_type === 'OPCIONES' && empty($response['survey_question_option_id'])) {
                    $validator->errors()->add("responses.$index.survey_question_option_id", "Debe seleccionar al menos una opción para preguntas de tipo OPCIONES.");
                }
            }

            $numberDocument = $this->input('number_document');
            $surveyId = $this->input('survey_id');
    
            // Buscar la persona por número de documento
            $person = Respondent::where('number_document', $numberDocument)->first();
    
            if ($person) {
                // Verificar si ya existe un registro de encuesta en el mismo proyecto
                $survey = Survey::find($surveyId);
    
                if ($survey && $survey->proyect_id) {
                    $exists = Surveyed::where('respondent_id', $person->id)
                        ->whereHas('survey', function ($q) use ($survey) {
                            $q->where('proyect_id', $survey->proyect_id);
                        })
                        ->exists();
    
                    if ($exists) {
                        $validator->errors()->add('number_document', 'Esta persona ya ha sido encuestada en este proyecto.');
                    }
                }
            }
        });
    }

    public function messages()
    {
        return [
            'number_document.required' => 'El número de documento es obligatorio.',
            'number_document.string'   => 'El número de documento debe ser una cadena de texto.',
            'number_document.max'      => 'El número de documento no debe exceder los 20 caracteres.',
    
            'names.required' => 'El nombre es obligatorio.',
            'names.string'   => 'El nombre debe ser una cadena de texto.',
            'names.max'      => 'El nombre no debe exceder los 1000 caracteres.',
    
            'date_of_birth.date' => 'La fecha de nacimiento debe tener un formato válido.',
    
            'phone.string' => 'El teléfono debe ser una cadena de texto.',
            'phone.max'    => 'El teléfono no debe exceder los 255 caracteres.',
    
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.max'   => 'El correo electrónico no debe exceder los 255 caracteres.',
    
            'genero.string' => 'El género debe ser una cadena de texto.',
            'genero.max'    => 'El género no debe exceder los 255 caracteres.',
    
            'survey_id.required' => 'El campo survey_id es obligatorio.',
            'survey_id.integer'  => 'El campo survey_id debe ser un número entero.',
            'survey_id.exists'   => 'El survey_id no existe en la base de datos.',
    
            'responses.required' => 'Debe proporcionar al menos una respuesta.',
            'responses.array'    => 'Las respuestas deben enviarse como un arreglo.',
    
            'responses.*.survey_question_id.required' => 'La pregunta es obligatoria.',
            'responses.*.survey_question_id.integer'  => 'El ID de la pregunta debe ser un número entero.',
            'responses.*.survey_question_id.exists'   => 'La pregunta seleccionada no existe.',
    
            'responses.*.survey_question_option_id.array'      => 'Las opciones seleccionadas deben ser un arreglo.',
            'responses.*.survey_question_option_id.*.integer'  => 'El ID de la opción debe ser un número entero.',
            'responses.*.survey_question_option_id.*.exists'   => 'Una de las opciones seleccionadas no existe.',
    
            'responses.*.response_text.string' => 'La respuesta de texto debe ser una cadena.',
            'responses.*.response_text.max'    => 'La respuesta de texto no debe exceder los 1000 caracteres.',
        ];
    }
    
}
