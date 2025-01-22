<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeders extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('users')->insert([
            'id'              => 1, // El ID del usuario
            'type_document'   => 'DNI',
            'number_document' => '000000',
            'names'           => 'ADMINISTRADOR',
            'username'        => 'admin',                // Correo electrónico
            'password'        => Hash::make('adminmoon'), // Contraseña hasheada usando Hash::make
            'status'          => "Activo",
            'address'         => null,
            'phone'           => '99999999',
            'email'           => 'prueba@gmail.com',
            'rol_id'          => 1,

        ]);

    }
}
