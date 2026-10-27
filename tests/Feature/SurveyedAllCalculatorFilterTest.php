<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyedAllCalculatorFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_only_returns_responses_from_calculator_surveys(): void
    {
        Sanctum::actingAs($this->reportViewer());

        $project = Proyect::create(['name' => 'Proyecto Power BI']);
        $calculatorSurvey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'KPT línea base',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $regularSurvey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta general',
            'status' => Survey::STATUS_ACTIVE,
        ]);

        $calculatorQuestion = SurveyQuestion::create([
            'survey_id' => $calculatorSurvey->id,
            'question_text' => 'Peso inicial de leña',
            'question_type' => 'LIBRE',
            'is_required' => true,
            'calculator_key' => 'baseline.initial_wood_kg',
        ]);
        $regularQuestion = SurveyQuestion::create([
            'survey_id' => $regularSurvey->id,
            'question_text' => 'Comentario general',
            'question_type' => 'LIBRE',
            'is_required' => false,
        ]);

        $calculatorParticipation = $this->participation($calculatorSurvey, 'PBI-CALC-001');
        $regularParticipation = $this->participation($regularSurvey, 'PBI-GENERAL-001');

        SurveyedResponse::create([
            'surveyed_id' => $calculatorParticipation->id,
            'respondent_id' => $calculatorParticipation->respondent_id,
            'survey_question_id' => $calculatorQuestion->id,
            'response_text' => '10',
        ]);
        SurveyedResponse::create([
            'surveyed_id' => $regularParticipation->id,
            'respondent_id' => $regularParticipation->respondent_id,
            'survey_question_id' => $regularQuestion->id,
            'response_text' => 'No debe alimentar la calculadora',
        ]);

        $this->getJson('/api/surveyedAll')
            ->assertOk()
            ->assertJsonFragment(['surveyed_id' => $calculatorParticipation->id])
            ->assertJsonFragment(['surveyed_id' => $regularParticipation->id]);

        $this->getJson('/api/surveyedAll?calculator_only=true')
            ->assertOk()
            ->assertJsonFragment(['surveyed_id' => $calculatorParticipation->id])
            ->assertJsonMissing(['surveyed_id' => $regularParticipation->id]);
    }

    private function participation(Survey $survey, string $document): Surveyed
    {
        $respondent = Respondent::create([
            'number_document' => $document,
            'names' => 'Persona '.$document,
        ]);

        return Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_FINALIZED,
            'completed_at' => now(),
        ]);
    }

    private function reportViewer(): User
    {
        $role = Rol::create([
            'name' => 'Administrador Power BI',
            'status' => Rol::STATUS_ACTIVE,
        ]);
        $permission = Permission::where('route', 'participations.view')->firstOrFail();
        $role->permissions()->attach($permission->id, [
            'name_permission' => $permission->name,
            'name_rol' => $role->name,
            'type' => $permission->type,
        ]);

        return User::create([
            'number_document' => 'PBI-ADMIN-001',
            'names' => 'Administrador Power BI',
            'username' => 'power-bi-admin',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
