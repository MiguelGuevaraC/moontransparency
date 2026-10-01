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

    /**
     * Las encuestas sin días de medición aceptan day_number=1 (su único día),
     * que el panel envía por defecto; cualquier otro día se rechaza.
     */
    protected function rejectsDayNumberFor($surveyId, $dayNumber): bool
    {
        return filled($dayNumber)
            && (string) $dayNumber !== '1'
            && ! $this->surveySupportsDailyMeasurements($surveyId);
    }
}
