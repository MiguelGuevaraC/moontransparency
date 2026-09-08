<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const INDEX_NAME = 'survey_questions_survey_calculator_key_index';

    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('calculator_key', 100)->nullable()->after('question_text');
            $table->index(['survey_id', 'calculator_key'], self::INDEX_NAME);
        });

        $surveyNames = [
            'kpt linea base' => $this->baselineKeys(),
            'kpt monitoreo' => $this->monitoringKeys(),
        ];

        DB::table('surveys')
            ->select(['id', 'survey_name'])
            ->orderBy('id')
            ->get()
            ->each(function ($survey) use ($surveyNames) {
                $questionKeys = $surveyNames[$this->normalize($survey->survey_name)] ?? null;

                if (! $questionKeys) {
                    return;
                }

                DB::table('survey_questions')
                    ->select(['id', 'question_text'])
                    ->where('survey_id', $survey->id)
                    ->get()
                    ->each(function ($question) use ($questionKeys) {
                        $calculatorKey = $questionKeys[$this->normalize($question->question_text)] ?? null;

                        if ($calculatorKey) {
                            DB::table('survey_questions')
                                ->where('id', $question->id)
                                ->update(['calculator_key' => $calculatorKey]);
                        }
                    });
            });
    }

    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropIndex(self::INDEX_NAME);
            $table->dropColumn('calculator_key');
        });
    }

    private function baselineKeys(): array
    {
        return $this->householdKeys() + [
            'dia de medicion' => 'measurement.day',
            'fecha de medicion' => 'measurement.date',
            'hora de pesaje inicial' => 'measurement.start_time',
            'peso inicial de lena solo el dia 1' => 'baseline.initial_wood_kg',
            'peso de lena adicional a partir del dia 2' => 'baseline.additional_wood_kg',
            'peso final de lena sobrante lo que no se uso en el dia' => 'baseline.remaining_wood_kg',
            'peso de carbon producido material lenoso que no se quemo en la hoguera' => 'baseline.charcoal_kg',
        ];
    }

    private function monitoringKeys(): array
    {
        return $this->householdKeys() + [
            'dia de medicion' => 'measurement.day',
            'fecha de medicion' => 'measurement.date',
            'hora de pesaje inicial' => 'measurement.start_time',
            'peso inicial de lena cocina mejorada solo el dia 1' => 'monitoring.moon.initial_wood_kg',
            'peso de lena adicional durante el dia cocina mejorada a partir del dia 2' => 'monitoring.moon.additional_wood_kg',
            'peso final de lena sobrante cocina mejorada lo que no se uso en el dia' => 'monitoring.moon.remaining_wood_kg',
            'peso de carbon producido cocina mejorada material lenoso que no se quemo en la hoguera' => 'monitoring.moon.charcoal_kg',
            'peso inicial de lena cocina tradicional solo el dia 1' => 'monitoring.traditional.initial_wood_kg',
            'peso de lena adicional cocina tradicional a partir del dia 2' => 'monitoring.traditional.additional_wood_kg',
            'peso final de lena sobrante cocina tradicional lo que no se uso en el dia' => 'monitoring.traditional.remaining_wood_kg',
            'peso de carbon producido cocina tradicional material lenoso que no se quemo en la hoguera' => 'monitoring.traditional.charcoal_kg',
            'peso inicial de lena solo el dia 1' => 'monitoring.exclusive.initial_wood_kg',
            'peso de lena adicional a partir del dia 2' => 'monitoring.exclusive.additional_wood_kg',
            'peso final de lena sobrante lo que no se uso en el dia' => 'monitoring.exclusive.remaining_wood_kg',
            'peso de carbon producido material lenoso que no se quemo en la hoguera' => 'monitoring.exclusive.charcoal_kg',
        ];
    }

    private function householdKeys(): array
    {
        return [
            'id del hogar' => 'household.identifier',
            'numero de ninos as de 0 a 14 anos' => 'household.children_0_14',
            'numero de mujeres mayores de 14 anos' => 'household.women_over_14',
            'numero de hombres de 15 a 59 anos' => 'household.men_15_59',
            'numero de hombres mayores de 59 anos' => 'household.men_over_59',
        ];
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
