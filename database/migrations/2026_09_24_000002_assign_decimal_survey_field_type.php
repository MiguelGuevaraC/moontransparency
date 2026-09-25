<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('survey_questions')
            ->where('calculator_value_type', 'number')
            ->whereIn('calculator_unit', ['kg', 'g', 'km', 'degree'])
            ->whereIn('type_field', ['NUMERICO', 'NUMERO', 'NUMBER'])
            ->update(['type_field' => 'DECIMAL']);
    }

    public function down(): void
    {
        DB::table('survey_questions')
            ->where('type_field', 'DECIMAL')
            ->where('calculator_value_type', 'number')
            ->whereIn('calculator_unit', ['kg', 'g', 'km', 'degree'])
            ->update(['type_field' => 'NUMERICO']);
    }
};
