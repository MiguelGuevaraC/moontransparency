<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContactSendResource extends JsonResource
{
/**
 * @OA\Schema(
 *     schema="ContactSend",
 *     type="object",
 *     required={"name_contact", "subject", "description", "contact_email"},
 *     @OA\Property(property="name_contact", type="string", example="Juan Pérez"),
 *     @OA\Property(property="subject", type="string", example="Consulta sobre productos"),
 *     @OA\Property(property="description", type="string", example="Me gustaría obtener más información sobre sus servicios."),
 *     @OA\Property(property="contact_email", type="string", format="email", example="contacto@empresa.com"),
 *     @OA\Property(property="status", type="string", example="pending", enum={"pending", "read", "answered"})
 * )
 */
    public function toArray($request)
    {
        return [
            'id'            => $this->id ?? null,
            'name_contact'  => $this->name_contact ?? null,
            'subject'       => $this->subject ?? null,
            'description'   => $this->description ?? null,
            'contact_email' => $this->contact_email ?? null,
            'status'        => $this->status ?? null,
            'created_at'    => $this->created_at,
        ];
    }

}
