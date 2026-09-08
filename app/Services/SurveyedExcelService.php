<?php

namespace App\Services;

use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestionOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SurveyedExcelService
{
    public function exportRows(?string $responseText = null)
    {
        return DB::table('surveyeds')
            ->join('respondents', 'respondents.id', '=', 'surveyeds.respondent_id')
            ->join('surveys', 'surveys.id', '=', 'surveyeds.survey_id')
            ->join('proyects', 'proyects.id', '=', 'surveys.proyect_id')
            ->join('surveyed_responses', 'surveyed_responses.surveyed_id', '=', 'surveyeds.id')
            ->join('survey_questions', 'survey_questions.id', '=', 'surveyed_responses.survey_question_id')
            ->leftJoin('surveyed_measurements', 'surveyed_measurements.id', '=', 'surveyed_responses.surveyed_measurement_id')
            ->leftJoin('households', 'households.id', '=', 'surveyeds.household_id')
            ->leftJoin('surveyed_response_options', function ($join) {
                $join->on('surveyed_response_options.surveyed_response_id', '=', 'surveyed_responses.id')
                    ->whereNull('surveyed_response_options.deleted_at');
            })
            ->leftJoin('survey_question_options', function ($join) {
                $join->on('survey_question_options.id', '=', 'surveyed_response_options.survey_question_options_id')
                    ->whereNull('survey_question_options.deleted_at');
            })
            ->whereNull('surveyeds.deleted_at')
            ->whereNull('respondents.deleted_at')
            ->whereNull('surveys.deleted_at')
            ->whereNull('proyects.deleted_at')
            ->whereNull('surveyed_responses.deleted_at')
            ->whereNull('survey_questions.deleted_at')
            ->when($responseText, fn ($query, $value) => $query->whereRaw(
                'UPPER(surveyed_responses.response_text) LIKE UPPER(?)',
                ['%'.$value.'%']
            ))
            ->select([
                'surveyed_responses.id as response_id', 'proyects.name as project_name',
                'surveys.survey_name', 'surveyeds.created_at as answered_at',
                'respondents.genero as respondent_gender', 'respondents.names as respondent_name',
                'survey_questions.question_text', 'survey_questions.question_type',
                'surveyed_responses.response_text', 'survey_question_options.description as selected_option',
                'surveyed_response_options.id as selected_option_id', 'surveyed_responses.file_path',
                'surveyeds.id as participation_id', 'surveyeds.status', 'surveyeds.completed_at',
                'surveyed_measurements.day_number', 'surveyeds.latitude', 'surveyeds.longitude',
                'households.code as household_code',
            ])
            ->orderBy('surveyeds.id')
            ->orderBy('surveyed_measurements.day_number')
            ->orderBy('surveyed_responses.id')
            ->get();
    }

    public function toHtml($rows): string
    {
        $headers = [
            'ID', 'Proyecto', 'Encuesta', 'Fecha Respuesta', 'Genero Encuestado', 'Encuestado',
            'Pregunta', 'Tipo Pregunta', 'Respuesta', 'Opción Seleccionada',
            'ID Opción Seleccionada', 'Archivo', 'Participación ID', 'Estado', 'Fecha cierre',
            'Día', 'Latitud', 'Longitud', 'Código hogar',
        ];
        $columns = [
            'response_id', 'project_name', 'survey_name', 'answered_at', 'respondent_gender',
            'respondent_name', 'question_text', 'question_type', 'response_text', 'selected_option',
            'selected_option_id', 'file_path', 'participation_id', 'status', 'completed_at',
            'day_number', 'latitude', 'longitude', 'household_code',
        ];

        $html = '<table border="1"><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.$this->escapeCell($header).'</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $html .= '<td>'.$this->escapeCell($row->{$column} ?? '').'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    public function import(UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0] ?? [];
        if (count($rows) <= 1) {
            throw ValidationException::withMessages(['file' => 'El archivo no contiene registros.']);
        }

        $indexes = $this->headerIndexes($rows[0]);
        if (! isset($indexes['id'], $indexes['respuesta'])) {
            throw ValidationException::withMessages([
                'file' => 'El formato del Excel no es válido. Faltan las columnas ID o Respuesta.',
            ]);
        }

        return DB::transaction(function () use ($rows, $indexes) {
            $responseUpdates = [];
            $optionUpdates = [];

            foreach (array_slice($rows, 1) as $offset => $row) {
                $id = trim((string) ($row[$indexes['id']] ?? ''));
                if ($id === '') {
                    continue;
                }
                if (! ctype_digit($id)) {
                    throw ValidationException::withMessages([
                        'file' => 'El ID de la fila '.($offset + 2).' debe ser un número entero.',
                    ]);
                }

                $responseUpdates[(int) $id] = $row[$indexes['respuesta']] ?? null;
                $selectionId = $this->cell($row, $indexes, 'id opcion seleccionada');
                $selectionText = $this->cell($row, $indexes, 'opcion seleccionada');
                if ($selectionId === '' && $selectionText === '') {
                    continue;
                }
                if (! ctype_digit($selectionId)) {
                    throw ValidationException::withMessages([
                        'file' => 'El ID de opción de la fila '.($offset + 2).' debe ser un número entero.',
                    ]);
                }
                $optionUpdates[(int) $selectionId] = [
                    'response_id' => (int) $id,
                    'description' => $selectionText,
                ];
            }

            $responses = SurveyedResponse::query()->whereKey(array_keys($responseUpdates))->get()->keyBy('id');
            if ($responses->count() !== count($responseUpdates)) {
                throw ValidationException::withMessages(['file' => 'El archivo contiene IDs de respuesta inexistentes.']);
            }
            foreach ($responseUpdates as $id => $value) {
                $responses[$id]->update(['response_text' => $value]);
            }

            $selections = SurveyedResponseOption::query()->whereKey(array_keys($optionUpdates))->get()->keyBy('id');
            foreach ($optionUpdates as $selectionId => $update) {
                $selection = $selections->get($selectionId);
                if (! $selection || (int) $selection->surveyed_response_id !== $update['response_id']) {
                    throw ValidationException::withMessages([
                        'file' => 'El archivo contiene una selección que no pertenece a la respuesta indicada.',
                    ]);
                }

                $questionId = $responses[$update['response_id']]->survey_question_id;
                $option = SurveyQuestionOption::query()
                    ->where('survey_question_id', $questionId)
                    ->whereRaw('LOWER(TRIM(description)) = ?', [Str::lower($update['description'])])
                    ->first();
                if (! $option) {
                    throw ValidationException::withMessages([
                        'file' => 'Una opción seleccionada no pertenece a la pregunta indicada.',
                    ]);
                }
                $selection->update(['survey_question_options_id' => $option->id]);
            }

            return [
                'responses_updated' => count($responseUpdates),
                'options_updated' => count($optionUpdates),
            ];
        });
    }

    private function headerIndexes(array $header): array
    {
        return collect($header)->mapWithKeys(function ($value, $index) {
            $normalized = Str::of((string) $value)->ascii()->lower()->squish()->toString();

            return [$normalized => $index];
        })->all();
    }

    private function cell(array $row, array $indexes, string $name): string
    {
        return isset($indexes[$name]) ? trim((string) ($row[$indexes[$name]] ?? '')) : '';
    }

    private function escapeCell($value): string
    {
        $value = (string) ($value ?? '');
        if (preg_match('/^[=+\-@]/', $value) && ! is_numeric($value)) {
            $value = "'".$value;
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
