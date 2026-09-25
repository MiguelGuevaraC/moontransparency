<?php

namespace App\Providers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
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
        SurveyQuestion::observe(SurveyChangeObserver::class);
        SurveyQuestionOption::observe(SurveyChangeObserver::class);
    }
}
