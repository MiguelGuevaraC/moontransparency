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
        Schema::create('proyects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->string('type', 100)->nullable();
            $table->string('status', 100)->default('Activo')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('location')->nullable();
            $table->text('images')->nullable();
            $table->text('description')->nullable();
            $table->decimal('budget_estimated', 15, 2)->nullable();
            $table->integer('nro_beneficiaries')->nullable();
            $table->string('impact_initial')->nullable();
            $table->string('impact_final')->nullable();
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
        Schema::dropIfExists('proyects');
    }
};
