<?php
namespace App\Http\Requests\ContactSenderRequest;

use App\Http\Requests\IndexRequest;

class IndexContactSenderRequest extends IndexRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_contact'  => 'nullable|string',
            'subject'       => 'nullable|string',
            'description'   => 'nullable|string',
            'contact_email' => 'nullable|string',
            'sender_email'  => 'nullable|string',
        ];
    }
}
