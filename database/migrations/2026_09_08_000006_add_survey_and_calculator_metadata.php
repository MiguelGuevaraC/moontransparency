<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const SURVEY_CODE_INDEX = 'surveys_project_code_unique';

    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('code', 100)->nullable()->after('id');
            $table->boolean('requires_coordinates')->default(false)->after('status');
            $table->unsignedTinyInteger('expected_days')->nullable()->after('requires_coordinates');
            $table->unique(['proyect_id', 'code'], self::SURVEY_CODE_INDEX);
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('calculator_value_type', 20)->nullable()->after('calculator_key');
            $table->string('calculator_unit', 20)->nullable()->after('calculator_value_type');
        });

        $this->backfillSurveyMetadata();
        $this->backfillQuestionMetadata();
    }

    public function down(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn(['calculator_value_type', 'calculator_unit']);
        });

        Schema::table('surveys', function (Blueprint $table) {
            $table->dropUnique(self::SURVEY_CODE_INDEX);
            $table->dropColumn(['code', 'requires_coordinates', 'expected_days']);
        });
    }

    private function backfillSurveyMetadata(): void
    {
        $geoName = (string) config('geobosques.survey.name');
        DB::table('surveys')
            ->where('survey_name', $geoName)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('proyect_id')
            ->each(function ($surveys) {
                $surveys->values()->each(function ($survey, int $index) {
                    $baseCode = (string) config('geobosques.survey.code');
                    DB::table('surveys')->where('id', $survey->id)->update([
                        'code' => $index === 0 ? $baseCode : $baseCode.'_LEGACY_'.$survey->id,
                        'requires_coordinates' => true,
                    ]);
                });
            });

        $calculatorSurveyIds = DB::table('survey_questions')
            ->whereNotNull('calculator_key')
            ->where(function ($query) {
                $query->where('calculator_key', 'like', 'baseline.%')
                    ->orWhere('calculator_key', 'like', 'monitoring.%');
            })
            ->pluck('survey_id')
            ->unique();

        if ($calculatorSurveyIds->isNotEmpty()) {
            DB::table('surveys')->whereIn('id', $calculatorSurveyIds)->update([
                'expected_days' => config('surveying.default_expected_days', 7),
            ]);
        }
    }

    private function backfillQuestionMetadata(): void
    {
        DB::table('survey_questions')
            ->select(['id', 'question_text', 'question_type', 'type_field', 'calculator_key'])
            ->orderBy('id')
            ->get()
            ->each(function ($question) {
                $calculatorKey = $question->calculator_key;
                if (! $calculatorKey && $this->normalize($question->question_text) === 'dia de medicion') {
                    $calculatorKey = 'measurement.day';
                }

                DB::table('survey_questions')->where('id', $question->id)->update([
                    'calculator_key' => $calculatorKey,
                    'calculator_value_type' => $this->valueType($question),
                    'calculator_unit' => $this->unit($calculatorKey),
                ]);
            });
    }

    private function valueType(object $question): string
    {
        return match (Str::upper((string) $question->question_type)) {
            'OPCIONES' => 'options',
            'FILE' => 'file',
            'UBICACION' => 'location',
            default => match (Str::upper((string) $question->type_field)) {
                'NUMERICO', 'NUMERO', 'NUMBER' => 'number',
                'FECHA' => 'date',
                'HORA', 'TIME' => 'time',
                default => 'string',
            },
        };
    }

    private function unit(?string $calculatorKey): ?string
    {
        if (! $calculatorKey) {
            return null;
        }

        if (Str::endsWith($calculatorKey, '_kg')) {
            return 'kg';
        }

        if (Str::startsWith($calculatorKey, 'household.') && $calculatorKey !== 'household.identifier') {
            return 'person';
        }

        return $calculatorKey === 'measurement.day' ? 'day' : null;
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
