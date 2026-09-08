<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_cleanup_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('survey_id');
            $table->foreignId('performed_by')->constrained('users');
            $table->text('reason');
            $table->string('confirmation', 100);
            $table->json('deleted_counts');
            $table->string('backup_disk', 50);
            $table->string('backup_path', 500);
            $table->char('backup_sha256', 64);
            $table->timestamps();

            $table->index(['survey_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_cleanup_audits');
    }
};
