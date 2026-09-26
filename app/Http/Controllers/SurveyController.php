<?php

namespace App\Http\Controllers;

use App\Http\Requests\SurveyRequest\IndexSurveyRequest;
use App\Http\Requests\SurveyRequest\PublicSurveyIndexRequest;
use App\Http\Requests\SurveyRequest\StoreSurveyRequest;
use App\Http\Requests\SurveyRequest\UpdateSurveyRequest;
use App\Http\Resources\PublicSurveyResource;
use App\Http\Resources\SurveyChangeLogResource;
use App\Http\Resources\SurveyPreviewResource;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyChangeLog;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

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
     *
     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="proyect_id", in="query", description="ID del proyecto", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="survey_name", in="query", description="Nombre de la encuesta", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="description", in="query", description="Descripción de la encuesta", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Lista de Surveys", @OA\JsonContent(ref="#/components/schemas/Survey")),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(@OA\Property(property="error", type="string")))
     * )
     */
    public function index(IndexSurveyRequest $request)
    {
        $query = Survey::query();

        if ($request->filled('is_post_survey_id')) {
            $val = (int) $request->input('is_post_survey_id');
            if ($val === 1) {
                $query->has('postSurvey'); // equivalente a whereNotNull('post_survey_id') + existencia de relación
            } elseif ($val === 0) {
                $query->doesntHave('postSurvey'); // equivalente a whereNull('post_survey_id')
            }
        }
        $query
            ->orderByRaw('display_order IS NULL')
            ->orderBy('display_order')
            ->orderBy('id');
        // pasa la query al método de filtrado/paginación
        return $this->getFilteredResults(
            $query,                    // <- aquí pasamos el Builder
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
     *
     *     @OA\Parameter(name="id", in="path", description="ID del Survey", required=true, @OA\Schema(type="integer", example=1)),
     *
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

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey/{id}/preview",
     *     operationId="previewSurvey",
     *     summary="Obtener los datos y una URL temporal para mostrar la encuesta en un iframe",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
     *     @OA\Response(response=200, description="Encuesta y URL firmada listas para vista previa", @OA\JsonContent(ref="#/components/schemas/SurveyPreview")),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permiso surveys.view"),
     *     @OA\Response(response=404, description="Encuesta no encontrada")
     * )
     */
    public function preview(Request $request, $id)
    {
        $survey = $this->surveyService->getSurveyPreviewById((int) $id);
        $expiresAt = now()->addMinutes(
            max(1, min((int) config('surveying.preview_link_ttl_minutes', 30), 120))
        );
        $relativeUrl = URL::temporarySignedRoute(
            'surveys.preview.embed',
            $expiresAt,
            ['survey' => $survey->id],
            false
        );
        $publicBaseUrl = config('surveying.preview_public_url')
            ?: $request->getSchemeAndHttpHost().$request->getBaseUrl();

        return new SurveyPreviewResource(
            $survey,
            rtrim($publicBaseUrl, '/').$relativeUrl,
            $expiresAt->toIso8601String()
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey/{id}/history",
     *     operationId="surveyChangeHistory",
     *     summary="Consultar el historial de cambios de una encuesta",
     *     description="Incluye cambios de la encuesta, sus preguntas y opciones, con valores anteriores y nuevos.",
     *     tags={"Survey"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="action", in="query", required=false, @OA\Schema(type="string", enum={"CREATED", "UPDATED", "DELETED"})),
     *     @OA\Parameter(name="entity_type", in="query", required=false, @OA\Schema(type="string", enum={"SURVEY", "QUESTION", "OPTION"})),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=25)),
     *
     *     @OA\Response(response=200, description="Historial paginado", @OA\JsonContent(type="object", @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/SurveyChangeLog")))),
     *     @OA\Response(response=403, description="Sin permiso surveys.view"),
     *     @OA\Response(response=404, description="Encuesta no encontrada")
     * )
     */
    public function history(Request $request, $id)
    {
        Survey::findOrFail((int) $id);
        $validated = $request->validate([
            'action' => ['nullable', Rule::in([
                SurveyChangeLog::ACTION_CREATED,
                SurveyChangeLog::ACTION_UPDATED,
                SurveyChangeLog::ACTION_DELETED,
            ])],
            'entity_type' => ['nullable', Rule::in(['SURVEY', 'QUESTION', 'OPTION'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $history = SurveyChangeLog::query()
            ->where('survey_id', (int) $id)
            ->when($validated['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($validated['entity_type'] ?? null, fn ($query, $entityType) => $query->where('entity_type', $entityType))
            ->with('user')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return SurveyChangeLogResource::collection($history);
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey-show/{id}",
     *     summary="Mostrar encuesta activa (show_web)",
     *     tags={"Survey"},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="UUID", in="header", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/Survey")),
     *     @OA\Response(response=401, description="UUID ausente o inválido"),
     *     @OA\Response(response=404, description="Encuesta no activa o no encontrada", @OA\JsonContent(@OA\Property(property="error", type="string", example="Esta Encuesta no se encuentra Activa")))
     * )
     */
    public function show_web($id)
    {
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
     * @OA\Get(
     *     path="/moontransparency/public/api/surveys-public",
     *     operationId="listPublicSurveys",
     *     summary="Listar encuestas activas sin iniciar sesión",
     *     tags={"Survey"},
     *
     *     @OA\Parameter(name="UUID", in="header", required=true, description="Clave de acceso de la web pública", @OA\Schema(type="string")),
     *     @OA\Parameter(name="project_id", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="survey_name", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="survey_type", in="query", required=false, @OA\Schema(type="string", enum={"PRE", "POST"})),
     *     @OA\Parameter(name="all", in="query", required=false, @OA\Schema(type="string", enum={"true", "false"})),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100)),
     *
     *     @OA\Response(response=200, description="Listado ordenado de encuestas activas"),
     *     @OA\Response(response=401, description="UUID ausente o inválido"),
     *     @OA\Response(response=422, description="Filtros inválidos")
     * )
     */
    public function publicIndex(PublicSurveyIndexRequest $request)
    {
        $validated = $request->validated();
        $query = Survey::query()
            ->where('status', Survey::STATUS_ACTIVE)
            ->with('proyect:id,name')
            ->withCount('survey_questions')
            ->when(isset($validated['project_id']), fn ($builder) => $builder
                ->where('proyect_id', $validated['project_id']))
            ->when(isset($validated['survey_name']), fn ($builder) => $builder
                ->where('survey_name', 'like', '%'.$validated['survey_name'].'%'))
            ->when(isset($validated['survey_type']), fn ($builder) => $builder
                ->where('survey_type', $validated['survey_type']))
            ->orderByRaw('display_order IS NULL')
            ->orderBy('display_order')
            ->orderBy('id');

        if (($validated['all'] ?? 'false') === 'true') {
            return PublicSurveyResource::collection($query->get());
        }

        return PublicSurveyResource::collection(
            $query->paginate((int) ($validated['per_page'] ?? 15))
        );
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/survey",
     *     summary="Crear Survey",
     *     tags={"Survey"},
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
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
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
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
     *
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
