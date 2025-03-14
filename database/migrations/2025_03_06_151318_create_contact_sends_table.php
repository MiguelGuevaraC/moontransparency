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
        Schema::create('contact_sends', function (Blueprint $table) {
            $table->id();
            $table->string('name_contact')->nullable();
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('sender_email')->nullable();
            $table->string('status')->default('Generado');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
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
        Schema::dropIfExists('contact_sends');
    }
};
