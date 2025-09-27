<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_question_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_question_id')->nullable()->constrained('survey_questions');
            $table->foreignId('ods_id')->nullable()->constrained('ods');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('survey_question_ods');
    }
};