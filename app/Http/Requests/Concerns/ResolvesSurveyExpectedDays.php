<?php

namespace App\Http\Requests\Concerns;

use App\Models\Survey;

trait ResolvesSurveyExpectedDays
{
    protected function expectedDaysFor($surveyId): int
    {
        return Survey::find($surveyId)?->expectedDays() ?? Survey::KPT_EXPECTED_DAYS;
    }

    protected function surveySupportsDailyMeasurements($surveyId): bool
    {
        return Survey::find($surveyId)?->supportsDailyMeasurements() ?? false;
    }
}
