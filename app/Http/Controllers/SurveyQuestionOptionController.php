<?php
namespace App\Http\Controllers;

use App\Http\Requests\SurveyQuestionOptionRequest\IndexSurveyQuestionOptionRequest;
use App\Http\Requests\SurveyQuestionOptionRequest\StoreSurveyQuestionOptionRequest;
use App\Http\Requests\SurveyQuestionOptionRequest\UpdateSurveyQuestionOptionRequest;
use App\Http\Resources\SurveyQuestionOptionResource;
use App\Models\SurveyQuestionOption;
use App\Services\SurveyQuestionOptionService;
use Illuminate\Http\Request;

class SurveyQuestionOptionController extends Controller
{
    protected $surveyQuestionoptionService;

    public function __construct(SurveyQuestionOptionService $surveyQuestionoptionService)
    {
        $this->surveyQuestionoptionService = $surveyQuestionoptionService;
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
 *     @OA\Response(response=200, description="Lista de SurveysQuestion", @OA\JsonContent(ref="#/components/schemas/SurveyQuestionOption")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexSurveyQuestionOptionRequest $request)
    {

        return $this->getFilteredResults(
            SurveyQuestionOption::class,
            $request,
            SurveyQuestionOption::filters,
            SurveyQuestionOption::sorts,
            SurveyQuestionOptionResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/surveyquestionoption/{id}",
 *     summary="Obtener detalles de un Survey por ID",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Survey", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Opción de Pregunta de Encuesta encontrado", @OA\JsonContent(ref="#/components/schemas/SurveyQuestionOption")),
 *     @OA\Response(response=404, description="Opción de Pregunta de Encuesta No Encontrada", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Opción de Pregunta de Encuesta No Encontrada")))
 * )
 */

    public function show($id)
    {

        $survey = $this->surveyQuestionoptionService->getSurveyQuestionOptionById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Opción de Pregunta de Encuesta No Encontrada',
            ], 404);
        }

        return new SurveyQuestionOptionResource($survey);
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
 *             @OA\Schema(ref="#/components/schemas/SurveyQuestionOptionRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Opción de Pregunta de Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/SurveyQuestionOption")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreSurveyQuestionOptionRequest $request)
    {
        $survey = $this->surveyQuestionoptionService->createSurveyQuestionOption($request->validated());
        return new SurveyQuestionOptionResource($survey);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/surveyquestionoption/{id}",
 *     summary="Actualizar un Survey",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/SurveyQuestionOptionRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Opción de Pregunta de Encuesta actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/SurveyQuestionOption")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Opción de Pregunta de Encuesta No Encontrada", @OA\JsonContent(@OA\Property(property="error", type="string", example="Opción de Pregunta de Encuesta No Encontrada"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateSurveyQuestionOptionRequest $request, $id)
    {

        $validatedData = $request->validated();

        $survey = $this->surveyQuestionoptionService->getSurveyQuestionOptionById($id);
        if (! $survey) {
            return response()->json([
                'error' => 'Opción de Pregunta de Encuesta No Encontrada',
            ], 404);
        }

        $updatedCompany = $this->surveyQuestionoptionService->updateSurveyQuestionOption($survey, $validatedData);
        return new SurveyQuestionOptionResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/surveyquestionoption/{id}",
 *     summary="Eliminar un Survey por ID",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Opción de Pregunta de Encuesta eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Opción de Pregunta de Encuesta eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Opción de Pregunta de Encuesta No Encontrada"))),

 * )
 */

    public function destroy($id)
    {

        $survey = $this->surveyQuestionoptionService->getSurveyQuestionOptionById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Opción de Pregunta de Encuesta No Encontrada.',
            ], 404);
        }
        $survey = $this->surveyQuestionoptionService->destroyById($id);

        return response()->json([
            'message' => 'Opción de Pregunta de Encuesta eliminado exitosamente',
        ], 200);
    }
}
