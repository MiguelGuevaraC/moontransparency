<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_receives_panel_alerts_for_every_surveyor_save_and_finalization(): void
    {
        $administrator = $this->user('Administrador', 'alert-admin');
        $surveyor = $this->user('Encuestador', 'alert-surveyor');
        $project = Proyect::create(['name' => 'Proyecto alertas']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta con alertas',
            'status' => 'ACTIVA',
        ]);
        $payload = [
            'number_document' => 'DOC-ALERT-1',
            'names' => 'Persona Alertada',
            'survey_id' => $survey->id,
            'responses' => [],
        ];

        Sanctum::actingAs($surveyor);
        $created = $this->postJson('/api/response-survey', $payload)->assertOk();
        $id = (int) $created->json('data.id');
        $this->postJson("/api/response-survey/{$id}", $payload)->assertOk();
        $this->postJson("/api/response-survey/{$id}/finalize", $payload)->assertOk();

        $this->assertSame(3, $administrator->fresh()->unreadNotifications()->count());

        Sanctum::actingAs($administrator);
        $alerts = $this->getJson('/api/alerts')->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.unread_count', 3)
            ->assertJsonPath('data.0.data.action', 'FINALIZED');

        $this->getJson('/api/alerts?unread=true')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $alertId = $alerts->json('data.0.id');
        $this->patchJson("/api/alerts/{$alertId}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $alertId);

        $this->getJson('/api/alerts/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);

        $this->patchJson('/api/alerts/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_surveyors_cannot_open_the_administrator_alert_panel(): void
    {
        Sanctum::actingAs($this->user('Encuestador', 'blocked-surveyor'));

        $this->getJson('/api/alerts')->assertForbidden();
    }

    private function user(string $roleName, string $username): User
    {
        $role = Rol::firstOrCreate([
            'name' => $roleName,
        ], [
            'status' => Rol::STATUS_ACTIVE,
        ]);

        return User::create([
            'type_document' => 'DNI',
            'number_document' => 'USR-'.strtoupper($username),
            'names' => $username,
            'username' => $username,
            'password' => 'Password!2026',
            'email' => $username.'@example.test',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
    }
}
