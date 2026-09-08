<?php

namespace Tests\Feature;

use App\Models\OfflineSyncBatch;
use App\Models\Proyect;
use App\Models\Survey;
use App\Models\Surveyed;
use App\Models\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_batch_synchronizes_days_and_retries_without_duplicates(): void
    {
        [$survey, $question] = $this->createSurveyAndQuestion();
        $payload = $this->payload($survey, $question, '10');

        $this->postJson('/api/offline-sync', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'FULL_SUCCESS')
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('items.0.surveyed_status', Surveyed::STATUS_DRAFT)
            ->assertJsonCount(2, 'items.0.measurements');

        $surveyedId = OfflineSyncBatch::where('batch_id', $payload['batch_id'])
            ->firstOrFail()
            ->response_payload['items'][0]['surveyed_id'];
        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 2);
        $this->assertDatabaseCount('surveyed_responses', 2);

        $this->postJson('/api/offline-sync', $payload)
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('items.0.surveyed_id', $surveyedId);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 2);
        $this->assertDatabaseCount('surveyed_responses', 2);
        $this->assertDatabaseCount('offline_sync_batches', 1);
    }

    public function test_a_new_batch_updates_the_same_local_measurement_without_duplicates(): void
    {
        [$survey, $question] = $this->createSurveyAndQuestion();
        $first = $this->payload($survey, $question, '10');
        $this->postJson('/api/offline-sync', $first)->assertOk();

        $second = $first;
        $second['batch_id'] = (string) Str::uuid();
        $second['items'][0]['client_updated_at'] = now()->addMinute()->toIso8601String();
        $second['items'][0]['measurements'][0]['responses'][0]['response_text'] = '25';

        $this->postJson('/api/offline-sync', $second)
            ->assertOk()
            ->assertJsonPath('status', 'FULL_SUCCESS');

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 2);
        $this->assertDatabaseCount('offline_sync_measurements', 2);
        $this->assertDatabaseHas('surveyed_responses', [
            'survey_question_id' => $question->id,
            'response_text' => '25',
        ]);
    }

    public function test_reusing_a_batch_id_with_different_content_returns_a_conflict(): void
    {
        [$survey, $question] = $this->createSurveyAndQuestion();
        $payload = $this->payload($survey, $question, '10');
        $this->postJson('/api/offline-sync', $payload)->assertOk();

        $payload['items'][0]['measurements'][0]['responses'][0]['response_text'] = '99';

        $this->postJson('/api/offline-sync', $payload)
            ->assertConflict()
            ->assertJsonPath('message', 'El batch_id ya fue utilizado con un payload o archivos diferentes.');

        $this->assertDatabaseMissing('surveyed_responses', ['response_text' => '99']);
        $this->assertDatabaseCount('offline_sync_batches', 1);
    }

    public function test_a_batch_can_return_success_and_error_per_item_without_rolling_back_successes(): void
    {
        [$survey, $question] = $this->createSurveyAndQuestion();
        $payload = $this->payload($survey, $question, '10');
        $invalidItem = $payload['items'][0];
        $invalidItem['client_participation_id'] = (string) Str::uuid();
        $invalidItem['number_document'] = 'DOC-OFFLINE-002';
        $invalidItem['survey_id'] = 999999;
        $invalidItem['measurements'] = [];
        $payload['items'][] = $invalidItem;

        $this->postJson('/api/offline-sync', $payload)
            ->assertStatus(207)
            ->assertJsonPath('status', 'PARTIAL_SUCCESS')
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('error_count', 1)
            ->assertJsonPath('items.0.status', 'SYNCED')
            ->assertJsonPath('items.1.status', 'ERROR')
            ->assertJsonPath('items.1.http_status', 422);

        $this->assertDatabaseCount('surveyeds', 1);
        $this->assertDatabaseCount('surveyed_measurements', 2);
    }

    public function test_older_data_and_invalid_items_are_reported_individually(): void
    {
        [$survey, $question] = $this->createSurveyAndQuestion();
        $first = $this->payload($survey, $question, '10');
        $this->postJson('/api/offline-sync', $first)->assertOk();

        $older = $first;
        $older['batch_id'] = (string) Str::uuid();
        $older['items'][0]['client_updated_at'] = now()->subDay()->toIso8601String();
        $older['items'][0]['measurements'][0]['responses'][0]['response_text'] = '99';
        $older['items'][] = array_merge($older['items'][0], [
            'client_participation_id' => (string) Str::uuid(),
            'client_updated_at' => now()->toIso8601String(),
            'survey_id' => 999999,
            'number_document' => 'DOC-OFFLINE-INVALID',
            'measurements' => [],
        ]);

        $this->postJson('/api/offline-sync', $older)
            ->assertStatus(207)
            ->assertJsonPath('status', 'FAILED')
            ->assertJsonPath('error_count', 2)
            ->assertJsonPath('items.0.http_status', 409)
            ->assertJsonPath('items.0.code', 'CONFLICT')
            ->assertJsonPath('items.1.http_status', 422)
            ->assertJsonPath('items.1.code', 'VALIDATION_ERROR');

        $this->assertDatabaseMissing('surveyed_responses', ['response_text' => '99']);
        $this->assertDatabaseCount('surveyeds', 1);
    }

    public function test_multipart_payload_synchronizes_an_attachment(): void
    {
        Storage::fake('public');
        [$survey, $question] = $this->createSurveyAndQuestion('FILE');
        $payload = $this->payload($survey, $question, null, false);
        $payload['items'][0]['responses'] = [[
            'survey_question_id' => $question->id,
            'attachment_key' => 'evidence_1',
        ]];

        $this->post('/api/offline-sync', [
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'attachments' => [
                'evidence_1' => UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', 'FULL_SUCCESS');

        $filePath = (string) DB::table('surveyed_responses')->value('file_path');
        $this->assertNotSame('', $filePath);
        Storage::disk('public')->assertExists($filePath);
    }

    private function payload(Survey $survey, SurveyQuestion $question, ?string $value, bool $withDays = true): array
    {
        $participationId = (string) Str::uuid();
        $measurements = $withDays ? [
            [
                'client_measurement_id' => (string) Str::uuid(),
                'day_number' => 1,
                'responses' => [[
                    'survey_question_id' => $question->id,
                    'response_text' => $value,
                ]],
            ],
            [
                'client_measurement_id' => (string) Str::uuid(),
                'day_number' => 2,
                'responses' => [[
                    'survey_question_id' => $question->id,
                    'response_text' => $value === null ? null : (string) ((int) $value + 1),
                ]],
            ],
        ] : [];

        return [
            'contract_version' => '1.0',
            'batch_id' => (string) Str::uuid(),
            'device_id' => 'field-tablet-01',
            'items' => [[
                'client_participation_id' => $participationId,
                'client_updated_at' => now()->toIso8601String(),
                'action' => 'SAVE_DRAFT',
                'number_document' => 'DOC-OFFLINE-001',
                'names' => 'Persona sin conexión',
                'survey_id' => $survey->id,
                'measurements' => $measurements,
            ]],
        ];
    }

    private function createSurveyAndQuestion(string $questionType = 'LIBRE'): array
    {
        $project = Proyect::create(['name' => 'Proyecto offline']);
        $survey = Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => 'Encuesta offline '.uniqid(),
            'survey_type' => 'PRE',
            'description' => 'Encuesta para sincronización offline.',
            'status' => Survey::STATUS_ACTIVE,
        ]);
        $question = SurveyQuestion::create([
            'survey_id' => $survey->id,
            'question_text' => 'Valor capturado',
            'question_type' => $questionType,
            'type_field' => $questionType === 'FILE' ? null : 'NUMERICO',
            'order' => 1,
            'is_required' => false,
            'eje' => 'Offline',
            'justification' => '-',
        ]);

        return [$survey, $question];
    }
}
