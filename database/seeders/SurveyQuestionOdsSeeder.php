<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurveyQuestionOdsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Insertar preguntas (IDs desde 100 en adelante)
        DB::table('survey_questions')->insert([
            [
                'id' => 100,
                'survey_id' => 1,
                'question_text' => '¿Qué acciones realiza su organización para reducir la pobreza?',
                'question_type' => 'multiple_choice',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 101,
                'survey_id' => 1,
                'question_text' => '¿Qué medidas aplica para garantizar educación de calidad?',
                'question_type' => 'multiple_choice',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 102,
                'survey_id' => 1,
                'question_text' => '¿Cómo fomenta la igualdad de género en su entorno laboral?',
                'question_type' => 'scale',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 103,
                'survey_id' => 1,
                'question_text' => '¿Qué iniciativas desarrolla para promover energías limpias?',
                'question_type' => 'multiple_choice',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'id' => 104,
                'survey_id' => 1,
                'question_text' => '¿Qué prácticas implementa para garantizar producción y consumo responsables?',
                'question_type' => 'multiple_choice',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // Insertar relaciones con ODS
        DB::table('survey_question_ods')->insert([
            ['survey_question_id' => 100, 'ods_id' => 1,  'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 100, 'ods_id' => 3,  'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 101, 'ods_id' => 4,  'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 102, 'ods_id' => 5,  'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 102, 'ods_id' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 103, 'ods_id' => 7,  'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 104, 'ods_id' => 12, 'created_at' => $now, 'updated_at' => $now],
            ['survey_question_id' => 104, 'ods_id' => 13, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
