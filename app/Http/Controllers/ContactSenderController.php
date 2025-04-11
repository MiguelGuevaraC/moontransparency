<?php
namespace App\Http\Controllers;

use App\Http\Requests\ContactSenderRequest\IndexContactSenderRequest;
use App\Http\Requests\ContactSenderRequest\StoreContactSenderRequest;
use App\Http\Requests\ContactSenderRequest\UpdateContactSenderRequest;
use App\Http\Resources\ContactSendResource;
use App\Models\ContactSend;
use App\Services\ContactSenderService;
use Illuminate\Http\Request;

class ContactSenderController extends Controller
{
    protected $contactsenderService;

    public function __construct(ContactSenderService $contactsenderService)
    {
        $this->contactsenderService = $contactsenderService;
    }

/**
 * @OA\Get(
 *     path="/moontransparency/public/api/contactsend",
 *     summary="Obtener información de contactos con filtros y ordenamiento",
 *     tags={"ContactSend"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="name_contact", in="query", description="Filtrar por nombre de contacto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="subject", in="query", description="Filtrar por asunto", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="description", in="query", description="Filtrar por descripción", required=false, @OA\Schema(type="string")),
 *     @OA\Parameter(name="contact_email", in="query", description="Filtrar por email de contacto", required=false, @OA\Schema(type="string", format="email")),
 *     @OA\Parameter(name="sender_email", in="query", description="Filtrar por email del remitente", required=false, @OA\Schema(type="string", format="email")),
 *     @OA\Parameter(name="status", in="query", description="Filtrar por estado", required=false, @OA\Schema(type="string", enum={"pending", "read", "answered"})),
 *     @OA\Parameter(name="from", in="query", description="Fecha de inicio", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Parameter(name="to", in="query", description="Fecha de fin", required=false, @OA\Schema(type="string", format="date")),
 *     @OA\Response(response=200, description="Lista de contactos", @OA\JsonContent(ref="#/components/schemas/ContactSendRequest")),
 *     @OA\Response(response=422, description="Validación fallida", @OA\JsonContent(type="object", @OA\Property(property="error", type="string")))
 * )
 */

    public function index(IndexContactSenderRequest $request)
    {

        return $this->getFilteredResults(
            ContactSend::class,
            $request,
            ContactSend::filters,
            ContactSend::sorts,
            ContactSendResource::class
        );
    }
/**
 * @OA\Get(
 *     path="/moontransparency/public/api/contactsend/{id}",
 *     summary="Obtener detalles de un Contacto Envíopor ID",
 *     tags={"ContactSend"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", description="ID del ContactSend", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Contacto de Envío encontrado", @OA\JsonContent(ref="#/components/schemas/ContactSend")),
 *     @OA\Response(response=404, description="Contacto de Envío no encontrado", @OA\JsonContent(type="object", @OA\Property(property="error", type="string", example="Contacto de Envío no encontrado")))
 * )
 */

    public function show($id)
    {

        $contactsender = $this->contactsenderService->getContactSendById($id);

        if (! $contactsender) {
            return response()->json([
                'error' => 'Contacto de Envío No Encontrado',
            ], 404);
        }

        return new ContactSendResource($contactsender);
    }

/**
 * @OA\Post(
 *     path="/moontransparency/public/api/contactsend",
 *     summary="Crear ContactSend",
 *     tags={"ContactSend"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ContactSendRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Contacto de Envío creada exitosamente", @OA\JsonContent(ref="#/components/schemas/ContactSend")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 * )
 */
    public function store(StoreContactSenderRequest $request)
    {
        if ($request->header('UUID') !== env('APP_UUID')) {
            return response()->json(['status' => 'unauthorized'], 401);
        }
        $data               = $request->validated();
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->header('User-Agent');
        $data['status']     = 'Generada';
        $contactsender      = $this->contactsenderService->createContactSend($data);
        return new ContactSendResource($contactsender);
    }

/**
 * @OA\Put(
 *     path="/moontransparency/public/api/contactsend/{id}",
 *     summary="Actualizar un ContactSend",
 *     tags={"ContactSend"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(ref="#/components/schemas/ContactSendRequest")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Contacto de Envío actualizado exitosamente", @OA\JsonContent(ref="#/components/schemas/ContactSend")),
 *     @OA\Response(response=422, description="Error de validación", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error de validación"))),
 *     @OA\Response(response=404, description="Contacto de Envío no encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Contacto de Envío no encontrado"))),
 *     @OA\Response(response=500, description="Error interno", @OA\JsonContent(@OA\Property(property="error", type="string", example="Error interno del servidor")))
 * )
 */

    public function update(UpdateContactSenderRequest $request, $id)
    {

        $validatedData = $request->validated();

        $contactsender = $this->contactsenderService->getContactSendById($id);
        if (! $contactsender) {
            return response()->json([
                'error' => 'Contacto de Envío No Encontrado',
            ], 404);
        }

        $updatedCompany = $this->contactsenderService->updateContactSend($contactsender, $validatedData);
        return new ContactSendResource($updatedCompany);
    }

/**
 * @OA\Delete(
 *     path="/moontransparency/public/api/contactsend/{id}",
 *     summary="Eliminar un Contacto Envíopor ID",
 *     tags={"ContactSend"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer", example=1)),
 *     @OA\Response(response=200, description="Contacto de Envío eliminado", @OA\JsonContent(@OA\Property(property="message", type="string", example="Contacto de Envío eliminado exitosamente"))),
 *     @OA\Response(response=404, description="No encontrado", @OA\JsonContent(@OA\Property(property="error", type="string", example="Contacto de Envío no encontrado"))),

 * )
 */

    public function destroy($id)
    {

        $contactsender = $this->contactsenderService->getContactSendById($id);

        if (! $contactsender) {
            return response()->json([
                'error' => 'Contacto de Envío No Encontrado.',
            ], 404);
        }
        $contactsender = $this->contactsenderService->destroyById($id);

        return response()->json([
            'message' => 'Contacto Envíoeliminado exitosamente',
        ], 200);
    }

}
