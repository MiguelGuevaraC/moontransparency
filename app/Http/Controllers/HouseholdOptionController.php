<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Survey;
use App\Models\Surveyed;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HouseholdOptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/survey-show/{survey}/household-options",
     *     operationId="eligibleMonitoringHouseholds",
     *     summary="Buscar hogares elegibles de la línea base vinculada",
     *     description="Devuelve hogares con PRE finalizada que todavía no fueron utilizados en esta encuesta POST.",
     *     tags={"Survey"},
     *
     *     @OA\Parameter(name="survey", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="UUID", in="header", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string", maxLength=64)),
     *     @OA\Parameter(name="surveyed_id", in="query", required=false, description="Incluye la selección actual al editar un borrador.", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100, default=20)),
     *
     *     @OA\Response(response=200, description="Hogares elegibles paginados"),
     *     @OA\Response(response=401, description="UUID ausente o inválido"),
     *     @OA\Response(response=422, description="La encuesta no es POST o no tiene PRE vinculada")
     * )
     */
    public function index(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:64'],
            'surveyed_id' => ['nullable', 'integer', 'exists:surveyeds,id'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        if ($survey->survey_type !== 'POST') {
            throw ValidationException::withMessages([
                'survey' => 'Los hogares elegibles solo se consultan para encuestas POST.',
            ]);
        }

        $preSurvey = $survey->preSurvey()->first();
        if (! $preSurvey) {
            throw ValidationException::withMessages([
                'survey' => 'La encuesta POST no tiene una encuesta PRE vinculada.',
            ]);
        }

        $currentSurveyedId = isset($validated['surveyed_id'])
            ? (int) $validated['surveyed_id']
            : null;
        if ($currentSurveyedId && ! Surveyed::whereKey($currentSurveyedId)
            ->where('survey_id', $survey->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'surveyed_id' => 'La participación no pertenece a esta encuesta POST.',
            ]);
        }

        $query = Household::query()
            ->whereHas('surveyeds', fn ($participations) => $participations
                ->where('survey_id', $preSurvey->id)
                ->where('status', Surveyed::STATUS_FINALIZED))
            ->whereDoesntHave('surveyeds', fn ($participations) => $participations
                ->where('survey_id', $survey->id)
                ->when($currentSurveyedId, fn ($current) => $current
                    ->where('id', '<>', $currentSurveyedId)))
            ->when(isset($validated['search']), fn ($households) => $households
                ->where('code', 'like', '%'.trim($validated['search']).'%'))
            ->orderBy('code')
            ->orderBy('id');

        $paginator = $query->paginate((int) ($validated['per_page'] ?? 20));
        $paginator->setCollection($paginator->getCollection()->map(fn (Household $household) => [
            'id' => $household->id,
            'code' => $household->code,
            'label' => $household->code,
        ]));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
