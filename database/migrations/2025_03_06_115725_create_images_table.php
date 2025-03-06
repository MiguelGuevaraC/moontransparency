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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->text('name_table')->nullable();
            $table->text('name_image')->nullable();
            $table->text('route')->nullable();
            $table->foreignId('proyect_id')->nullable()->unsigned()->constrained('proyects');
            $table->foreignId('ally_id')->nullable()->unsigned()->constrained('allies');
            $table->foreignId('donation_id')->nullable()->unsigned()->constrained('donations');
            $table->foreignId('survey_id')->nullable()->unsigned()->constrained('surveys');
            $table->foreignId('user_created_id')->nullable()->unsigned()->constrained('users');
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
        Schema::dropIfExists('images');
    }
};
