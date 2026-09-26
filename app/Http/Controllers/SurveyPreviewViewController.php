<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\SurveyService;

class SurveyPreviewViewController extends Controller
{
    public function show(Survey $survey, SurveyService $surveyService)
    {
        $survey = $surveyService->getSurveyPreviewById((int) $survey->id);
        $origins = collect(config('surveying.preview_embed_allowed_origins', []))
            ->filter(fn ($origin) => is_string($origin)
                && preg_match('#^https?://[a-z0-9.-]+(?::[0-9]+)?$#i', $origin))
            ->implode(' ');
        $frameAncestors = trim("'self' ".$origins);

        return response()
            ->view('survey-preview', [
                'survey' => $survey,
                'supportsDailyMeasurements' => $survey->supportsDailyMeasurements(),
                'expectedDays' => $survey->expectedDays(),
            ])
            ->header('Content-Security-Policy', 'frame-ancestors '.$frameAncestors)
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
