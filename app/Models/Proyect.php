<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyect extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name',
        'type',
        'status',
        'start_date',
        'end_date',
        'location',
        'images',
        'description',
        'budget_estimated',
        'nro_beneficiaries',
        'impact_initial',
        'impact_final',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'name'              => 'like',
        'type'              => 'like',
        'status'            => 'like',
        'start_date'        => 'betweetn',
        'end_date'          => 'betweetn',
        'location'          => 'like',

        'description'       => 'like',
        'budget_estimated'  => 'like',
        'nro_beneficiaries' => 'like',
        'impact_initial'    => 'like',
        'impact_final'      => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];

    public function ods()
    {
        return $this->belongsToMany(Ods::class, 'proyect_ods', 'proyect_id', 'ods_id')
            ->whereNull('proyect_ods.deleted_at'); // Filtra solo las relaciones no eliminadas
    }

    public function imagestable()
    {
        return $this->hasMany(Image::class);
    }
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
    public function indicators()
    {
        return $this->hasMany(Indicator::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function allies()
    {
        return $this->belongsToMany(Ally::class, 'donations', 'proyect_id', 'ally_id')
            ->whereNull('donations.deleted_at')
            ->distinct();   
    }

}
