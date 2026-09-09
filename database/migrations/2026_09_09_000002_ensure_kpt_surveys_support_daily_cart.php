<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const KPT_BASELINE = 'kpt linea base';

    private const KPT_MONITORING = 'kpt monitoreo';

    public function up(): void
    {
        DB::transaction(function () {
            $surveys = DB::table('surveys')
                ->whereNull('deleted_at')
                ->get()
                ->filter(fn ($survey) => in_array($this->normalize($survey->survey_name), [
                    self::KPT_BASELINE,
                    self::KPT_MONITORING,
                ], true));

            foreach ($surveys as $survey) {
                DB::table('surveys')->where('id', $survey->id)->update([
                    'expected_days' => 7,
                    'updated_at' => now(),
                ]);

                $this->ensureDayQuestion((int) $survey->id);
            }

            $surveys
                ->groupBy('proyect_id')
                ->each(function ($projectSurveys) {
                    $baseline = $projectSurveys
                        ->first(fn ($survey) => $this->normalize($survey->survey_name) === self::KPT_BASELINE);
                    $monitoring = $projectSurveys
                        ->first(fn ($survey) => $this->normalize($survey->survey_name) === self::KPT_MONITORING);

                    if ($baseline && $monitoring && (int) ($baseline->post_survey_id ?? 0) !== (int) $monitoring->id) {
                        DB::table('surveys')->where('id', $baseline->id)->update([
                            'post_survey_id' => $monitoring->id,
                            'updated_at' => now(),
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        // No se revierte para no romper encuestas KPT que ya tengan respuestas diarias.
    }

    private function ensureDayQuestion(int $surveyId): void
    {
        $now = now();
        $question = DB::table('survey_questions')
            ->where('survey_id', $surveyId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('calculator_key', 'measurement.day')
                    ->orWhere('question_text', 'Día de medición');
            })
            ->orderByRaw("CASE WHEN calculator_key = 'measurement.day' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($question) {
            DB::table('survey_questions')->where('id', $question->id)->update([
                'question_text' => 'Día de medición',
                'calculator_key' => 'measurement.day',
                'calculator_value_type' => 'options',
                'calculator_unit' => 'day',
                'question_type' => 'OPCIONES',
                'type_field' => 'LISTADO',
                'is_required' => true,
                'updated_at' => $now,
            ]);
            $questionId = (int) $question->id;
        } else {
            $maxOrder = DB::table('survey_questions')
                ->where('survey_id', $surveyId)
                ->whereNull('deleted_at')
                ->max('order');
            $questionId = (int) DB::table('survey_questions')->insertGetId([
                'survey_id' => $surveyId,
                'question_text' => 'Día de medición',
                'calculator_key' => 'measurement.day',
                'calculator_value_type' => 'options',
                'calculator_unit' => 'day',
                'question_type' => 'OPCIONES',
                'type_field' => 'LISTADO',
                'order' => $maxOrder !== null ? ((float) $maxOrder + 1) : 1,
                'eje' => 'Medición diaria',
                'justification' => 'Permite guardar y continuar la misma participación por día, como el carrito del Excel.',
                'is_required' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (range(1, 7) as $day) {
            $option = DB::table('survey_question_options')
                ->where('survey_question_id', $questionId)
                ->where('description', (string) $day)
                ->first();

            if ($option) {
                DB::table('survey_question_options')->where('id', $option->id)->update([
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('survey_question_options')->insert([
                    'survey_question_id' => $questionId,
                    'description' => (string) $day,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function normalize(?string $value): string
    {
        return Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
};
