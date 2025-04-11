<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveyed_responses', function (Blueprint $table) {
            $table->id();
            $table->text('response_text')->nullable();
            $table->foreignId('survey_question_id')->nullable()->unsigned()->constrained('survey_questions');
            $table->foreignId('surveyed_id')->nullable()->unsigned()->constrained('surveyeds');
            $table->foreignId('respondent_id')->nullable()->unsigned()->constrained('respondents');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('surveyed_responses');
    }
};
