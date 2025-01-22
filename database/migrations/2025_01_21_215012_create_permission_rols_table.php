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
        Schema::create('permission_rols', function (Blueprint $table) {
            $table->id();
            $table->string('name_permission')->nullable();
            $table->string('name_rol')->nullable();
            $table->string('type')->nullable();
            $table->foreignId('rol_id')->nullable()->unsigned()->constrained('rols');
            $table->foreignId('permission_id')->nullable()->unsigned()->constrained('permissions');
            $table->softDeletes();
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
        Schema::dropIfExists('permission_rols');
    }
};
