<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name',
        'start_date',
        'end_date',
        'project_id',
        'objective',
        'total_amount',
        'collected_amount',
        'status',
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
        'project_id'       => '=',
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
