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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();                           // Crea el campo 'id' como clave primaria autoincremental
            $table->string('name')->nullable();     // Crea el campo 'name'
            $table->date('start_date')->nullable(); // Crea el campo 'start_date'
            $table->date('end_date')->nullable();   // Crea el campo 'end_date'

            $table->text('objective')->nullable();                  // Crea el campo 'objective'
            $table->decimal('total_amount', 15, 2)->nullable();     // Crea el campo 'total_amount' con 15 dígitos en total y 2 decimales
            $table->decimal('collected_amount', 15, 2)->nullable(); // Crea el campo 'collected_amount' con el mismo formato
            $table->string('status', 100)->default('Activo')->nullable();
            $table->foreignId('proyect_id')->nullable()->unsigned()->constrained('proyects');
            $table->timestamps(); // Crea los campos 'created_at' y 'updated_at'
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
        Schema::dropIfExists('activities');
    }
};
