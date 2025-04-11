<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyedRequest\IndexSurveyedRequest;
use App\Http\Requests\SurveyedRequest\StoreSurveyedRequest;
use App\Http\Resources\SurveyedResource;
use App\Models\Surveyed;
use App\Services\SurveyedService;
use Illuminate\Http\Request;

class SurveyedController extends Controller
{
    protected $surveyService;

    public function __construct(SurveyedService $surveyService)
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

 public function index(IndexSurveyedRequest $request)
 {

     return $this->getFilteredResults(
         Surveyed::class,
         $request,
         Surveyed::filters,
         Surveyed::sorts,
         SurveyedResource::class
     );
 }

    /**
 * @OA\Post(
 *     path="/moontransparency/public/api/surveyed",
 *     summary="Crear Surveyed",
 *     tags={"Surveyed"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/SurveyRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Encuesta creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Surveyed")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
public function store(StoreSurveyedRequest $request)
{
    $survey = $this->surveyService->createSurveyed($request->validated());
    return new SurveyedResource($survey);
}


/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/surveyed/{id}",
 *     summary="Eliminar un Survey por ID",
 *     tags={"Surveyed"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
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
