<?php

namespace App\Http\Controllers;

use App\Services\Co2EmissionCalculator;
use App\Services\Co2SurveyDatasetBuilder;
use App\Services\RechCalculatorViewDataMapper;
use Illuminate\Http\Request;

class CalculatorViewController extends Controller
{
    public function index(
        Co2EmissionCalculator $calculator,
        RechCalculatorViewDataMapper $mapper
    ) {
        return $this->calculatorResponse(
            $mapper->configuration($calculator->defaultParameters()),
            [],
            [
                'loaded' => false,
                'message' => 'Abra la calculadora desde el panel administrativo para cargar las encuestas reales.',
                'warnings' => [],
                'fingerprint' => 'empty',
            ]
        );
    }

    public function embed(
        Request $request,
        Co2SurveyDatasetBuilder $datasetBuilder,
        Co2EmissionCalculator $calculator,
        RechCalculatorViewDataMapper $mapper
    ) {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'min:1'],
            'baseline_survey_id' => ['required', 'integer', 'min:1'],
            'monitoring_survey_id' => ['nullable', 'integer', 'min:1'],
            'household_ids' => ['nullable', 'array', 'max:'.config('co2.sample_limit', 20)],
            'household_ids.*' => ['integer', 'distinct'],
            'sample_limit' => ['nullable', 'integer', 'min:1', 'max:'.config('co2.sample_limit', 20)],
        ]);
        $limit = (int) ($validated['sample_limit'] ?? config('co2.sample_limit', 20));
        $dataset = $datasetBuilder->build(
            (int) $validated['project_id'],
            (int) $validated['baseline_survey_id'],
            isset($validated['monitoring_survey_id']) ? (int) $validated['monitoring_survey_id'] : null,
            $validated['household_ids'] ?? [],
            $limit
        );
        $families = $mapper->families($dataset['families'], $limit);
        $fingerprint = hash('sha256', json_encode([
            'project_id' => $dataset['project_id'],
            'baseline_survey' => $dataset['baseline_survey']['id'],
            'monitoring_survey' => $dataset['monitoring_survey']['id'],
            'families' => $families,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $this->calculatorResponse(
            $mapper->configuration($calculator->defaultParameters()),
            $families,
            [
                'loaded' => true,
                'project_id' => $dataset['project_id'],
                'baseline_survey' => $dataset['baseline_survey'],
                'monitoring_survey' => $dataset['monitoring_survey'],
                'available_households' => $dataset['available_households'],
                'selected_households' => $dataset['selected_households'],
                'warnings' => $dataset['warnings'],
                'fingerprint' => $fingerprint,
            ]
        );
    }

    private function calculatorResponse(array $configuration, array $families, array $context)
    {
        $origins = collect(config('co2.embed_allowed_origins', []))
            ->filter(fn ($origin) => is_string($origin)
                && preg_match('#^https?://[a-z0-9.-]+(?::[0-9]+)?$#i', $origin))
            ->implode(' ');
        $frameAncestors = trim("'self' ".$origins);

        return response()
            ->view('calculadora', [
                'calculatorConfig' => $configuration,
                'calculatorFamilies' => $families,
                'calculatorContext' => $context,
            ])
            ->header('Content-Security-Policy', 'frame-ancestors '.$frameAncestors)
            ->header('Referrer-Policy', 'no-referrer');
    }
}
