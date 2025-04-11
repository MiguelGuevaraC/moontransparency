<?php
namespace App\Http\Controllers;

use App\Http\Requests\SurveyRequest\IndexSurveyRequest;
use App\Http\Requests\SurveyRequest\StoreSurveyRequest;
use App\Http\Requests\SurveyRequest\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Services\SurveyService;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/survey",
 *     summary="Obtener información de Surveys con filtros y ordenamiento",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="proyect_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="survey_name", in="query", description="Nombre de la encuesta", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="description", in="query", description="Descripción de la encuesta", required=false, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Lista de Surveys", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexSurveyRequest $request)
    {

        return $this->getFilteredResults(
            Survey::class,
            $request,
            Survey::filters,
            Survey::sorts,
            SurveyResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/survey/{id}",
 *     summary="Obtener detalles de un Survey por ID",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Survey", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Encuesta encontrado", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=404, description="Encuesta No Encontrada", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Encuesta No Encontrada")))
 * )
 */

    public function show($id)
    {

        $survey = $this->surveyService->getSurveyById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Encuesta No Encontrada',
            ], 404);
        }

        return new SurveyResource($survey);
    }

    public function show_web(Request $request, $id)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        $survey = $this->surveyService->getSurveyById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Encuesta No Encontrada',
            ], 404);
        }

        if ($survey->status != 'ACTIVA') {
            return response()->json([
                'error' => 'Esta Encuesta no se encuentra Activa',
            ], 404);
        }

        return new SurveyResource($survey);
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
 *             @OA\Schema(ref="#/components/schemas/SurveyRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreSurveyRequest $request)
    {
        $survey = $this->surveyService->createSurvey($request->validated());
        return new SurveyResource($survey);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/survey/{id}",
 *     summary="Actualizar un Survey",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/SurveyRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Encuesta actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Encuesta No Encontrada", @OA\JsonContent(@OA\Property(property="error", type="string", example="Encuesta No Encontrada"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateSurveyRequest $request, $id)
    {

        $validatedData = $request->validated();

        $survey = $this->surveyService->getSurveyById($id);
        if (! $survey) {
            return response()->json([
                'error' => 'Encuesta No Encontrada',
            ], 404);
        }

        $updatedCompany = $this->surveyService->updateSurvey($survey, $validatedData);
        return new SurveyResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/survey/{id}",
 *     summary="Eliminar un Survey por ID",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Encuesta eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Encuesta eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Encuesta No Encontrada"))),

 * )
 */

    public function destroy($id)
    {

        $survey = $this->surveyService->getSurveyById($id);

        if (! $survey) {
            return response()->json([
                'error' => 'Encuesta No Encontrada.',
            ], 404);
        }
        if ($survey->survey_questions()->exists()) {
            return response()->json([
                'error' => 'Esta encuesta tene preguntas relacionadas.',
            ], 422);
        }
        $survey = $this->surveyService->destroyById($id);

        return response()->json([
            'message' => 'Esta Encuesta es eliminada exitosamente',
        ], 200);
    }

}
