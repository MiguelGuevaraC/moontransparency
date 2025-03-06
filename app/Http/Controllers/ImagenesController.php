<?php
namespace App\Http\Controllers;

use App\Http\Requests\ImagenesRequest\IndexImagenesRequest;
use App\Http\Requests\ImagenesRequest\StoreImagenesRequest;
use App\Http\Requests\ImagenesRequest\UpdateImagenesRequest;
use App\Http\Resources\ImagenResource;
use App\Models\Image;
use App\Services\ImagesService;
use Illuminate\Http\Request;

class ImagenesController extends Controller
{
    protected $imagesService;

    public function __construct(ImagesService $imagesService)
    {
        $this->imagesService = $imagesService;
    }

/**
 * @OA\Get(
 *     path="/api/images-list",
 *     summary="Obtener información de Imágenes con filtros y ordenamiento",
 *     tags={"Imagenes-Controller"},
 *     security={{"bearerAuth": {}}},

 *     @OA\Parameter(name="name_table", in="query", description="Filtrar por nombre de la tabla", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="proyect_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="task_id", in="query", description="Filtrar por ID de la tarea", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="spring_id", in="query", description="Filtrar por ID del sprint", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="comment_id", in="query", description="Filtrar por ID del comentario", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="user_created_id", in="query", description="Filtrar por ID del usuario creador", required=false, @OA\Schema(type="string")),

 *     @OA\Response(response=200, description="Lista de Imágenes", @OA\JsonContent(ref="#/components/schemas/Imagenes")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function list(IndexImagenesRequest $request)
    {

        return $this->getFilteredResults(
            Image::class,
            $request,
            Image::filters,
            Image::sorts,
            ImagenResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/api/images/{id}",
 *     summary="Obtener detalles de un Imagenes por ID",
 *     tags={"Imagenes-Controller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del Imagenes", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Imagen encontrado", @OA\JsonContent(ref="#/components/schemas/Imagenes")),
 *     @OA\Response(response=404, description="Imagen no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Imagen no encontrado")))
 * )
 */

    public function show($id)
    {

        $images = $this->imagesService->getImageById($id);

        if (! $images) {
            return response()->json([
                'error' => 'Imagen No Encontrado',
            ], 404);
        }

        return new ImagenResource($images);
    }

/**
 * @OA\Post(
 *     path="/api/images",
 *     summary="Crear Imagenes",
 *     tags={"Imagenes-Controller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ImagenesRequest")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Imagen creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Imagenes")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreImagenesRequest $request)
    {
        $images = $this->imagesService->createImage($request->validated());

        // Si `createImage()` devuelve `null`, retornar una respuesta vacía
        if (! $images) {
            return ImagenResource::collection(collect());
        }

        return ImagenResource::collection(collect($images));
    }

/**
 * @OA\POST(
 *     path="/api/images/{id}",
 *     summary="Actualizar un Imagenes",
 *     tags={"Imagenes-Controller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ImagenesRequest")
 *         )
 *     ),
 *     @OA\Response(response=201, description="Imagen actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Imagenes")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Imagen no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Imagen no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateImagenesRequest $request, $id)
    {

        $validatedData = $request->validated();

        $images = $this->imagesService->getImageById($id);
        if (! $images) {
            return response()->json([
                'error' => 'Imagen No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->imagesService->updateImagePartial($images, $validatedData);
        return new ImagenResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/api/images/{id}",
 *     summary="Eliminar un Imagenes por ID",
 *     tags={"Imagenes-Controller"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Imagen eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Imagen eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Imagen no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $images = $this->imagesService->getImageById($id);

        if (! $images) {
            return response()->json([
                'error' => 'Imagen No Encontrado.',
            ], 404);
        }
        $images = $this->imagesService->destroyById($id);

        return response()->json([
            'message' => 'Imagen eliminado exitosamente',
        ], 200);
    }
}
