<?php
namespace App\Http\Controllers;

use App\Http\Requests\DonationRequest\IndexDonationRequest;
use App\Http\Requests\DonationRequest\StoreDonationRequest;
use App\Http\Requests\DonationRequest\UpdateDonationRequest;
use App\Http\Resources\DonationResource;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    protected $donationService;

    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/donation",
 *     summary="Obtener información de donaciones con filtros y ordenamiento",
 *     tags={"Donation"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name", in="query", description="Filtrar por nombre de donation", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="proyect_id", in="query", description="Filtrar por ID del proyecto", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="activity_id", in="query", description="Filtrar por ID de actividad", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="date_donation", in="query", description="Filtrar por fecha de donation", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="ally_id", in="query", description="Filtrar por ID del aliado", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="details", in="query", description="Filtrar por detalles de la donation", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="contribution_type", in="query", description="Filtrar por tipo de contribución", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="amount", in="query", description="Filtrar por monto de la donation", required=false, @OA\Schema(type="number", format="float")),
 *     @OA\Parameter(name="images", in="query", description="Filtrar por evidencia de la donation", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de donaciones", @OA\JsonContent(ref="#/components/schemas/Donation")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexDonationRequest $request)
    {

        return $this->getFilteredResults(
            Donation::class,
            $request,
            Donation::filters,
            Donation::sorts,
            DonationResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/donation/{id}",
 *     summary="Obtener detalles de un Donación por ID",
 *     tags={"Donation"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del donation", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación encontrado", @OA\JsonContent(ref="#/components/schemas/Donation")),
 *     @OA\Response(response=404, description="Donación No Encontrada", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Donación No Encontrada")))
 * )
 */

    public function show($id)
    {

        $rol = $this->donationService->getDonationById($id);

        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrada',
            ], 404);
        }

        return new DonationResource($rol);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/donation",
 *     summary="Crear Donation",
 *     tags={"Donation"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/DonationRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación creada exitosamente", @OA\JsonContent(ref="#/components/schemas/Donation")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreDonationRequest $request)
    {
        $rol = $this->donationService->createDonation($request->validated());
        return new DonationResource($rol);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/donation/{id}",
 *     summary="Actualizar un donation",
 *     tags={"Donation"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
  *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/DonationRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Donación actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/Donation")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Donación No Encontrada", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación No Encontrada"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateDonationRequest $request, $id)
    {

        $validatedData = $request->validated();

        $rol = $this->donationService->getDonationById($id);
        if (! $rol) {
            return response()->json([
                'error' => 'Donación No Encontrada',
            ], 404);
        }

        $updatedCompany = $this->donationService->updateDonation($rol, $validatedData);
        return new DonationResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/donation/{id}",
 *     summary="Eliminar un Donación por ID",
 *     tags={"Donation"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Donación eliminada", @OA\JsonContent(@OA\Property(property="message", type="string", example="Donación eliminada exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Donación No Encontrada"))),

 * )
 */

    public function destroy($id)
    {

        $activity = $this->donationService->getDonationById($id);

        if (! $activity) {
            return response()->json([
                'error' => 'Donación No Encontrada.',
            ], 404);
        }
        $activity = $this->donationService->destroyById($id);

        return response()->json([
            'message' => 'Donación eliminada exitosamente',
        ], 200);
    }

}
