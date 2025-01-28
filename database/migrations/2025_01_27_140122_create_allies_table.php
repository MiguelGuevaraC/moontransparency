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
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('business_name')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->text('area_of_interest')->nullable();
            $table->string('participation_type', 255)->nullable();
            $table->string('images')->nullable();
           
            $table->timestamps();  // created_at y updated_at
            $table->softDeletes(); // Para manejar registros eliminados de forma lógica
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
