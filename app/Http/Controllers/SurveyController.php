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
 *     @OA\Parameter(name="project_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
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
 *     @OA\Response(response=200, description="Donación encontrado", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Donación no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->surveyService->getSurveyById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        return new SurveyResource($rol);
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
 *     @OA\Response(response=200, description="Donación creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="La validación falló.")))
 * )
 */
    public function store(StoreSurveyRequest $request)
    {
        $rol = $this->surveyService->createSurvey($request->validated());
        return new SurveyResource($rol);
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
 *     @OA\Response(response=200, description="Donación actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Survey")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Datos inválidos"))),
 *     @OA\Response(response=404, description="Donación no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateSurveyRequest $request, $id)
    {

        $validatedData = $request->validated();

        if ($id == 1) {
            return response()->json([
                'message' => 'Esta Donación No puede ser Editado',
            ], 422);
        }
        $rol = $this->surveyService->getSurveyById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->surveyService->updateSurvey($rol, $validatedData);
        return new SurveyResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/survey/{id}",
 *     summary="Eliminar un Survey por ID",
 *     tags={"Survey"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Donación eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $proyect = $this->surveyService->getSurveyById($id);

        if (! $proyect) {
            return response()->json([
                'error' => 'Donación No Encontrado.',
            ], 404);
        }
        $proyect = $this->surveyService->destroyById($id);

        return response()->json([
            'message' => 'Survey eliminado exitosamente',
        ], 200);
    }

}
