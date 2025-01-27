<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProyectOds extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'proyect_id',
        'ods_id',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'proyect_id'=> '=',
        'ods_id'=> '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];

}
