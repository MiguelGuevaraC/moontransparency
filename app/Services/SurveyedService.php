<?php
namespace App\Services;

use App\Models\Respondent;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use Illuminate\Http\UploadedFile;

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
                'names' => $data['names'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'genero' => $data['genero'] ?? null,
            ]
        );

        // 2. Crear registro en Surveyed
        $surveyed = Surveyed::create([
            'respondent_id' => $person->id,
            'survey_id' => $data['survey_id'] ?? null,
        ]);

        // 3. Registrar respuestas si existen
        if (isset($data['responses']) && is_array($data['responses'])) {
            foreach ($data['responses'] as $index => $response) {
                if (!isset($response['survey_question_id'])) {
                    continue;
                }

                $question = SurveyQuestion::find($response['survey_question_id']);
                if (!$question) {
                    continue;
                }

                // Preparar payload base
                $answerData = [
                    'respondent_id' => $person->id,
                    'surveyed_id' => $surveyed->id,
                    'survey_id' => $data['survey_id'] ?? null,
                    'survey_question_id' => $question->id,
                    'response_text' => null,
                    'file_path' => null,
                ];

                // Si es tipo LIBRE -> texto
                if (isset($response['response_text'])) {
                    $answerData['response_text'] = $response['response_text'];
                }

                // Si es tipo FILE -> guardar archivo
                if ($question->question_type === 'FILE') {
                    // Si $response proviene de $request->validated(), puede contener UploadedFile
                    $uploadedFile = null;

                    if (isset($response['file']) && $response['file'] instanceof UploadedFile) {
                        $uploadedFile = $response['file'];
                    } else {
                        // fallback: si pasas $request->all() a servicio, usa Request::file
                        // pero aquí asumimos que $response['file'] viene como UploadedFile
                    }

                    if ($uploadedFile instanceof UploadedFile) {
                        // Guardar en disk public bajo carpeta por encuesta/encuestado
                        $subfolder = 'survey_files/';
                        $path = $uploadedFile->store($subfolder, 'public'); // storage/app/public/{path}
                        $answerData['file_path'] = $path;
                    }
                }

                // Crear la respuesta y luego las opciones si aplica
                $answer = SurveyedResponse::create($answerData);

                // Si es tipo OPCIONES, asociar las opciones seleccionadas
                if (
                    $question->question_type === 'OPCIONES' &&
                    isset($response['survey_question_option_id']) &&
                    is_array($response['survey_question_option_id']) &&
                    count($response['survey_question_option_id']) > 0
                ) {

                    foreach ($response['survey_question_option_id'] as $optionId) {
                        SurveyedResponseOption::create([
                            'surveyed_response_id' => $answer->id,
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
