<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->timestamps();
        });

        Schema::table('surveyeds', function (Blueprint $table) {
            $table->foreignId('household_id')
                ->nullable()
                ->after('respondent_id')
                ->constrained('households');
        });
    }

    public function down(): void
    {
        Schema::table('surveyeds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('household_id');
        });

        Schema::dropIfExists('households');
    }
};
