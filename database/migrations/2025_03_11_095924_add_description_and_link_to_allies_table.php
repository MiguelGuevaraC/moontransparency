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
        Schema::table('allies', function (Blueprint $table) {
            $table->text('description')->nullable()->after('id');
            $table->text('link')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allies', function (Blueprint $table) {
            $table->dropColumn(['description', 'link']);
        });
    }
};
