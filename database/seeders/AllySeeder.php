<?php
namespace Database\Seeders;

use App\Models\Ally;
use App\Models\Ods;
use App\Models\Proyect;
use App\Models\ProyectOds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        Ally::create([
            'ruc_dni'            => '12345678901',  // RUC o DNI
            'first_name'         => 'Juan',         // Nombre del aliado
            'last_name'          => 'Pérez',        // Apellido del aliado
            'business_name'      => 'Tecnologías Avanzadas S.A.', // Nombre comercial
            'phone'              => '987654321',    // Número de teléfono
            'email'              => 'juan@empresa.com',  // Correo electrónico
            'area_of_interest'   => 'Educación',  // Área de interés
            'participation_type' => 'Voluntario',      // Tipo de participación
            'images'             => '',
        ]);
    }
}
