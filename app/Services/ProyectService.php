<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Proyect;
use App\Models\Proyect;

class ProyectService
{

    public function getProyectById(int $id): ?Proyect
    {
        return Proyect::find($id);
    }

    public function createProyect(array $data): Proyect
    {
        return Proyect::create($data);
    }

    public function updateProyect($Proyect, array $data)
    {
        $Proyect->update($data);
        return $Proyect;
    }

    public function destroyById($id)
    {
        // Encuentra y elimina el registro si existe
        return Proyect::find($id)?->delete() ?? false;
    }

}
