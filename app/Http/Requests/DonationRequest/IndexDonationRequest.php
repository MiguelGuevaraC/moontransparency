<?php
namespace App\Http\Requests\DonationRequest;

use App\Http\Requests\IndexRequest;

class IndexDonationRequest extends IndexRequest
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

            'project_id'        => 'nullable|string',
            'activity_id'       => 'nullable|string',
            'date_donation'     => 'nullable|string',
            'ally_id'           => 'nullable|string',
            'details'           => 'nullable|string',
            'contribution_type' => 'nullable|string',
            'amount'            => 'nullable|string',
            'evidence'          => 'nullable|string',
        ];
    }
}
