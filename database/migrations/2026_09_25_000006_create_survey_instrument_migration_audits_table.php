<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_instrument_migration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('proyects');
            $table->unsignedBigInteger('baseline_survey_id')->nullable();
            $table->unsignedBigInteger('monitoring_survey_id')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->string('instrument_version', 40);
            $table->string('confirmation', 100);
            $table->json('migrated_counts');
            $table->json('warnings')->nullable();
            $table->string('backup_disk', 40);
            $table->string('backup_path');
            $table->string('backup_sha256', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_instrument_migration_audits');
    }
};
