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
        Schema::create('allies', function (Blueprint $table) {
            $table->id();
            $table->string('ruc_dni', 20);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('business_name');
            $table->string('phone', 15);
            $table->string('email');
            $table->text('area_of_interest');
            $table->string('participation_type', 255);
            $table->string('images')->nullable();
            $table->softDeletes(); // Para manejar registros eliminados de forma lógica
            $table->timestamps();  // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('allies');
    }
};
