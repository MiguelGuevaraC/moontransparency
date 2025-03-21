<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Activity extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name',
        'start_date',
        'end_date',
        'proyect_id',
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

        'start_date' => 'desc',
        'id'         => 'desc',

    ];

    public function proyect()
    {
        return $this->belongsTo(Proyect::class);
    }

    public function updateTotalRecaudado()
    {

        $collected_amount = DB::table('donations')
            ->where('activity_id', $this->id)
            ->whereNull('deleted_at')
            ->sum('amount');

        $this->update(['collected_amount' => $collected_amount ?? 0]);

    }
}
