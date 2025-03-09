<?php
namespace App\Http\Controllers;

use App\Http\Requests\AllyRequest\IndexAllyRequest;
use App\Http\Requests\AllyRequest\StoreAllyRequest;
use App\Http\Requests\AllyRequest\UpdateAllyRequest;
use App\Http\Resources\AllyResource;
use App\Http\Resources\AllyWebResource\Web;
use App\Http\Resources\Web\AllyWebResource;
use App\Models\Ally;
use App\Services\AllyService;
use Illuminate\Http\Request;

class AllyController extends Controller
{
    protected $allyService;

    public function __construct(AllyService $allyService)
    {
        $this->allyService = $allyService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/ally",
 *     summary="Obtener información de aliados con filtros y ordenamiento",
 *     tags={"Ally"},
 *     security={{"bearerAuth": {}}},
 *
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="ruc_dni", in="query", description="RUC o DNI del aliado", required=false, @OA\Schema(type="string", maxLength=20)),
 *     @OA\Parameter(name="first_name", in="query", description="Nombre del aliado", required=false, @OA\Schema(type="string", maxLength=100)),
 *     @OA\Parameter(name="last_name", in="query", description="Apellido del aliado", required=false, @OA\Schema(type="string", maxLength=100)),
 *     @OA\Parameter(name="business_name", in="query", description="Nombre comercial del aliado", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="phone", in="query", description="Número de teléfono del aliado", required=false, @OA\Schema(type="string", pattern="^\d{9}$")),
 *     @OA\Parameter(name="email", in="query", description="Correo electrónico del aliado", required=false, @OA\Schema(type="string", format="email")),
 *     @OA\Parameter(name="area_of_interest", in="query", description="Área de interés del aliado", required=false, @OA\Schema(type="string", maxLength=255)),
 *     @OA\Parameter(name="participation_type", in="query", description="Tipo de participación del aliado", required=false, @OA\Schema(type="string", maxLength=255)),
 *     @OA\Response(response=200, description="Lista de aliados", @OA\JsonContent(
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Ally")
 *     )),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="error", type="string", description="Mensaje de error")
 *     ))
 * )
 */

    public function index(IndexAllyRequest $request)
    {

        return $this->getFilteredResults(
            Ally::class,
            $request,
            Ally::filters,
            Ally::sorts,
            AllyResource::class
        );
    }

    /**
     * @OA\Get(
     *     path="/moontransparency/public/api/ally-web",
     *     summary="Obtener información de aliados con filtros y ordenamiento",
     *     tags={"Ally"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="ruc_dni", in="query", description="RUC o DNI del aliado", required=false, @OA\Schema(type="string", maxLength=20)),
     *     @OA\Parameter(name="first_name", in="query", description="Nombre del aliado", required=false, @OA\Schema(type="string", maxLength=100)),
     *     @OA\Parameter(name="last_name", in="query", description="Apellido del aliado", required=false, @OA\Schema(type="string", maxLength=100)),
     *     @OA\Parameter(name="business_name", in="query", description="Nombre comercial del aliado", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="phone", in="query", description="Número de teléfono del aliado", required=false, @OA\Schema(type="string", pattern="^\d{9}$")),
     *     @OA\Parameter(name="email", in="query", description="Correo electrónico del aliado", required=false, @OA\Schema(type="string", format="email")),
     *     @OA\Parameter(name="area_of_interest", in="query", description="Área de interés del aliado", required=false, @OA\Schema(type="string", maxLength=255)),
     *     @OA\Parameter(name="participation_type", in="query", description="Tipo de participación del aliado", required=false, @OA\Schema(type="string", maxLength=255)),
     *     @OA\Response(response=200, description="Lista de aliados", @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Ally")
     *     )),
     *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="error", type="string", description="Mensaje de error")
     *     ))
     * )
     */

    public function list_web(IndexAllyRequest $request)
    {

        return $this->getFilteredResults(
            Ally::class,
            $request,
            Ally::filters,
            Ally::sorts,
            AllyWebResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/ally/{id}",
 *     summary="Obtener detalles de un aliado por ID",
 *     tags={"Ally"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del aliado", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Detalles del aliado", @OA\JsonContent(ref="#/components/schemas/Ally")),
 *     @OA\Response(response=404, description="Aliado no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Aliado no encontrado")))
 * )
 */

    public function show($id)
    {

        $rol = $this->allyService->getAllyById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Aliado No Encontrado',
            ], 404);
        }

        return new AllyResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/ally",
 *     summary="Crear aliado",
 *     tags={"Ally"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 type="object",
 *                 required={"ruc_dni"},
 *                 @OA\Property(property="ruc_dni", type="string", description="RUC o DNI del aliado"),
 *                 @OA\Property(property="first_name", type="string", description="Nombre del aliado"),
 *                 @OA\Property(property="last_name", type="string", description="Apellido del aliado"),
 *                 @OA\Property(property="business_name", type="string", description="Nombre comercial del aliado"),
 *                 @OA\Property(property="phone", type="string", description="Número de teléfono del aliado"),
 *                 @OA\Property(property="email", type="string", format="email", description="Correo electrónico del aliado"),
 *                 @OA\Property(property="area_of_interest", type="string", description="Área de interés del aliado"),
 *                 @OA\Property(property="participation_type", type="string", description="Tipo de participación del aliado"),
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary"),
 *                     description="Imágenes del aliado"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Aliado creado exitosamente",
 *         @OA\JsonContent(ref="#/components/schemas/Ally")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error de validación")
 *         )
 *     )
 * )
 */

    public function store(StoreAllyRequest $request)
    {
        $rol = $this->allyService->createAlly($request->validated());
        return new AllyResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/ally/{id}",
 *     summary="Actualizar un aliado",
 *     tags={"Ally"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"ruc_dni"},
 *                 @OA\Property(property="ruc_dni", type="string", description="RUC o DNI del aliado"),
 *                 @OA\Property(property="first_name", type="string", description="Nombre del aliado"),
 *                 @OA\Property(property="last_name", type="string", description="Apellido del aliado"),
 *                 @OA\Property(property="business_name", type="string", description="Nombre comercial del aliado"),
 *                 @OA\Property(property="phone", type="string", description="Número de teléfono del aliado"),
 *                 @OA\Property(property="email", type="string", format="email", description="Correo electrónico del aliado"),
 *                 @OA\Property(property="area_of_interest", type="string", description="Área de interés del aliado"),
 *                 @OA\Property(property="participation_type", type="string", description="Tipo de participación del aliado"),
 *                 @OA\Property(
 *                     property="images",
 *                     type="array",
 *                     @OA\Items(type="string", format="binary"),
 *                     description="Imágenes del aliado"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Aliado actualizado exitosamente",
 *         @OA\JsonContent(ref="#/components/schemas/Ally")
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error de validación")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Aliado no encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Aliado no encontrado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Error interno",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Error interno del servidor")
 *         )
 *     )
 * )
 */

    public function update(UpdateAllyRequest $request, $id)
    {

        $validatedData = $request->validated();

        $rol = $this->allyService->getAllyById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Aliado No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->allyService->updateAlly($rol, $validatedData);
        return new AllyResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/ally/{id}",
 *     summary="Eliminar un aliado por ID",
 *     tags={"Ally"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Aliado eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Aliado eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Aliado no encontrado")))
 * )
 */

    public function destroy($id)
    {

        $ally = $this->allyService->getAllyById($id);

        if (! $ally) {
            return response()->json([
                'error' => 'Aliado No Encontrado.',
            ], 404);
        }
        $ally = $this->allyService->destroyById($id);

        return response()->json([
            'message' => 'Ally eliminado exitosamente',
        ], 200);
    }

}
