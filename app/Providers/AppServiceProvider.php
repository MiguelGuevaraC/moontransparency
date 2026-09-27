<?php

namespace App\Providers;

use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyedResponseOption;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionOds;
use App\Models\SurveyQuestionOption;
use App\Observers\SurveyChangeObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Survey::observe(SurveyChangeObserver::class);
        Surveyed::observe(SurveyChangeObserver::class);
        SurveyedMeasurement::observe(SurveyChangeObserver::class);
        SurveyedResponse::observe(SurveyChangeObserver::class);
        SurveyedResponseOption::observe(SurveyChangeObserver::class);
        SurveyQuestion::observe(SurveyChangeObserver::class);
        SurveyQuestionOds::observe(SurveyChangeObserver::class);
        SurveyQuestionOption::observe(SurveyChangeObserver::class);
    }
}
