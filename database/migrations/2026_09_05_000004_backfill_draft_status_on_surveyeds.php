<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('surveyeds')
            ->whereNull('status')
            ->update(['status' => 'BORRADOR']);
    }

    public function down()
    {
        // La normalización de datos históricos no se revierte para no borrar estados válidos.
    }
};
