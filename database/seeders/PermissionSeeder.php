<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Permisos para "Usuarios"
        Permission::create(['name' => 'Leer', 'type' => 'Usuarios']);
        Permission::create(['name' => 'Crear', 'type' => 'Usuarios']);
        Permission::create(['name' => 'Editar', 'type' => 'Usuarios']);
        Permission::create(['name' => 'Eliminar', 'type' => 'Usuarios']);

        // Permisos para "Roles"
        Permission::create(['name' => 'Leer Roles', 'type' => 'Roles']);
        Permission::create(['name' => 'Asignar Permiso', 'type' => 'Roles']);
        Permission::create(['name' => 'Revocar Permiso', 'type' => 'Roles']);
    }
}
