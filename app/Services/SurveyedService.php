<?php
namespace App\Services;

use App\Models\Respondent;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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
            // 'file_path' se completará luego si hay archivo
        ]);

        // --- 2.1 Guardar el archivo (si viene) en la entidad Surveyed ---
        // Buscamos archivos pasados desde el Request (los mandamos en $validated['_files'] desde el controlador)
        $files = $data['_files'] ?? [];

        // 3 formas posibles de subir el archivo:
        //  - archivo general: $files['file']
        //  - archivo por respuesta: $files['responses'][<index>]['file']
        //  - archivo top-level en $data['file'] (si lo incluyes manualmente)
        $uploadedFile = null;

        // 1) Si tienes un campo global 'file'
        if (isset($files['file']) && $files['file'] instanceof UploadedFile) {
            $uploadedFile = $files['file'];
        }

        // 2) Buscar archivos dentro de responses (tomamos el primer archivo encontrado)
        if (!$uploadedFile && isset($files['responses']) && is_array($files['responses'])) {
            foreach ($files['responses'] as $idx => $respFiles) {
                if (isset($respFiles['file']) && $respFiles['file'] instanceof UploadedFile) {
                    $uploadedFile = $respFiles['file'];
                    break;
                }
                // Soporta también respuestas que vienen como arrays numerados: e.g. $files['responses'][0]['file']
            }
        }

        // 3) Fallback: si el cliente dejó un UploadedFile dentro de $data['responses'][i]['file']
        if (!$uploadedFile && isset($data['responses']) && is_array($data['responses'])) {
            foreach ($data['responses'] as $idx => $resp) {
                if (isset($resp['file']) && $resp['file'] instanceof UploadedFile) {
                    $uploadedFile = $resp['file'];
                    break;
                }
            }
        }

        // Si encontramos archivo, lo guardamos bajo storage/app/public/survey_files/{surveyed_id}/
        if ($uploadedFile instanceof UploadedFile) {
            // carpeta por surveyed id
            $folder = 'survey_files/' . $surveyed->id;

            // nombre seguro único
            $filename = Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME))
                . '-' . time() . '.' . $uploadedFile->getClientOriginalExtension();

            // store en disk 'public' -> storage/app/public/{folder}/{filename}
            $path = $uploadedFile->storeAs($folder, $filename, 'public');

            // actualizar surveyed con la ruta relativa
            $surveyed->file_path = $path; // ej: 'survey_files/12/documento.pdf'
            $surveyed->save();
        }

        // 4. Registrar respuestas si existen (igual que antes, pero *sin* guardar el archivo en surveyed_responses)
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
                    // NOTA: NO guardamos file_path aquí porque el archivo va en la tabla surveyed
                ];

                // Si es tipo LIBRE -> texto
                if ($question->question_type === 'LIBRE' && isset($response['response_text'])) {
                    $answerData['response_text'] = $response['response_text'];
                }

                // Crear la respuesta
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
