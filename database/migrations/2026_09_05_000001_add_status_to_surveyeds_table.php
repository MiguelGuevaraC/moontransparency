<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('surveyeds', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->after('respondent_id')->index();
        });
    }

    public function down()
    {
        Schema::table('surveyeds', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
