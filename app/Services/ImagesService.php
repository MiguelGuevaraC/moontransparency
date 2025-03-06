<?php
namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagesService
{
    protected $commonService;

    public function __construct(CommonService $commonService)
    {
        $this->commonService = $commonService;
    }
    public function getImageById(int $id): ?Image
    {
        return Image::find($id);
    }

    public function createImage(array $data): array
    {
        $data['user_created_id'] = Auth::id();
        $images                  = $data['images'] ?? [];
        $name_folder             = $data['name_table'] ?? 'otros';
        $ruta                    = "https://develop.garzasoft.com/moontransparency/public";
    
        if (empty($images)) {
            return []; // Retorna un array vacío si no hay imágenes
        }
    
        $savedImages = [];
    
        try {
            // Determinar si las imágenes son un array de archivos (sin claves 'file' y 'name')
            $isFileArray = isset($images[0]) && $images[0] instanceof \Illuminate\Http\UploadedFile;
    
            if ($isFileArray) {
                // Si es un array de archivos, convertirlo al formato adecuado
                $images = array_map(fn($file) => ['file' => $file], $images);
            }
    
            foreach ($images as $imageData) {
                $hasFile = isset($imageData['file']) && $imageData['file'] instanceof \Illuminate\Http\UploadedFile;
                $hasName = isset($imageData['name']) && is_string($imageData['name']) && !empty(trim($imageData['name']));
    
                // Si no tiene ni file ni name, se ignora
                if (!$hasFile && !$hasName) {
                    continue;
                }
    
                // Si hay name pero no hay file, se ignora
                if ($hasName && !$hasFile) {
                    continue;
                }
    
                // Validar tipos de archivo y tamaño (según rules)
                if ($hasFile) {
                    $allowedMimeTypes = ['jpeg', 'jpg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                    $maxSize          = 4096 * 1024; // 4MB en bytes
    
                    $fileMimeType = $imageData['file']->getClientOriginalExtension();
                    $fileSize     = $imageData['file']->getSize();
    
                    if (!in_array($fileMimeType, $allowedMimeTypes) || $fileSize > $maxSize) {
                        continue;
                    }
                }
    
                // Asignar un nombre válido si no tiene 'name'
                $file               = $imageData['file'];
                $name               = $hasName ? Str::slug($imageData['name']) : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $extension          = $file->getClientOriginalExtension();
                $data['name_image'] = $name;
                $fileName           = Str::uuid() . ".{$extension}"; 
                $filePath           = $file->storeAs($name_folder, $fileName, 'public');
                $data['route']      = $ruta . Storage::url($filePath);
                $Image              = Image::create($data);
    
                $savedImages[] = $Image;
            }
        } catch (\Exception $e) {
            Log::error('Error al subir imagen: ' . $e->getMessage());
            return [];
        }
    
        return $savedImages;
    }
    

    public function updateImage(Image $image, array $data): ?Image
    {

        $images      = $data['images'] ?? [];
        $name_folder = $data['name_table'] ?? 'otros';
        $ruta        = "https://develop.garzasoft.com/moontransparency/public";

        try {
            if (! empty($images)) {
                // Eliminar archivo anterior si existe
                if (! empty($image->route)) {
                    $oldFilePath = str_replace($ruta . '/storage/', '', $image->route);
                    Storage::disk('public')->delete($oldFilePath);
                }

                foreach ($images as $imageData) {
                    if (! isset($imageData['file'], $imageData['name']) || ! $imageData['file'] instanceof \Illuminate\Http\UploadedFile) {
                        continue; // Si falta el archivo o el nombre, omitir esta imagen
                    }

                    $file               = $imageData['file'];
                    $name               = Str::slug($imageData['name']); // Normalizamos el nombre
                    $extension          = $file->getClientOriginalExtension();
                    $data['name_image'] = $name;
                    $fileName           = Str::uuid() . ".{$extension}"; // Evitamos duplicados con UUID
                    $filePath           = $file->storeAs($name_folder, $fileName, 'public');
                    $data['route']      = $ruta . Storage::url($filePath);
                }
            }

            $image->update($data);
        } catch (\Exception $e) {
            Log::error('Error al actualizar imagen: ' . $e->getMessage());
            return null;
        }

        return $image;
    }
    public function updateImagePartial(Image $image, array $data): ?Image
    {

        $ruta = "https://develop.garzasoft.com/moontransparency/public";

        try {
            $changes = array_filter($data, fn($value, $key) => $image->getAttribute($key) !== $value, ARRAY_FILTER_USE_BOTH);

            // Si hay cambios, actualizar el modelo
            if (! empty($changes)) {
                $image->update($changes);
            }
        } catch (\Exception $e) {
            Log::error('Error al actualizar imagen: ' . $e->getMessage());
            return null;
        }

        return $image;
    }

    public function destroyById($id)
    {
        $Image = Image::find($id);
        if (! $Image) {
            return false;
        }
        return $Image->delete(); // Devuelve true si la eliminación fue exitosa
    }

}
