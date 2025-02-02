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

        // Verificar si se han subido imágenes y si es un array
        if (isset($data['imagesave']) && is_array($data['imagesave'])) {
            $imagePaths = []; // Un array para almacenar las rutas de las imágenes subidas
            $i          = 1;
            foreach ($data['imagesave'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $timestamp = now()->format('Ymd_His');
                    $extension = $image->getClientOriginalExtension();
                    $fileName  = "{$object->id}_{$i}_{$timestamp}.{$extension}";
                    $filePath  = $image->storeAs($name_folder, $fileName, 'public');
                    $i++;
                    // Guardar la ruta completa de la imagen
                    $imagePaths[] = $ruta . Storage::url($filePath);
                }
            }

            // Verificar que $imagePaths no esté vacío y que sea un array
            if (! empty($imagePaths) && is_array($imagePaths)) {
                // Convertir el array de rutas a una cadena separada por comas
                $imagePathsString = implode(',', $imagePaths);
                $object->update(['images' => $imagePathsString]); // Guardar las rutas como una cadena separada por comas
            }
        }
    }

    public function update_photo(array $data, Object $object, String $name_folder): string
    {
        $ruta = "https://develop.garzasoft.com/moontransparency/public";

        // Verificar si existe una ruta de imágenes anteriores y eliminarlas
        if (! empty($object->images)) {
            // Obtener las rutas anteriores separadas por comas
            $oldPaths = explode(',', $object->images);

            // Eliminar las imágenes previas
            foreach ($oldPaths as $oldPath) {
                $oldPath = str_replace($ruta . '/storage/', '', $oldPath); // Limpiar la ruta

                // Verificar si el archivo existe y eliminarlo
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

                          // Verificar si se proporcionaron nuevas imágenes
        $imagePaths = []; // Un array para almacenar las rutas de las imágenes subidas
        if (isset($data['imagesave']) && is_array($data['imagesave'])) {
            $i = 1; // Inicializar el contador de imágenes
            foreach ($data['imagesave'] as $image) {
                if ($image instanceof \Illuminate\Http\UploadedFile) {
                    $timestamp = now()->format('Ymd_His');
                    $extension = $image->getClientOriginalExtension();
                    $fileName  = "{$object->id}_{$i}_{$timestamp}.{$extension}";
                    $filePath  = $image->storeAs($name_folder, $fileName, 'public');
                    $i++;

                    // Guardar la ruta completa de la imagen
                    $imagePaths[] = $ruta . Storage::url($filePath);
                }
            }
        }

        // Si se subieron nuevas imágenes, actualizar las rutas
        if (! empty($imagePaths)) {
            // Convertir el array de rutas a una cadena separada por comas
            $imagePathsString = implode(',', $imagePaths);

            // Devolver la cadena de rutas
            return $imagePathsString;
        }

        // Si no se subieron nuevas imágenes, devolver lo anterior
        return $object->images;
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
            'data_old'    => json_encode(['old' => $oldData]),
            'data_new'    => json_encode(['new' => $newData]),
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->header('User-Agent'),
            'user_id'     => Auth::id() ?? null, // Obtiene el ID del usuario autenticado
        ]);
    }

}
