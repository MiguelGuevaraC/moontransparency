<?php

namespace App\Services;

use App\Models\Proyect;
use App\Models\Survey;
use Illuminate\Validation\ValidationException;

class Co2PublicPortalSelection
{
    public function resolve(): array
    {
        $project = $this->resolveProject();
        $baseline = $this->resolveSurvey(
            $project,
            'baseline',
            'baseline.%',
            'línea base'
        );
        $monitoring = $this->resolveSurvey(
            $project,
            'monitoring',
            'monitoring.%',
            'monitoreo'
        );

        return [
            'project_id' => $project->id,
            'baseline_survey_id' => $baseline->id,
            'monitoring_survey_id' => $monitoring->id,
        ];
    }

    private function resolveProject(): Proyect
    {
        $configuredId = config('co2.public_portal.project_id');
        $project = $configuredId
            ? Proyect::query()->find($configuredId)
            : Proyect::query()
                ->where('name', 'like', config('co2.public_portal.project_name').'%')
                ->orderBy('id')
                ->first();

        if (! $project) {
            throw ValidationException::withMessages([
                'project' => 'No se encontró el proyecto fijo de la calculadora CO2. Configure CO2_PUBLIC_PROJECT_ID.',
            ]);
        }

        return $project;
    }

    private function resolveSurvey(
        Proyect $project,
        string $configKey,
        string $calculatorKey,
        string $label
    ): Survey {
        $configuredId = config("co2.public_portal.{$configKey}_survey_id");
        $query = Survey::query()
            ->where('proyect_id', $project->id)
            ->where('status', Survey::STATUS_ACTIVE)
            ->whereHas('survey_questions', fn ($questions) => $questions
                ->where('calculator_key', 'like', $calculatorKey));

        $survey = $configuredId
            ? $query->whereKey($configuredId)->first()
            : $query
                ->where('survey_name', config("co2.public_portal.{$configKey}_survey_name"))
                ->orderBy('id')
                ->first();

        if (! $survey) {
            throw ValidationException::withMessages([
                "{$configKey}_survey" => "No se encontró la encuesta KPT de {$label} activa y mapeada para el proyecto fijo.",
            ]);
        }

        return $survey;
    }
}
