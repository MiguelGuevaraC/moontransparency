<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class CesarAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new RuntimeException('El usuario de prueba de Cesar solo puede crearse en el entorno local.');
        }

        $role = Rol::whereIn('name', ['Administrador', 'Administrador Moon'])->first()
            ?? Rol::create([
                'name' => 'Administrador',
                'status' => User::STATUS_ACTIVE,
            ]);

        $user = User::withTrashed()->firstOrNew(['username' => 'cesar.admin']);
        $user->fill([
            'type_document' => 'DNI',
            'number_document' => 'CESAR-LOCAL-ADMIN',
            'names' => 'Cesar - Administrador local',
            'password' => env('CESAR_ADMIN_PASSWORD', 'CesarAdmin!2026'),
            'email' => 'cesar.admin@local.test',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => $role->id,
        ]);
        $user->save();

        if ($user->trashed()) {
            $user->restore();
        }
    }
}
