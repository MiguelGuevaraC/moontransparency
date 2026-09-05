<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('surveyed_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surveyed_id')->constrained('surveyeds');
            $table->unsignedTinyInteger('day_number');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['surveyed_id', 'day_number'], 'surveyed_measurement_day_unique');
        });

        Schema::table('surveyed_responses', function (Blueprint $table) {
            $table->foreignId('surveyed_measurement_id')
                ->nullable()
                ->after('surveyed_id')
                ->constrained('surveyed_measurements');
        });

        $daySelections = DB::table('surveyed_responses')
            ->join('survey_questions', 'survey_questions.id', '=', 'surveyed_responses.survey_question_id')
            ->join('surveyed_response_options', function ($join) {
                $join->on('surveyed_response_options.surveyed_response_id', '=', 'surveyed_responses.id')
                    ->whereNull('surveyed_response_options.deleted_at');
            })
            ->join('survey_question_options', function ($join) {
                $join->on('survey_question_options.id', '=', 'surveyed_response_options.survey_question_options_id')
                    ->whereNull('survey_question_options.deleted_at');
            })
            ->whereNull('surveyed_responses.deleted_at')
            ->where('survey_questions.question_text', 'Día de medición')
            ->select(
                'surveyed_responses.surveyed_id',
                'survey_question_options.description as day_number'
            )
            ->get();

        $processedMeasurements = [];

        foreach ($daySelections as $selection) {
            $dayNumber = filter_var($selection->day_number, FILTER_VALIDATE_INT);

            if ($dayNumber === false || $dayNumber < 1 || $dayNumber > 7) {
                continue;
            }

            $measurementKey = $selection->surveyed_id.'-'.$dayNumber;

            if (isset($processedMeasurements[$measurementKey])) {
                continue;
            }

            DB::table('surveyed_measurements')->updateOrInsert([
                'surveyed_id' => $selection->surveyed_id,
                'day_number' => $dayNumber,
            ], [
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $measurementId = DB::table('surveyed_measurements')
                ->where('surveyed_id', $selection->surveyed_id)
                ->where('day_number', $dayNumber)
                ->value('id');

            $processedMeasurements[$measurementKey] = true;

            DB::table('surveyed_responses')
                ->where('surveyed_id', $selection->surveyed_id)
                ->whereNull('surveyed_measurement_id')
                ->update(['surveyed_measurement_id' => $measurementId]);
        }
    }

    public function down()
    {
        Schema::table('surveyed_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surveyed_measurement_id');
        });

        Schema::dropIfExists('surveyed_measurements');
    }
};
