<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DESCRIPTION = 'Se inicializó el historial con el estado actual de la encuesta.';

    public function up(): void
    {
        if (! Schema::hasTable('surveys') || ! Schema::hasTable('survey_change_logs')) {
            return;
        }

        $ignoredFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $now = now();

        DB::table('surveys')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($surveys) use ($ignoredFields, $now) {
                $surveyIds = $surveys->pluck('id')->map(fn ($id) => (int) $id)->all();
                $surveyIdsWithHistory = DB::table('survey_change_logs')
                    ->whereIn('survey_id', $surveyIds)
                    ->pluck('survey_id')
                    ->map(fn ($id) => (int) $id)
                    ->flip();

                $rows = $surveys
                    ->reject(fn ($survey) => $surveyIdsWithHistory->has((int) $survey->id))
                    ->map(function ($survey) use ($ignoredFields, $now) {
                        $changes = collect((array) $survey)
                            ->except($ignoredFields)
                            ->map(fn ($value) => ['old' => null, 'new' => $value])
                            ->all();

                        return [
                            'survey_id' => $survey->id,
                            'action' => 'CREATED',
                            'entity_type' => 'SURVEY',
                            'entity_id' => $survey->id,
                            'description' => self::DESCRIPTION,
                            'changes' => json_encode(
                                $changes,
                                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                            ),
                            'user_id' => null,
                            'ip_address' => null,
                            'user_agent' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    })
                    ->values()
                    ->all();

                if ($rows !== []) {
                    DB::table('survey_change_logs')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('survey_change_logs')) {
            return;
        }

        DB::table('survey_change_logs')
            ->where('description', self::DESCRIPTION)
            ->delete();
    }
};
