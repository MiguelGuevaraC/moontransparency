<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique();
            $table->string('contract_version', 20);
            $table->string('device_id', 100);
            $table->char('request_hash', 64);
            $table->string('status', 30)->default('PROCESSING');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offline_sync_participations', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_participation_id')->unique();
            $table->string('device_id', 100);
            $table->foreignId('surveyed_id')->constrained('surveyeds')->cascadeOnDelete();
            $table->timestamp('last_client_updated_at');
            $table->char('last_payload_hash', 64);
            $table->timestamps();

            $table->index(
                ['surveyed_id', 'last_client_updated_at'],
                'offline_participation_surveyed_updated_index'
            );
        });

        Schema::create('offline_sync_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offline_sync_participation_id')
                ->constrained('offline_sync_participations')
                ->cascadeOnDelete();
            $table->uuid('client_measurement_id')->unique();
            $table->foreignId('surveyed_measurement_id')
                ->constrained('surveyed_measurements')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->timestamps();

            $table->unique(
                ['offline_sync_participation_id', 'day_number'],
                'offline_sync_participation_day_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sync_measurements');
        Schema::dropIfExists('offline_sync_participations');
        Schema::dropIfExists('offline_sync_batches');
    }
};
