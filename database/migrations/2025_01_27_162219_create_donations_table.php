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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyect_id')->nullable()->unsigned()->constrained('proyects');
            $table->foreignId('activity_id')->nullable()->unsigned()->constrained('activities');
            $table->dateTime('date_donation')->nullable();
            $table->foreignId('ally_id')->nullable()->unsigned()->constrained('allies');
            $table->text('details')->nullable();
            $table->string('contribution_type', 255)->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('evidence')->nullable();
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
        Schema::dropIfExists('donations');
    }
};
