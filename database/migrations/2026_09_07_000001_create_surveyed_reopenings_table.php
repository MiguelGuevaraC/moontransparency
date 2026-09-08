<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveyed_reopenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surveyed_id')->constrained('surveyeds')->cascadeOnDelete();
            $table->string('previous_status', 30);
            $table->timestamp('previous_completed_at')->nullable();
            $table->text('reason');
            $table->foreignId('reopened_by')->constrained('users');
            $table->timestamps();

            $table->index(['surveyed_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveyed_reopenings');
    }
};
