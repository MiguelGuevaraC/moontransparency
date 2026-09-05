<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\Permission;
use Illuminate\Validation\Rule;

class UpdateAccessRequest extends UpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access' => ['present', 'array'],
            'access.*' => [
                'integer', 'distinct',
                Rule::exists('permissions', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', Permission::STATUS_ACTIVE),
            ],
        ];
    }
}
