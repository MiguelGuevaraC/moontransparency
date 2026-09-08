<?php

namespace App\Http\Requests\Concerns;

use App\Models\Survey;

trait ResolvesSurveyExpectedDays
{
    protected function expectedDaysFor($surveyId): int
    {
        $configured = Survey::whereKey($surveyId)->value('expected_days');
        $days = (int) ($configured ?: config('surveying.default_expected_days', 7));

        return max(1, min($days, (int) config('surveying.max_expected_days', 31)));
    }
}
