<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Proyect;
use App\Models\Proyect;

class ProyectService
{

    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }


    public function getProyectById(int $id): ?Proyect
    {
        return Proyect::find($id);
    }

    public function createProyect(array $data): Proyect
    {

        $data['imagesave']=$data['images'];
        $data['images']=null;
        $proyect = Proyect::create($data);
        if ($proyect) {
            $this->commonService->store_photo($data, $proyect, name_folder: 'proyects');
        }
        return $proyect;
    }
    


    public function updateProyect(Proyect $proyect, array $data): Proyect
    {
        $data['imagesave']=$data['images'];
        $data['images'] = $this->commonService->update_photo($data, $proyect, 'proyects');
        $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        // Encuentra y elimina el registro si existe
        return Proyect::find($id)?->delete() ?? false;
    }

}
