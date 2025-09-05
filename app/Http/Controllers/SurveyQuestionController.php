<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyQuestionRequest\IndexSurveyQuestionRequest;
use App\Http\Requests\SurveyQuestionRequest\StoreSurveyQuestionRequest;
use App\Http\Requests\SurveyQuestionRequest\UpdateSurveyQuestionRequest;
use App\Http\Resources\SurveyQuestionResource;
use App\Models\SurveyQuestion;
use App\Services\SurveyQuestionService;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    protected $surveyQuestionService;

    public function __construct(SurveyQuestionService $surveyQuestionService)
    {
        $this->surveyQuestionService = $surveyQuestionService;
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey",
     *     summary="Obtener información de SurveysQuestion con filtros y ordenamiento",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="proyect_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="survey_name", in="query", description="Nombre de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="description", in="query", description="Descripción de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Lista de SurveysQuestion", @OA\JsonContent(ref="#/components/schemas/SurveyQuestion")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
     * )
     */

    public function index(IndexSurveyQuestionRequest $request)
    {
        // base query
        $query = SurveyQuestion::query();

        // aplicar filtro antes de pasar al getFilteredResults
        if ($request->filled('is_has_options')) {
            $val = filter_var($request->get('is_has_options'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (!is_null($val)) {
                if ($val) {
                    $query->whereHas('survey_questions_options');
                } else {
                    $query->whereDoesntHave('survey_questions_options');
                }
            }
        }

        // ahora pasas el query en vez de la clase
        return $this->getFilteredResults(
            $query,
            $request,
            SurveyQuestion::filters,
            SurveyQuestion::sorts,
            SurveyQuestionResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/surveyquestion/{id}",
     *     summary="Obtener detalles de un Survey por ID",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID del Survey", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Pregunta Encuesta encontrado", @OA\JsonContent(ref="#/components/schemas/SurveyQuestion")),
     *     @OA\Response(response=404, description="Pregunta Encuesta No Encontrada", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Pregunta Encuesta No Encontrada")))
     * )
     */

    public function show($id)
    {

        $survey = $this->surveyQuestionService->getSurveyQuestionById($id);

        if (!$survey) {
            return response()->json([
                'error' => 'Pregunta Encuesta No Encontrada',
            ], 404);
        }

        return new SurveyQuestionResource($survey);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/survey",
     *     summary="Crear Survey",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/SurveyQuestionRequest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pregunta Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/SurveyQuestion")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     * )
     */
    public function store(StoreSurveyQuestionRequest $request)
    {
        $survey = $this->surveyQuestionService->createSurveyQuestion($request->validated());
        return new SurveyQuestionResource($survey);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/surveyquestion/{id}",
     *     summary="Actualizar un Survey",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/SurveyQuestionRequest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pregunta Encuesta actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/SurveyQuestion")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     *     @OA\Response(response=404, description="Pregunta Encuesta No Encontrada", @OA\JsonContent(@OA\Property(property="error", type="string", example="Pregunta Encuesta No Encontrada"))),
     *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
     * )
     */

    public function update(UpdateSurveyQuestionRequest $request, $id)
    {

        $validatedData = $request->validated();

        $survey = $this->surveyQuestionService->getSurveyQuestionById($id);
        if (!$survey) {
            return response()->json([
                'error' => 'Pregunta Encuesta No Encontrada',
            ], 404);
        }

        $updatedCompany = $this->surveyQuestionService->updateSurveyQuestion($survey, $validatedData);
        return new SurveyQuestionResource($updatedCompany);
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/surveyquestion/{id}",
     *     summary="Eliminar un Survey por ID",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Pregunta Encuesta eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Pregunta Encuesta eliminado exitosamente"))),
     *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Pregunta Encuesta No Encontrada"))),

     * )
     */

    public function destroy($id)
    {

        $surveyquestion = $this->surveyQuestionService->getSurveyQuestionById($id);

        if (!$surveyquestion) {
            return response()->json([
                'error' => 'Pregunta Encuesta No Encontrada.',
            ], 404);
        }
        if ($surveyquestion->survey_questions_options()->exists()) {
            return response()->json([
                'error' => 'Esta pregunta tiene opciones relacionadas.',
            ], 422);
        }

        $surveyquestion = $this->surveyQuestionService->destroyById($id);

        return response()->json([
            'message' => 'Pregunta Encuesta eliminado exitosamente',
        ], 200);
    }
}
