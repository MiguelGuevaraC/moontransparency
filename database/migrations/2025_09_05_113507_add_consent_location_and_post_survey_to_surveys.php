<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->boolean('is_consentimiento')->default(false)->after('description');
            $table->json('data_ubicacion')->nullable()->after('is_consentimiento');
            $table->foreignId('post_survey_id')->nullable()->constrained('surveys');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropForeign(['post_survey_id']);
            $table->dropColumn('post_survey_id');
            $table->dropColumn('data_ubicacion');
            $table->dropColumn('is_consentimiento');
        });
    }
};
