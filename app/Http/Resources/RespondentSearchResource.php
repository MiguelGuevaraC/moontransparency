<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;



class RespondentSearchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'              => $this->id ?? null,
            'number_document' => $this->number_document ?? null,
            'names'           => $this->names ?? null,
        ];
    }
}
