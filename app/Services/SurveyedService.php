<?php
namespace App\Services;

use App\Models\Respondent;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;

class SurveyedService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }

    public function getSurveyedById(int $id): ?Surveyed
    {
        return Surveyed::find($id);
    }

    public function createSurveyed(array $data)
    {
        // 1. Buscar o crear al encuestado por su número de documento
        $person = Respondent::firstOrCreate(
            ['number_document' => $data['number_document']],
            [
                'names'         => isset($data['names']) ? $data['names'] : null,
                'date_of_birth' => isset($data['date_of_birth']) ? $data['date_of_birth'] : null,
                'phone'         => isset($data['phone']) ? $data['phone'] : null,
                'email'         => isset($data['email']) ? $data['email'] : null,
                'genero'        => isset($data['genero']) ? $data['genero'] : null,
            ]
        );

        // 2. Crear registro en Surveyed
        $surveyed = Surveyed::create([
            'respondent_id' => $person->id,
            'survey_id' => isset($data['survey_id']) ? $data['survey_id'] : null,
        ]);

        // 3. Registrar respuestas si existen
        if (isset($data['responses']) && is_array($data['responses'])) {
            foreach ($data['responses'] as $response) {
                if (! isset($response['survey_question_id'])) {
                    continue;
                }

                $question = SurveyQuestion::find($response['survey_question_id']);
                if (! $question) {
                    continue;
                }

                // Registro base de la respuesta
                $answer = SurveyedResponse::create([
                    'respondent_id'        => $person->id,
                    'surveyed_id'        => $surveyed->id,
                    'survey_id'          => isset($data['survey_id']) ? $data['survey_id'] : null,
                    'survey_question_id' => $question->id,
                    'response_text'      => ($question->question_type === 'LIBRE' && isset($response['response_text']))
                    ? $response['response_text']
                    : null,
                ]);

                // Si es tipo OPCIONES, asociar las opciones seleccionadas
                if ($question->question_type === 'OPCIONES' &&
                    isset($response['survey_question_option_id']) &&
                    
                    count($response['survey_question_option_id']) > 0) {

                    foreach ($response['survey_question_option_id'] as $optionId) {
                        SurveyedResponseOption::create([
                            'surveyed_response_id'          => $answer->id,
                            'survey_question_options_id' => $optionId,
                            'respondent_id' => $person->id,
                            'surveyed_id' => $surveyed->id,
                        ]);
                    }
                }
            }
        }

        return $surveyed;
    }

    public function updateSurveyed(Surveyed $proyect, array $data): Surveyed
    {
        $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return Surveyed::find($id)?->delete() ?? false;
    }

}
