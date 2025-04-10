<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Respondent extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'number_document',
        'names',
        'date_of_birth',
        'phone',
        'email',
        'genero',

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'number_document' => 'like',
        'names' => 'like',
        'date_of_birth' => 'date',

        'phone' => 'like',
        'email' => 'like',
        'genero' => 'like',
    ];

    const filters_search= ['number_document' => '='];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'   => 'desc',
        'names'   => 'desc',
    ];

}
