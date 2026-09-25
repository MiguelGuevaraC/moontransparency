<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('menus')->where('code', 'home')->update([
            'status' => 'Inactivo',
            'updated_at' => now(),
        ]);

        $mappedSurveyIds = DB::table('survey_questions')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('calculator_key', 'like', 'baseline.%')
                    ->orWhere('calculator_key', 'like', 'monitoring.%');
            })
            ->pluck('survey_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $kptSurveyIds = DB::table('surveys')
            ->whereNull('deleted_at')
            ->get(['id', 'survey_name'])
            ->filter(function ($survey) use ($mappedSurveyIds) {
                if (in_array((int) $survey->id, $mappedSurveyIds, true)) {
                    return true;
                }

                $name = Str::lower(Str::ascii((string) $survey->survey_name));

                return str_contains($name, 'kpt')
                    && (str_contains($name, 'linea base') || str_contains($name, 'monitoreo'));
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::table('surveys')->whereIn('id', $kptSurveyIds)->update([
            'expected_days' => 7,
            'updated_at' => now(),
        ]);

        DB::table('surveys')->whereNotIn('id', $kptSurveyIds)->update([
            'expected_days' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('menus')->where('code', 'home')->update([
            'status' => 'Activo',
            'updated_at' => now(),
        ]);
    }
};
