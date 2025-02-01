<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'proyect_id',
        'activity_id',
        'date_donation',
        'ally_id',
        'details',
        'contribution_type',
        'amount',
        'evidence',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [

        'ruc_dni'          => 'like',
        'name'             => 'like',
        'start_date'       => 'date',
        'end_date'         => 'date',
        'proyect_id'       => '=',
        'objective'        => 'like',
        'total_amount'     => '=',
        'collected_amount' => '=',
        'status'           => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [

        'start_date'    => 'desc',
        'id' => 'desc',

    ];
}
