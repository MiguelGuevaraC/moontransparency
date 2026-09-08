<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HouseholdIdentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_generates_a_global_household_code_separate_from_the_document(): void
    {
        $survey = $this->createSurvey('Identificación');

        $created = $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-01'))
            ->assertOk()
            ->assertJsonPath('data.household.code', 'HOG-00000001');

        $surveyedId = (int) $created->json('data.id');
        $householdId = (int) $created->json('data.household.id');

        $this->assertNotSame('DOC-PERSONA-01', $created->json('data.household.code'));
        $this->assertDatabaseHas('households', [
            'id' => $householdId,
            'code' => 'HOG-00000001',
        ]);
        $this->assertDatabaseHas('surveyeds', [
            'id' => $surveyedId,
            'household_id' => $householdId,
        ]);

        $this->authenticateSurveyor();
        $this->getJson("/api/surveyed/{$surveyedId}/calculator")
            ->assertOk()
            ->assertJsonPath('data.contract.version', '1.2')
            ->assertJsonPath('data.household.id', $householdId)
            ->assertJsonPath('data.household.code', 'HOG-00000001')
            ->assertJsonPath('data.household.identifier', 'HOG-00000001')
            ->assertJsonPath('data.household.source', 'HOUSEHOLD');
    }

    public function test_same_respondent_reuses_the_household_across_different_surveys(): void
    {
        $identification = $this->createSurvey('Identificación');
        $baseline = $this->createSurvey('KPT línea base');

        $first = $this->postJson('/api/response-survey', $this->payload($identification, 'DOC-PERSONA-02'))
            ->assertOk();
        $second = $this->postJson('/api/response-survey', $this->payload($baseline, 'DOC-PERSONA-02'))
            ->assertOk();

        $this->assertSame($first->json('data.household.id'), $second->json('data.household.id'));
        $this->assertSame($first->json('data.household.code'), $second->json('data.household.code'));
        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('surveyeds', 2);
    }

    public function test_multiple_respondents_can_be_linked_to_the_same_household(): void
    {
        $survey = $this->createSurvey('Encuesta familiar');
        $first = $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-03'))
            ->assertOk();
        $householdCode = $first->json('data.household.code');

        $second = $this->postJson('/api/response-survey', $this->payload(
            $survey,
            'DOC-PERSONA-04',
            $householdCode
        ))
            ->assertOk()
            ->assertJsonPath('data.household.code', $householdCode);

        $this->assertSame($first->json('data.household.id'), $second->json('data.household.id'));
        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('respondents', 2);
        $this->assertDatabaseCount('surveyeds', 2);
    }

    public function test_unknown_or_malformed_household_codes_are_rejected(): void
    {
        $survey = $this->createSurvey('Validación de hogar');

        $this->postJson('/api/response-survey', $this->payload(
            $survey,
            'DOC-PERSONA-05',
            'HOG-99999999'
        ))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El ID del hogar indicado no existe.');

        $this->postJson('/api/response-survey', $this->payload(
            $survey,
            'DOC-PERSONA-06',
            'CASA-1'
        ))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El ID del hogar debe tener el formato HOG-00000001.');

        $this->assertDatabaseCount('households', 0);
        $this->assertDatabaseCount('surveyeds', 0);
    }

    public function test_an_existing_participation_cannot_be_reassigned_to_another_household(): void
    {
        $survey = $this->createSurvey('Protección de vínculo');
        $first = $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-07'))
            ->assertOk();
        $second = $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-08'))
            ->assertOk();

        $firstId = (int) $first->json('data.id');
        $firstHouseholdId = (int) $first->json('data.household.id');

        $this->postJson("/api/response-survey/{$firstId}", $this->payload(
            $survey,
            'DOC-PERSONA-07',
            $second->json('data.household.code')
        ))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'La participación ya pertenece a otro hogar y no puede reasignarse.');

        $this->assertSame($firstHouseholdId, Surveyed::findOrFail($firstId)->household_id);
        $this->assertDatabaseCount('households', 2);
    }

    public function test_history_can_be_filtered_by_the_global_household_code(): void
    {
        $survey = $this->createSurvey('Historial por hogar');
        $first = $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-09'))
            ->assertOk();
        $this->postJson('/api/response-survey', $this->payload($survey, 'DOC-PERSONA-10'))
            ->assertOk();
        $this->authenticateSurveyor();

        $this->getJson('/api/surveyed?all=true&household_code='.$first->json('data.household.code'))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $first->json('data.id'))
            ->assertJsonPath('0.household.code', $first->json('data.household.code'));
    }

    private function createSurvey(string $name): Survey
    {
        $project = Proyect::create(['name' => 'Proyecto '.$name]);

        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => $name,
            'status' => 'ACTIVA',
        ]);
    }

    private function payload(Survey $survey, string $document, ?string $householdCode = null): array
    {
        return array_filter([
            'number_document' => $document,
            'names' => 'Persona '.$document,
            'survey_id' => $survey->id,
            'household_code' => $householdCode,
            'responses' => [],
        ], static fn ($value) => $value !== null);
    }

    private function authenticateSurveyor(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-HOUSEHOLD-001',
            'username' => 'household-test',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Encuestador')->firstOrFail()->id,
        ]));
    }
}
