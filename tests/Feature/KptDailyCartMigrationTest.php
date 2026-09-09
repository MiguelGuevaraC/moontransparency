<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KptDailyCartMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prepares_existing_kpt_surveys_for_daily_cart_capture(): void
    {
        $project = Proyect::create(['name' => 'Proyecto KPT']);
        $baseline = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
            'expected_days' => null,
        ]);
        $monitoring = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT monitoreo',
            'survey_type' => 'POST',
            'status' => Survey::STATUS_ACTIVE,
            'expected_days' => null,
        ]);

        SurveyQuestion::create([
            'survey_id' => $baseline->id,
            'question_text' => 'ID del hogar',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);
        SurveyQuestion::create([
            'survey_id' => $monitoring->id,
            'question_text' => 'ID del hogar',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
            'is_required' => true,
        ]);

        $migration = require database_path('migrations/2026_09_09_000002_ensure_kpt_surveys_support_daily_cart.php');
        $migration->up();
        $migration->up();

        $this->assertSame(7, $baseline->fresh()->expected_days);
        $this->assertSame(7, $monitoring->fresh()->expected_days);
        $this->assertSame($monitoring->id, $baseline->fresh()->post_survey_id);

        foreach ([$baseline, $monitoring] as $survey) {
            $dayQuestions = $survey->survey_questions()
                ->where('calculator_key', 'measurement.day')
                ->with('survey_questions_options')
                ->get();

            $this->assertCount(1, $dayQuestions);
            $this->assertSame('Día de medición', $dayQuestions->first()->question_text);
            $this->assertSame('OPCIONES', $dayQuestions->first()->question_type);
            $this->assertSame('LISTADO', $dayQuestions->first()->type_field);
            $this->assertTrue((bool) $dayQuestions->first()->is_required);
            $this->assertSame(
                ['1', '2', '3', '4', '5', '6', '7'],
                $dayQuestions->first()->survey_questions_options->pluck('description')->sort()->values()->all()
            );
        }
    }
}
