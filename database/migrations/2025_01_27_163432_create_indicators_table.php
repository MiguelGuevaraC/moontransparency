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
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->string('indicator_name', 255)->nullable();
            $table->decimal('target_value', 15, 2)->nullable();
            $table->decimal('progress_value', 15, 2)->nullable();
            $table->string('unit', 255)->nullable();
            $table->foreignId('proyect_id')->nullable()->unsigned()->constrained('proyects');
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
        Schema::dropIfExists('indicators');
    }
};
