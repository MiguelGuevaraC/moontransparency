<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyDisplayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_list_uses_the_display_order_selected_by_the_user(): void
    {
        $this->authenticateAdministrator();
        $project = Proyect::create(['name' => 'Proyecto con orden']);

        $this->postJson('/api/survey', $this->payload($project, 'Encuesta tercera', 30))
            ->assertCreated()
            ->assertJsonPath('data.display_order', 30);
        $this->postJson('/api/survey', $this->payload($project, 'Encuesta primera', 10))
            ->assertCreated()
            ->assertJsonPath('data.display_order', 10);
        $this->postJson('/api/survey', $this->payload($project, 'Encuesta segunda', 20))
            ->assertCreated()
            ->assertJsonPath('data.display_order', 20);

        $this->getJson('/api/survey?all=true')
            ->assertOk()
            ->assertJsonPath('0.survey_name', 'Encuesta primera')
            ->assertJsonPath('0.display_order', 10)
            ->assertJsonPath('1.survey_name', 'Encuesta segunda')
            ->assertJsonPath('1.display_order', 20)
            ->assertJsonPath('2.survey_name', 'Encuesta tercera')
            ->assertJsonPath('2.display_order', 30);
    }

    public function test_display_order_can_be_updated_and_must_be_a_positive_integer(): void
    {
        $this->authenticateAdministrator();
        $project = Proyect::create(['name' => 'Proyecto para reordenar']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta reordenable',
            'description' => 'Descripción',
            'survey_type' => 'PRE',
            'status' => Survey::STATUS_ACTIVE,
            'display_order' => 8,
        ]);

        $this->putJson('/api/survey/'.$survey->id, $this->payload($project, $survey->survey_name, 2))
            ->assertOk()
            ->assertJsonPath('data.display_order', 2);

        $this->putJson('/api/survey/'.$survey->id, $this->payload($project, $survey->survey_name, 0))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El orden de la encuesta debe ser mayor o igual a 1.');
    }

    public function test_backend_assigns_the_next_order_when_an_old_client_omits_it(): void
    {
        $project = Proyect::create(['name' => 'Proyecto compatible']);
        Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta existente',
            'display_order' => 12,
        ]);

        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta de cliente anterior',
        ]);

        $this->assertSame(13, $survey->display_order);
    }

    private function payload(Proyect $project, string $name, int $displayOrder): array
    {
        return [
            'proyect_id' => $project->id,
            'survey_name' => $name,
            'description' => 'Encuesta para validar el orden',
            'status' => Survey::STATUS_ACTIVE,
            'survey_type' => 'PRE',
            'display_order' => $displayOrder,
        ];
    }

    private function authenticateAdministrator(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-SURVEY-ORDER',
            'names' => 'Administrador de orden',
            'username' => 'survey-order-admin',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->firstOrFail()->id,
        ]));
    }
}
