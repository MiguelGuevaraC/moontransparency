<?php
namespace Database\Seeders;

use App\Models\Ods;
use App\Models\Proyect;
use App\Models\ProyectOds;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        $project=Proyect::create([
            'name' => 'Proyecto de Energía Renovable',
            'type' => 'Energía',
            'status' => 'Activo',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'location' => "(-77.032, -12.045)",
            'images' => null,
            'description' => 'Proyecto para implementar fuentes de energía renovable en comunidades rurales.',
            'budget_estimated' => 500000,
            'nro_beneficiaries' => 5000,
            'impact_initial' => '0',
            'impact_final' => '50',
            'created_at' => now(),
        ]);
        $ods1 = Ods::where('code', 'ODS07')->first(); // Energía asequible y no contaminante
        $ods2 = Ods::where('code', 'ODS13')->first(); // Acción por el clima

        ProyectOds::create(['proyect_id' => $project->id,'ods_id' => $ods1->id,]);
        ProyectOds::create(['proyect_id' => $project->id,'ods_id' => $ods2->id,]);
    }
}
