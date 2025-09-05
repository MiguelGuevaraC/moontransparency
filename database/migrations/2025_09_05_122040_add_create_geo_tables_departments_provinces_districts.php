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
        // 1) departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ubigeo_code', 2)->unique();
            $table->timestamps();
        });

        // 2) provinces (referencia a departments)
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // nullable + constrained to departments
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->string('ubigeo_code', 4)->unique();
            $table->timestamps();
        });

        // 3) districts (referencia a provinces)
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cadena');
            // nullable + constrained to provinces
            $table->foreignId('province_id')->nullable()->constrained('provinces');
            $table->string('ubigeo_code', 6)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Borrar en orden inverso para evitar conflictos de FK
        Schema::dropIfExists('districts');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('departments');
    }
};
