<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('co2_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('proyects')->restrictOnDelete();
            $table->foreignId('baseline_survey_id')->constrained('surveys')->restrictOnDelete();
            $table->foreignId('monitoring_survey_id')->nullable()->constrained('surveys')->nullOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('household_ids')->nullable();
            $table->json('parameters');
            $table->json('result');
            $table->string('methodology', 50);
            $table->string('formula_version', 50);
            $table->string('contract_version', 20);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'created_at']);
            $table->index(['baseline_survey_id', 'monitoring_survey_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('co2_calculations');
    }
};
