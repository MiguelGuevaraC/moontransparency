<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    protected $fillable = [
        'id',
        'proyect_id',
        'indicator_name',
        'target_value',
        'progress_value',
        'unit',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [

        'proyect_id' => '=',
        'indicator_name' => 'like',
        'target_value' => 'like',
        'progress_value' => 'like',
        'unit' => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',

    ];
}
