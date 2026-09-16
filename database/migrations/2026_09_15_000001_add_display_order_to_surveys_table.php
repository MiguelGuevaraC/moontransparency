<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->unsignedInteger('display_order')
                ->nullable()
                ->after('status')
                ->index();
        });

        DB::table('surveys')
            ->whereNull('display_order')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($survey) {
                DB::table('surveys')
                    ->where('id', $survey->id)
                    ->update(['display_order' => $survey->id]);
            });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropIndex(['display_order']);
            $table->dropColumn('display_order');
        });
    }
};
