<?php
namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CommonService
{

    public function store_photo(array $data, Object $object, String $name_folder)
    {
        $ruta = "https://develop.garzasoft.com/moontransparency/public";
        if (isset($data['route']) && $data['route'] instanceof \Illuminate\Http\UploadedFile) {
            $timestamp = now()->format('Ymd_His');
            $extension = $data['route']->getClientOriginalExtension();
            $fileName  = "{$object->id}_{$timestamp}.{$extension}";
            $filePath  = $data['route']->storeAs($name_folder, $fileName, 'public');
            $object->update(['route' => $ruta . Storage::url($filePath)]);
        }
    }

    public function update_photo(array $data, Object $object, String $name_folder): string
    {
        $ruta = "https://develop.garzasoft.com/moontransparency/public";

        // Verificar si existe una ruta de foto anterior y eliminarla
        if (! empty($object->route)) {
            $oldPath = str_replace($ruta . '/storage/', '', $object->route);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Subir y guardar la nueva imagen si se proporciona
        if (isset($data['route']) && $data['route'] instanceof \Illuminate\Http\UploadedFile) {
            $timestamp = now()->format('Ymd_His');
            $extension = $data['route']->getClientOriginalExtension();
            $fileName  = "{$object->id}_{$timestamp}.{$extension}";
            $filePath  = $data['route']->storeAs($name_folder, $fileName, 'public');
            $ruta      = $ruta . Storage::url($filePath);
        }
        return $ruta;
    }

    public function logActivity(
        string $action,
        string $tableName,
        int $recordId,
        string $description,
        array $oldData = [],
        array $newData = []
    ): Bitacora {
        return Bitacora::create([
            'action'      => $action,
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'description' => $description,
            'data_old'        => json_encode(['old' => $oldData]),
            'data_new'        => json_encode(['new' => $newData]),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->header('User-Agent'),
            'user_id'     => Auth::id() ?? null, // Obtiene el ID del usuario autenticado
        ]);
    }

}
