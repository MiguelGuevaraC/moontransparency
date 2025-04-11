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
        Schema::create('surveyeds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_id')->nullable()->unsigned()->constrained('surveys');
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
        Schema::dropIfExists('surveyeds');
    }
};
