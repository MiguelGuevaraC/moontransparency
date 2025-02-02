<?php
namespace App\Services;

use App\Models\Ods;
use App\Models\Proyect;
use App\Models\ProyectOds;

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
        $data['imagesave'] = isset($data['images']) ? $data['images'] : null;
        $data['images']    = null;
        $proyect           = Proyect::create($data);
        if ($proyect) {
            $this->commonService->store_photo($data, $proyect, name_folder: 'proyects');
        }
        if (isset($data['ods'])) {
            $currentOds = $proyect->ods()->pluck('ods.id')->toArray();
            ProyectOds::where('proyect_id', $proyect->id)->whereNotIn('ods_id', $data['ods'])->delete();
            foreach ($data['ods'] as $od) {
                $proyect->ods()->firstOrCreate(['ods.id' => $od]);
            }
        }
        return $proyect;
    }

    public function updateProyect(Proyect $proyect, array $data): Proyect
    {
        // Verificar si 'images' existe en $data antes de usarlo
        if (isset($data['images'])) {
            $data['imagesave'] = $data['images'];
            $data['images']    = $this->commonService->update_photo($data, $proyect, 'proyects');
        } else {
            // Si 'images' no existe, evitar errores
            $data['imagesave'] = null;
            $data['images']    = $proyect->images ?? null;
        }

        // Actualizar los datos del proyecto
        $proyect->update($data);

        if (isset($data['ods'])) {
            $currentOds = $proyect->ods()->pluck('ods.id')->toArray();
            ProyectOds::where('proyect_id', $proyect->id)->whereNotIn('ods_id', $data['ods'])->delete();
            foreach ($data['ods'] as $od) {
                $proyect->ods()->firstOrCreate(['ods.id' => $od]);
            }
        }

        return $proyect;
    }

    public function destroyById($id)
    {
        // Encuentra y elimina el registro si existe
        return Proyect::find($id)?->delete() ?? false;
    }

}
