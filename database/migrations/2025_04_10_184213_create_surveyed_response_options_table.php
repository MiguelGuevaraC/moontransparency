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
        Schema::create('surveyed_response_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surveyed_response_id')->nullable()->unsigned()->constrained('surveyed_responses');
            $table->foreignId('survey_question_options_id')->nullable()->unsigned()->constrained('survey_question_options');
            $table->foreignId('respondent_id')->nullable()->unsigned()->constrained('respondents');
            $table->foreignId('surveyed_id')->nullable()->unsigned()->constrained('surveyeds');
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
        Schema::dropIfExists('surveyed_response_options');
    }
};
