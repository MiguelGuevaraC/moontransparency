<?php

namespace App\Http\Controllers;

use App\Http\Requests\Co2CalculationRequest;
use App\Models\Co2Calculation;
use App\Models\Proyect;
use App\Models\Survey;
use App\Services\Co2EmissionCalculator;
use App\Services\Co2SurveyDatasetBuilder;
use Illuminate\Http\Request;

class Co2CalculatorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/calculator/co2/configuration",
     *     operationId="co2CalculatorConfiguration",
     *     summary="Obtener parámetros RECH y encuestas KPT compatibles",
     *     tags={"CO2 Calculator"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="project_id", in="query", required=true, @OA\Schema(type="integer", minimum=1)),
     *
     *     @OA\Response(response=200, description="Configuración y encuestas disponibles"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin el permiso calculator.view"),
     *     @OA\Response(response=404, description="Proyecto no encontrado"),
     *     @OA\Response(response=422, description="Validación fallida")
     * )
     */
    public function configuration(Request $request, Co2EmissionCalculator $calculator)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
        ]);
        $project = Proyect::find($validated['project_id']);
        if (! $project) {
            return response()->json(['message' => 'Proyecto no encontrado.'], 404);
        }

        $surveys = Survey::query()
            ->where('proyect_id', $project->id)
            ->whereHas('survey_questions', fn ($query) => $query
                ->whereNotNull('calculator_key')
                ->where(function ($keys) {
                    $keys->where('calculator_key', 'like', 'baseline.%')
                        ->orWhere('calculator_key', 'like', 'monitoring.%');
                }))
            ->withCount('surveyeds')
            ->orderBy('id')
            ->get()
            ->map(function (Survey $survey) {
                return [
                    'id' => $survey->id,
                    'code' => $survey->code,
                    'name' => $survey->survey_name,
                    'kind' => $survey->calculatorKind(),
                    'status' => $survey->status,
                    'post_survey_id' => $survey->post_survey_id,
                    'participations_count' => $survey->surveyeds_count,
                ];
            })->values();

        return response()->json(['data' => [
            'contract_version' => config('co2.contract_version', '1.0'),
            'methodology' => config('co2.methodology', 'RECH v5.0'),
            'project' => ['id' => $project->id, 'name' => $project->name],
            'sample_limit' => config('co2.sample_limit', 20),
            'surveys' => $surveys,
            'default_parameters' => $calculator->defaultParameters(),
        ]]);
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/calculator/co2/history",
     *     operationId="co2CalculationHistory",
     *     summary="Consultar el historial de cálculos CO2",
     *     tags={"CO2 Calculator"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="project_id", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100)),
     *     @OA\Response(response=200, description="Historial paginado de cálculos"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin el permiso calculator.view"),
     *     @OA\Response(response=422, description="Filtros inválidos")
     * )
     */
    public function history(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'min:1', 'exists:proyects,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $calculations = Co2Calculation::query()
            ->with(['project:id,name', 'baselineSurvey:id,survey_name', 'monitoringSurvey:id,survey_name', 'executedBy:id,names'])
            ->when(isset($validated['project_id']), fn ($query) => $query->where('project_id', $validated['project_id']))
            ->latest('id')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'data' => $calculations->getCollection()->map(fn (Co2Calculation $calculation) => [
                'id' => $calculation->id,
                'project' => $calculation->project,
                'baseline_survey' => $calculation->baselineSurvey,
                'monitoring_survey' => $calculation->monitoringSurvey,
                'executed_by' => $calculation->executedBy,
                'household_ids' => $calculation->household_ids,
                'parameters' => $calculation->parameters,
                'result' => $calculation->result,
                'methodology' => $calculation->methodology,
                'formula_version' => $calculation->formula_version,
                'contract_version' => $calculation->contract_version,
                'created_at' => $calculation->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $calculations->currentPage(),
                'last_page' => $calculations->lastPage(),
                'per_page' => $calculations->perPage(),
                'total' => $calculations->total(),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/moontransparency/public/api/calculator/co2",
     *     operationId="calculateCo2Reduction",
     *     summary="Calcular la reducción neta de emisiones con la metodología RECH v5.0",
     *     description="Consolida hasta 20 hogares de las encuestas KPT de línea base y monitoreo. Aplica precisión 90/10, tope per cápita, DAF, fugas y efecto Hawthorne.",
     *     tags={"CO2 Calculator"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"project_id", "baseline_survey_id"},
     *
     *         @OA\Property(property="project_id", type="integer", example=2),
     *         @OA\Property(property="baseline_survey_id", type="integer", example=10),
     *         @OA\Property(property="monitoring_survey_id", type="integer", nullable=true, example=11),
     *         @OA\Property(property="household_ids", type="array", maxItems=20, @OA\Items(type="integer")),
     *         @OA\Property(property="sample_limit", type="integer", minimum=1, maximum=20, example=20),
     *         @OA\Property(property="parameters", type="object",
     *             @OA\Property(property="monitoring_year", type="integer", example=2026),
     *             @OA\Property(property="monitoring_method", type="string", enum={"MANUAL", "SENSORS"}),
     *             @OA\Property(property="destruction_evidence", type="boolean", example=false)
     *         )
     *     )),
     *
     *     @OA\Response(response=200, description="Cálculo RECH, parámetros, pesos originales, componentes Moon/tradicional y trazabilidad por hogar"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin el permiso calculator.view"),
     *     @OA\Response(response=422, description="Encuestas incompatibles o parámetros inválidos")
     * )
     */
    public function calculate(
        Co2CalculationRequest $request,
        Co2SurveyDatasetBuilder $datasetBuilder,
        Co2EmissionCalculator $calculator
    ) {
        $validated = $request->validated();
        $dataset = $datasetBuilder->build(
            (int) $validated['project_id'],
            (int) $validated['baseline_survey_id'],
            isset($validated['monitoring_survey_id']) ? (int) $validated['monitoring_survey_id'] : null,
            $validated['household_ids'] ?? [],
            (int) ($validated['sample_limit'] ?? config('co2.sample_limit', 20))
        );
        $calculation = $calculator->calculate($dataset['families'], $validated['parameters'] ?? []);
        $calculationRecord = Co2Calculation::create([
            'project_id' => $dataset['project_id'],
            'baseline_survey_id' => $validated['baseline_survey_id'],
            'monitoring_survey_id' => $validated['monitoring_survey_id'] ?? $dataset['monitoring_survey']['id'] ?? null,
            'executed_by' => $request->user()?->id,
            'household_ids' => $validated['household_ids'] ?? null,
            'parameters' => $calculation['parameters'],
            'result' => $calculation,
            'methodology' => config('co2.methodology'),
            'formula_version' => config('co2.formula_version'),
            'contract_version' => config('co2.contract_version'),
        ]);

        return response()->json(['data' => [
            'calculation_id' => $calculationRecord->id,
            'calculated_at' => $calculationRecord->created_at?->toIso8601String(),
            'source' => [
                'project_id' => $dataset['project_id'],
                'baseline_survey' => $dataset['baseline_survey'],
                'monitoring_survey' => $dataset['monitoring_survey'],
                'available_households' => $dataset['available_households'],
                'selected_households' => $dataset['selected_households'],
                'warnings' => $dataset['warnings'],
                'households' => $dataset['families'],
            ],
            'calculation' => $calculation,
        ]]);
    }
}
