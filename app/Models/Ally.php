<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ally extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'ruc_dni',
        'first_name',
        'last_name',
        'business_name',
        'phone',
        'email',
        'images',
        'area_of_interest',
        'participation_type',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [

        'ruc_dni'            => 'like',
        'first_name'         => 'like',
        'last_name'          => 'like',
        'business_name'      => 'like',
        'phone'              => 'like',
        'email'              => 'like',

        'area_of_interest'   => 'like',
        'participation_type' => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [

        'first_name'    => 'desc',
        'business_name' => 'desc',
        'id'            => 'desc',
    ];
}
