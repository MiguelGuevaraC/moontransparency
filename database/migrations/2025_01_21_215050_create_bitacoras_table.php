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

        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id(); // Campo para ID auto incremental

            $table->unsignedBigInteger('record_id')->nullable(); // ID del registro afectado
            $table->text('description')->nullable();             // Descripción detallada de la actividad
            $table->string('action')->nullable();                // Acción realizada (create, update, delete)
            $table->string('table_name')->nullable();            // Nombre de la tabla afectada
            $table->json('data_old')->nullable();                // Datos en formato JSON (para almacenar datos antiguos)
            $table->json('data_new')->nullable();                // Datos en formato JSON (para almacenar datos nuevos)
            $table->ipAddress('ip_address')->nullable();         // Dirección IP del usuario
            $table->text('user_agent')->nullable();              // Información sobre el navegador o dispositivo
            $table->foreignId('user_id')->nullable()->unsigned()->constrained('users');
            $table->timestamps(); // timestamps para created_at y updated_at
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
        Schema::dropIfExists('bitacoras');
    }
};
