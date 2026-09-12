<?php

namespace App\Http\Requests\RolRequest;

use App\Http\Requests\UpdateRequest;
use App\Models\Menu;
use Illuminate\Validation\Rule;

class UpdateMenuAccessRequest extends UpdateRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menus' => ['present', 'array'],
            'menus.*' => [
                'integer',
                'distinct',
                Rule::exists('menus', 'id')
                    ->whereNull('deleted_at')
                    ->where('status', Menu::STATUS_ACTIVE),
            ],
        ];
    }
}
