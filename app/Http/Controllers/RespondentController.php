<?php
namespace App\Http\Controllers;

use App\Http\Requests\RespondentRequest\IndexRespondentRequest;
use App\Http\Requests\RespondentRequest\StoreRespondentRequest;
use App\Http\Requests\RespondentRequest\UpdateRespondentRequest;
use App\Http\Resources\RespondentResource;
use App\Http\Resources\RespondentSearchResource;
use App\Models\Respondent;
use App\Services\RespondentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RespondentController extends Controller
{
    protected $respondentService;

    public function __construct(RespondentService $respondentService)
    {
        $this->respondentService = $respondentService;
    }


     public function index(IndexRespondentRequest $request)
    {

        return $this->getFilteredResults(
            Respondent::class,
            $request,
            Respondent::filters,
            Respondent::sorts,
            RespondentResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey",
     *     summary="Obtener información de Respondents con filtros y ordenamiento",
     *     tags={"Respondent"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="proyect_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="survey_name", in="query", description="Nombre de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="description", in="query", description="Descripción de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Lista de Respondents", @OA\JsonContent(ref="#/components/schemas/Respondent")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
     * )
     */

    public function index_search(IndexRespondentRequest $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        $dni = $request->input('number_document');

        // 1️⃣ Buscar en la base de datos
        $results = $this->getFilteredResults(
            Respondent::class,
            $request,
            Respondent::filters_search,
            Respondent::sorts,
            RespondentSearchResource::class,
            false // opcional: si getFilteredResults tiene flag de paginación o transformación
        );

        if (!empty($results) && count($results) > 0) {
            // Si se encuentra en DB, devolver resultados
            return response()->json([
                'success' => true,
                'message' => 'Información encontrada en la base de datos',
                'data' => $results
            ], 200);
        }

        // 2️⃣ Si no hay resultados, buscar en el servicio externo (RENIEC)
        try {
            $url = config('services.search_identity.dni.url');
            $token = config('services.search_identity.dni.token');

            $externalData = $this->callExternalApi($url, [
                'dni' => $dni,
                'fe' => 'N',
                'token' => $token
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Información encontrada en RENIEC',
                'data' => [
                    [
                        "id"=>null,
                    "number_document"=>$externalData['dni'],
                    "names"=>$externalData['nombres'] .' '.$externalData['apepat'] .' '.$externalData['apemat'],
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró información en la base de datos ni en RENIEC',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    
    /** Llamada a API externa */
    private function callExternalApi(string $url, array $params)
    {
        $response = Http::get($url, $params);
        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Error al comunicarse con el servicio externo');
    }



    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey/{id}",
     *     summary="Obtener detalles de un Respondent por ID",
     *     tags={"Respondent"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", description="ID del Respondent", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Encuestado encontrado", @OA\JsonContent(ref="#/components/schemas/Respondent")),
     *     @OA\Response(response=404, description="Encuestado No Encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Encuestado No Encontrado")))
     * )
     */

    public function show($id)
    {

        $survey = $this->respondentService->getRespondentById($id);

        if (!$survey) {
            return response()->json([
                'error' => 'Encuestado No Encontrado',
            ], 404);
        }

        return new RespondentResource($survey);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/survey",
     *     summary="Crear Respondent",
     *     tags={"Respondent"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/RespondentRequest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Encuestado creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Respondent")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     * )
     */
    public function store(StoreRespondentRequest $request)
    {
        $survey = $this->respondentService->createRespondent($request->validated());
        return new RespondentResource($survey);
    }

    /**
     * @OA\Put(
     *     path="/moontransparency/public/api/survey/{id}",
     *     summary="Actualizar un Respondent",
     *     tags={"Respondent"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/RespondentRequest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Encuestado actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Respondent")),
     *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
     *     @OA\Response(response=404, description="Encuestado No Encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Encuestado No Encontrado"))),
     *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
     * )
     */

    public function update(UpdateRespondentRequest $request, $id)
    {

        $validatedData = $request->validated();

        $survey = $this->respondentService->getRespondentById($id);
        if (!$survey) {
            return response()->json([
                'error' => 'Encuestado No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->respondentService->updateRespondent($survey, $validatedData);
        return new RespondentResource($updatedCompany);
    }

    /**
     * @OA\Delete(
     *     path="/moontransparency/public/api/survey/{id}",
     *     summary="Eliminar un Respondent por ID",
     *     tags={"Respondent"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *     @OA\Response(response=200, description="Encuestado eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Encuestado eliminado exitosamente"))),
     *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Encuestado No Encontrado"))),

     * )
     */

    public function destroy($id)
    {

        $survey = $this->respondentService->getRespondentById($id);

        if (!$survey) {
            return response()->json([
                'error' => 'Encuestado No Encontrado.',
            ], 404);
        }
        // if ($survey->survey_questions()->exists()) {
        //     return response()->json([
        //         'error' => 'Esta encuesta tene preguntas relacionadas.',
        //     ], 422);
        // }
        $survey = $this->respondentService->destroyById($id);

        return response()->json([
            'message' => 'Respondent eliminado exitosamente',
        ], 200);
    }
}
