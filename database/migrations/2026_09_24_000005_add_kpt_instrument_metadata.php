<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('instrument_key', 120)->nullable()->after('question_text');
            $table->string('response_scope', 20)->nullable()->after('calculator_unit');
            $table->json('applicable_days')->nullable()->after('response_scope');
            $table->string('scenario', 40)->nullable()->after('applicable_days');
            $table->string('section_key', 100)->nullable()->after('scenario');
            $table->string('section_title')->nullable()->after('section_key');

            $table->index(
                ['survey_id', 'instrument_key'],
                'survey_questions_survey_instrument_key_index'
            );
        });

        Schema::table('surveyeds', function (Blueprint $table) {
            $table->string('survey_variant', 40)->nullable()->after('survey_id');
        });
    }

    public function down(): void
    {
        Schema::table('surveyeds', function (Blueprint $table) {
            $table->dropColumn('survey_variant');
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropIndex('survey_questions_survey_instrument_key_index');
            $table->dropColumn([
                'instrument_key',
                'response_scope',
                'applicable_days',
                'scenario',
                'section_key',
                'section_title',
            ]);
        });
    }
};
