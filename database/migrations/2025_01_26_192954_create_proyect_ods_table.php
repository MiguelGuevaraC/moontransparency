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
        Schema::create('proyect_ods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyect_id')->nullable()->unsigned()->constrained('proyects');
            $table->foreignId('ods_id')->nullable()->unsigned()->constrained('ods');
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
        Schema::dropIfExists('proyect_ods');
    }
};
