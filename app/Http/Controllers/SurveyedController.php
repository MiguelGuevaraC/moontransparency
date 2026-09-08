<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyedRequest\IndexSurveyedRequest;
use App\Http\Requests\SurveyedRequest\ReopenSurveyedRequest;
use App\Http\Requests\SurveyedRequest\StoreSurveyedRequest;
use App\Http\Requests\SurveyedRequest\UpdateSurveyedRequest;
use App\Http\Resources\CalculatorParticipationResource;
use App\Http\Resources\SurveyedResource;
use App\Models\Surveyed;
use App\Services\SurveyedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class SurveyedController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyedService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/surveyed",
     *     summary="Obtener el historial de participaciones con filtros y ordenamiento",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="proyect_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="survey_name", in="query", description="Nombre de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="description", in="query", description="Descripción de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="household_code", in="query", description="Código global del hogar", required=false, @OA\Schema(type="string", example="HOG-00000001")),
     *
     *     @OA\Response(response=200, description="Lista de Surveys", @OA\JsonContent(ref="#/components/schemas/Survey")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
     * )
     */
    public function index(IndexSurveyedRequest $request)
    {
        $query = Surveyed::query()->with([
            'respondent',
            'household',
            'createdBy.rol',
            'updatedBy.rol',
            'survey.proyect',
            'surveyed_responses.survey_question.survey_questions_options',
            'surveyed_responses.surveyed_responses_options.survey_question_options',
            'surveyed_responses.measurement',
            'measurements.surveyed_responses.survey_question.survey_questions_options',
            'measurements.surveyed_responses.surveyed_responses_options.survey_question_options',
            'measurements.surveyed_responses.measurement',
        ]);

        if ($request->filled('respondent')) {
            $respondent = $request->query('respondent');
            $query->whereHas('respondent', function ($respondentQuery) use ($respondent) {
                $respondentQuery
                    ->where('names', 'like', "%$respondent%")
                    ->orWhere('number_document', 'like', "%$respondent%");
            });
        }

        if ($request->filled('respondent_name')) {
            $respondentName = $request->query('respondent_name');
            $query->whereHas('respondent', function ($respondentQuery) use ($respondentName) {
                $respondentQuery->where('names', 'like', "%$respondentName%");
            });
        }

        if ($request->filled('number_document')) {
            $numberDocument = $request->query('number_document');
            $query->whereHas('respondent', function ($respondentQuery) use ($numberDocument) {
                $respondentQuery->where('number_document', 'like', "%$numberDocument%");
            });
        }

        if ($request->filled('household_code')) {
            $householdCode = $request->query('household_code');
            $query->whereHas('household', function ($householdQuery) use ($householdCode) {
                $householdQuery->where('code', $householdCode);
            });
        }

        if ($request->filled('project_id')) {
            $projectId = $request->integer('project_id');
            $query->whereHas('survey', function ($surveyQuery) use ($projectId) {
                $surveyQuery->where('proyect_id', $projectId);
            });
        }

        if ($request->filled('response_text')) {
            $responseText = $request->query('response_text');
            $query->whereHas('surveyed_responses', function ($responseQuery) use ($responseText) {
                $responseQuery->whereRaw('UPPER(response_text) LIKE UPPER(?)', ["%$responseText%"]);
            });
        }
        if ($request->filled('response_text_lena')) {
            $firewoodResponse = $request->query('response_text_lena');
            $query->whereHas('surveyed_responses', function ($responseQuery) use ($firewoodResponse) {
                $responseQuery
                    ->whereRaw('UPPER(response_text) LIKE UPPER(?)', ["%$firewoodResponse%"])
                    ->whereHas('survey_question', function ($questionQuery) {
                        $questionQuery->whereRaw('UPPER(question_text) LIKE UPPER(?)', ['%Tipo de Le%']);
                    });
            });
        }

        return $this->getFilteredResults(
            $query,
            $request,
            Surveyed::filters,
            Surveyed::sorts,
            SurveyedResource::class
        );
    }

    public function indexAll(IndexSurveyedRequest $request)
    {
        $query = Surveyed::query()
                ->join('respondents', 'respondents.id', '=', 'surveyeds.respondent_id')
                ->join('surveys', 'surveys.id', '=', 'surveyeds.survey_id')
                ->join('proyects', 'proyects.id', '=', 'surveys.proyect_id')
                ->join('surveyed_responses', 'surveyed_responses.surveyed_id', '=', 'surveyeds.id')
                ->join('survey_questions', 'survey_questions.id', '=', 'surveyed_responses.survey_question_id')
                ->leftJoin('surveyed_response_options', function ($join) {
                    $join->on('surveyed_response_options.surveyed_response_id', '=', 'surveyed_responses.id')
                        ->whereNull('surveyed_response_options.deleted_at');
                })
                ->leftJoin('survey_question_options', function ($join) {
                    $join->on('survey_question_options.id', '=', 'surveyed_response_options.survey_question_options_id')
                        ->whereNull('survey_question_options.deleted_at');
                })
                ->whereNull('respondents.deleted_at')
                ->whereNull('surveys.deleted_at')
                ->whereNull('proyects.deleted_at')
                ->whereNull('surveyed_responses.deleted_at')
                ->whereNull('survey_questions.deleted_at');

        if ($request->filled('response_text')) {
            $query = $query->whereHas('surveyed_responses', function ($q) use ($request) {
                $q->where(DB::raw('upper(response_text)'), 'like', DB::raw("upper('%{$request->input('response_text')}%')"));
            });
        }

        $query->select('surveyeds.id', 'surveyeds.id as surveyed_id', 'surveyeds.respondent_id', 'surveyeds.status', 'surveyeds.completed_at', 'surveyeds.latitude', 'surveyeds.longitude', 'respondents.names as respondent_name', 'proyects.name as proyect_name', 'surveys.id as survey_id',
            'surveyed_responses.id as response_id', 'surveyed_responses.survey_question_id', 'survey_questions.question_text as survey_question_text', 'survey_questions.question_type as survey_question_type',
            'surveyed_responses.response_text', 'survey_question_options.description as selected_option_description', 'surveyed_responses.file_path', 'surveyed_responses.created_at as response_created_at',
            'surveyeds.created_at as surveyed_created_at', 'surveyeds.created_at as loaded_at', 'respondents.genero as respondent_gender', 'surveys.survey_name', 'surveyed_response_options.id as id2')
                ->orderBy('surveyeds.id');

        return response()->json($query->get());
    }

    public function indexAllExcel(IndexSurveyedRequest $request)
    {
        ini_set('max_execution_time', '9000');

        $query = Surveyed::query()
                ->join('respondents', 'respondents.id', '=', 'surveyeds.respondent_id')
                ->join('surveys', 'surveys.id', '=', 'surveyeds.survey_id')
                ->join('proyects', 'proyects.id', '=', 'surveys.proyect_id')
                ->join('surveyed_responses', 'surveyed_responses.surveyed_id', '=', 'surveyeds.id')
                ->join('survey_questions', 'survey_questions.id', '=', 'surveyed_responses.survey_question_id')
                ->leftJoin('surveyed_response_options', function ($join) {
                    $join->on('surveyed_response_options.surveyed_response_id', '=', 'surveyed_responses.id')
                        ->whereNull('surveyed_response_options.deleted_at');
                })
                ->leftJoin('survey_question_options', function ($join) {
                    $join->on('survey_question_options.id', '=', 'surveyed_response_options.survey_question_options_id')
                        ->whereNull('survey_question_options.deleted_at');
                })
                ->whereNull('respondents.deleted_at')
                ->whereNull('surveys.deleted_at')
                ->whereNull('proyects.deleted_at')
                ->whereNull('surveyed_responses.deleted_at')
                ->whereNull('survey_questions.deleted_at');

        if ($request->filled('response_text')) {
            $query = $query->whereHas('surveyed_responses', function ($q) use ($request) {
                $q->where(DB::raw('upper(response_text)'), 'like', DB::raw("upper('%{$request->input('response_text')}%')"));
            });
        }

        $query->select('surveyeds.id', 'surveyeds.id as surveyed_id', 'surveyeds.respondent_id', 'respondents.names as respondent_name', 'proyects.name as proyect_name', 'surveys.id as survey_id',
            'surveyed_responses.id as response_id', 'surveyed_responses.survey_question_id', 'survey_questions.question_text as survey_question_text', 'survey_questions.question_type as survey_question_type',
            'surveyed_responses.response_text', 'survey_question_options.description as selected_option_description', 'surveyed_responses.file_path', 'surveyed_responses.created_at as response_created_at',
            'surveyeds.created_at as surveyed_created_at', 'surveyeds.created_at as loaded_at', 'respondents.genero as respondent_gender', 'surveys.survey_name', 'surveyed_response_options.id as id2')
                ->orderBy('surveyeds.id');

        $rows = $query->get();

        $excel = "<table border='1'>";
        $excel .= '<thead><tr>';
        $excel .= '<th>ID</th>';
        $excel .= '<th>Proyecto</th>';
        $excel .= '<th>Encuesta</th>';
        $excel .= '<th>Fecha Respuesta</th>';
        $excel .= '<th>Genero Encuestado</th>';
        $excel .= '<th>Encuestado</th>';
        $excel .= '<th>Pregunta</th>';
        $excel .= '<th>Tipo Pregunta</th>';
        $excel .= '<th>Respuesta</th>';
        $excel .= '<th>Opción Seleccionada</th>';
        $excel .= '<th>ID Opción Seleccionada</th>';
        $excel .= '<th>Archivo</th>';
        $excel .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $excel .= '<tr>';
            $excel .= '<td>'.($row->response_id ?? '').'</td>';
            $excel .= '<td>'.($row->proyect_name ?? '').'</td>';
            $excel .= '<td>'.($row->survey_name ?? '').'</td>';
            $excel .= '<td>'.($row->loaded_at ?? '').'</td>';
            $excel .= '<td>'.($row->respondent_gender ?? '').'</td>';
            $excel .= '<td>'.($row->respondent_name ?? '').'</td>';
            $excel .= '<td>'.($row->survey_question_text ?? '').'</td>';
            $excel .= '<td>'.($row->survey_question_type ?? '').'</td>';
            $excel .= '<td>'.($row->response_text ?? '').'</td>';
            $excel .= '<td>'.($row->selected_option_description ?? '').'</td>';
            $excel .= '<td>'.($row->id2 ?? '').'</td>';
            $excel .= '<td>'.($row->file_path ?? '').'</td>';
            $excel .= '</tr>';
        }

        $excel .= '</tbody></table>';

        /*header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=encuestas_respuestas.xls");
        echo utf8_decode($excel);
        exit;*/
        return response(utf8_decode($excel), 200)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename=encuestas_respuestas.xls');
    }

    public function importExcel(Request $request)
    {
        ini_set('max_execution_time', 9000);
        ini_set('memory_limit', '-1');

        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        DB::beginTransaction();

        try {
            $rows = Excel::toArray([], $request->file('file'));
            $rows = $rows[0];

            if (count($rows) <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no contiene registros.',
                ], 422);
            }

            // Validar encabezado
            $header = array_map('trim', $rows[0]);

            if ($header[0] != 'ID' || $header[8] != 'Respuesta') {
                return response()->json([
                    'success' => false,
                    'message' => 'El formato del Excel no es válido.',
                ], 422);
            }

            $chunk = 50;

            $datos = [];
            $datos2 = [];

            foreach ($rows as $i => $row) {
                if ($i == 0) {
                    continue;
                }

                $id = trim($row[0]);

                if ($id == '') {
                    continue;
                }
                if ($row[8] != '') {
                    $datos[] = [
                        'id' => $id,
                        'respuesta' => $row[8] ?? '',
                    ];
                }
                if (trim($row[7]) == 'OPCIONES') {
                    $datos2[] = [
                        'id' => $id,
                        'option' => $row[9] ?? '',
                        'id_option' => $row[10],
                    ];
                }
            }

            foreach (array_chunk($datos, $chunk) as $grupo) {
                $cases = [];
                $ids = [];

                foreach ($grupo as $item) {
                    $respuesta = str_replace("'", "\\'", $item['respuesta']);

                    $cases[] = "WHEN {$item['id']} THEN '{$respuesta}'";
                    $ids[] = $item['id'];
                }

                $sql = '
                    UPDATE surveyed_responses
                    SET
                        response_text = CASE id
                            '.implode("\n", $cases).'
                        END,
                        updated_at = NOW()
                    WHERE id IN ('.implode(',', $ids).')
                ';

                DB::statement($sql);
            }

            $surveyQuestions = DB::table('surveyed_responses')
                                ->whereIn('id', collect($datos2)->pluck('id'))
                                ->pluck('survey_question_id', 'id');

            $options = DB::table('survey_question_options')
                        ->get()
                        ->keyBy(function ($item) {
                            return $item->survey_question_id.'|'.mb_strtolower(trim($item->description));
                        });

            foreach (array_chunk($datos2, 100) as $grupo) {
                $cases = [];
                $ids = [];

                foreach ($grupo as $item) {
                    if (! isset($surveyQuestions[$item['id']])) {
                        continue;
                    }

                    $key = $surveyQuestions[$item['id']].'|'.mb_strtolower(trim($item['option']));

                    if (! isset($options[$key])) {
                        continue;
                    }

                    $cases[] = "WHEN {$item['id_option']} THEN {$options[$key]->id}";
                    $ids[] = $item['id_option'];
                }

                if (count($ids)) {
                    DB::statement('
                        UPDATE surveyed_response_options
                        SET
                            survey_question_options_id = CASE id
                                '.implode("\n", $cases).'
                            END,
                            updated_at = NOW()
                        WHERE id IN ('.implode(',', $ids).')
                    ');
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizados '.count($ids),
                'actualizados' => count($ids),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/response-survey",
     *     summary="Crear Surveyed",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(ref="#/components/schemas/SurveyRequest")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Surveyed")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     * )
     */
    public function store(StoreSurveyedRequest $request)
    {
        // validado para los campos textuales
        $validated = $request->validated();

        // agregamos todos los archivos (mantienen la misma estructura anidada que envía el cliente)
        $validated['_files'] = $request->allFiles();

        $surveyed = $this->surveyService->createSurveyed($validated);

        return new SurveyedResource($surveyed);
    }

    public function show($id)
    {
        $surveyed = $this->surveyService->getSurveyedById((int) $id);

        if (! $surveyed) {
            return response()->json([
                'message' => 'Respuesta de encuesta no encontrada.',
            ], 404);
        }

        return new SurveyedResource($surveyed);
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/surveyed/{id}/calculator",
     *     summary="Obtener datos consolidados de una participación para la calculadora de CO2",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Participación consolidada en un contrato estable de siete días",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", ref="#/components/schemas/CalculatorParticipation")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Participación no encontrada")
     * )
     */
    public function calculator($id)
    {
        $surveyed = $this->surveyService->getSurveyedById((int) $id);

        if (! $surveyed) {
            return response()->json([
                'message' => 'Respuesta de encuesta no encontrada.',
            ], 404);
        }

        return new CalculatorParticipationResource($surveyed);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/response-survey/{id}",
     *     summary="Actualizar Surveyed",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(ref="#/components/schemas/SurveyRequest")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Surveyed")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     * )
     */
    public function update(UpdateSurveyedRequest $request, $id)
    {
        //Log::info('Request data', $request->all());
        // validado para los campos textuales
        $validated = $request->validated();

        // agregamos todos los archivos (mantienen la misma estructura anidada que envía el cliente)
        $validated['_files'] = $request->allFiles();

        $surveyed = $this->surveyService->updateSurveyedById((int) $id, $validated);

        if (! $surveyed) {
            return response()->json([
                'message' => 'Respuesta de encuesta no encontrada.',
            ], 404);
        }

        return new SurveyedResource($surveyed);
    }

    public function finalize(UpdateSurveyedRequest $request, $id)
    {
        $validated = $request->validated();
        $validated['_files'] = $request->allFiles();

        $surveyed = $this->surveyService->finalizeSurveyedById((int) $id, $validated);

        if (! $surveyed) {
            return response()->json([
                'message' => 'Respuesta de encuesta no encontrada.',
            ], 404);
        }

        return new SurveyedResource($surveyed);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/surveyed/{id}/reopen",
     *     summary="Reabrir una participación finalizada",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"reason"},
     *
     *             @OA\Property(property="reason", type="string", maxLength=1000, example="Se requiere corregir la medición del día 4")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Participación reabierta como BORRADOR"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso o sin rol administrador"),
     *     @OA\Response(response=404, description="Participación no encontrada"),
     *     @OA\Response(response=409, description="La participación no está finalizada"),
     *     @OA\Response(response=422, description="Motivo inválido")
     * )
     */
    public function reopen(ReopenSurveyedRequest $request, $id)
    {
        $actor = $request->user();

        if (! $actor || ! $actor->isAdministrator()) {
            return response()->json([
                'message' => 'Solo un administrador puede reabrir una encuesta finalizada.',
            ], 403);
        }

        $surveyed = $this->surveyService->reopenSurveyedById(
            (int) $id,
            $request->validated('reason'),
            $actor
        );

        if (! $surveyed) {
            return response()->json([
                'message' => 'Respuesta de encuesta no encontrada.',
            ], 404);
        }

        return new SurveyedResource($surveyed);
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/surveyed/{id}",
     *     summary="Eliminar un Survey por ID",
     *     tags={"Surveyed"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Encuesta eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Encuesta eliminado exitosamente"))),
     *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Encuesta No Encontrada"))),

     * )
     */
    public function destroy($id)
    {
        $survey = $this->surveyService->getSurveyedById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Respuesta Encuesta No Encontrada.',
            ], 404);
        }

        $survey = $this->surveyService->destroyById($id);

        return response()->json([
            'message' => 'Esta respuesta de encuesta eliminada exitosamente',
        ], 200);
    }
}
