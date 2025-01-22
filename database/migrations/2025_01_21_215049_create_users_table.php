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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('type_document')->nullable();; // Tipo de documento
            $table->string('number_document'); // Número de documento
            $table->string('names')->nullable();; // Nombres del usuario
            $table->string('username')->unique()->nullable();; // Nombre de usuario único
            $table->string('password'); // Contraseña
            $table->string('address')->nullable(); // Dirección (opcional)
            $table->string('phone')->nullable(); // Teléfono (opcional)
            $table->string('email')->unique()->nullable(); // Correo electrónico único (opcional)
            $table->string('status')->default('Activo'); // Estado (por defecto 'Activo')
            $table->rememberToken();
            $table->foreignId('rol_id')->nullable()->unsigned()->constrained('rols');
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
        Schema::dropIfExists('users');
    }
};
