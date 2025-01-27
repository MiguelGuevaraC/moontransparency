<?php
namespace App\Services;

use App\Models\Permission;
use App\Models\Permission_Ally;
use App\Models\Ally;

class AllyService
{

    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }


    public function getAllyById(int $id): ?Ally
    {
        return Ally::find($id);
    }

    public function createAlly(array $data): Ally
    {

        $data['imagesave']=$data['images'];
        $data['images']=null;
        $ally = Ally::create($data);
        if ($ally) {
            $this->commonService->store_photo($data, $ally, name_folder: 'allies');
        }
        return $ally;
    }
    


    public function updateAlly(Ally $ally, array $data): Ally
    {
        $data['imagesave']=$data['images'];
        $data['images'] = $this->commonService->update_photo($data, $ally, 'allies');
        $ally->update($data);
        return $ally;
    }

    public function destroyById($id)
    {
        // Encuentra y elimina el registro si existe
        return Ally::find($id)?->delete() ?? false;
    }

}
