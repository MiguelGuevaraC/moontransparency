<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Respondent;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyedMeasurement;
use App\Models\SurveyedResponse;
use App\Models\SurveyQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SurveyedExcelCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_preserves_legacy_columns_and_adds_participation_metadata(): void
    {
        $response = $this->createParticipation('=FORMULA');
        $this->authenticateAdministrator();

        $export = $this->get('/api/surveyedExcel')->assertOk();
        $content = $export->getContent();

        $this->assertStringContainsString('<th>ID</th>', $content);
        $this->assertStringContainsString('<th>Respuesta</th>', $content);
        $this->assertStringContainsString('<th>Participación ID</th>', $content);
        $this->assertStringContainsString('<th>Estado</th>', $content);
        $this->assertStringContainsString('<th>Día</th>', $content);
        $this->assertStringContainsString('<th>Código hogar</th>', $content);
        $this->assertStringContainsString('&#039;=FORMULA', $content);
        $this->assertStringContainsString((string) $response->id, $content);
    }

    public function test_import_updates_a_response_using_the_export_compatible_headers(): void
    {
        $response = $this->createParticipation('antes');
        $this->authenticateAdministrator();
        $file = $this->xlsx([
            ['ID', 'Proyecto', 'Encuesta', 'Fecha Respuesta', 'Genero Encuestado', 'Encuestado', 'Pregunta', 'Tipo Pregunta', 'Respuesta', 'Opción Seleccionada', 'ID Opción Seleccionada', 'Archivo'],
            [$response->id, '', '', '', '', '', '', 'LIBRE', 'después', '', '', ''],
        ]);

        $this->postJson('/api/surveyed/import-excel', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('actualizados', 1)
            ->assertJsonPath('opciones_actualizadas', 0);

        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $response->id,
            'response_text' => 'después',
        ]);
    }

    public function test_import_rejects_non_numeric_ids_without_changing_data(): void
    {
        $response = $this->createParticipation('sin cambios');
        $this->authenticateAdministrator();
        $file = $this->xlsx([
            ['ID', 'Respuesta'],
            ['1 OR 1=1', 'ataque'],
        ]);

        $this->postJson('/api/surveyed/import-excel', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseHas('surveyed_responses', [
            'id' => $response->id,
            'response_text' => 'sin cambios',
        ]);
    }

    private function createParticipation(string $value): SurveyedResponse
    {
        $project = Proyect::create(['name' => 'Proyecto Excel']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta Excel',
            'status' => 'ACTIVA',
            'expected_days' => 7,
        ]);
        $respondent = Respondent::create([
            'number_document' => 'DOC-EXCEL-001',
            'names' => 'Persona Excel',
        ]);
        $surveyed = Surveyed::create([
            'respondent_id' => $respondent->id,
            'survey_id' => $survey->id,
            'status' => Surveyed::STATUS_DRAFT,
            'latitude' => -12.1,
            'longitude' => -77.1,
        ]);
        $measurement = SurveyedMeasurement::create(['surveyed_id' => $surveyed->id, 'day_number' => 1]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Dato de prueba',
            'question_type' => 'LIBRE',
            'type_field' => 'CORTO',
            'order' => 1,
        ]);

        return SurveyedResponse::create([
            'surveyed_id' => $surveyed->id,
            'respondent_id' => $respondent->id,
            'surveyed_measurement_id' => $measurement->id,
            'survey_question_id' => $question->id,
            'response_text' => $value,
        ]);
    }

    private function authenticateAdministrator(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'DOC-ADMIN-EXCEL',
            'username' => 'admin-excel',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->firstOrFail()->id,
        ]));
    }

    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'surveyed_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'encuestas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
