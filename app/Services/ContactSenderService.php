<?php
namespace App\Services;

use App\Mail\ContactFormMail;
use App\Models\ContactSend;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactSenderService
{

    public function getContactSendById(int $id): ?ContactSend
    {
        return ContactSend::find($id);
    }

    public function createContactSend(array $data): ContactSend
    {
        // Enviar correo electrónico
        $this->notifyAboutNewContact($data);

        // Crear y devolver el registro
        return ContactSend::create($data);
    }

/**
 * Envía notificación de nuevo contacto
 */
    private function notifyAboutNewContact(array $data): void
    {
        try {
            $emailData = [
                'name'    => $data['name_contact'],
                'email'   => $data['contact_email'],
                'subject' => $data['subject'],
                'message' => $data['description'],
                'sender'  => $data['sender_email'] ?? null,
            ];
            $correoSend = env('MAIL_FROM_ADDRESS');
            Mail::to([$correoSend])->send(new ContactFormMail($emailData));

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación: ' . $data['contact_email'] . ', error:' . $e->getMessage());
        }
    }

    public function updateContactSend(ContactSend $proyect, array $data): ContactSend
    {
        $proyect->update($data);
        return $proyect;
    }

    public function destroyById($id)
    {
        return ContactSend::find($id)?->delete() ?? false;
    }

}
