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
        'description',
        'link',

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
        'description'        => 'like',
        'link'               => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'            => 'desc',
    ];

    public function imagestable()
    {
        return $this->hasMany(Image::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function total_donated(): string
    {
        $total = $this->donations()->sum('amount');
        return (string) ($total ?: "0");
    }

    public function proyects()
    {
        return $this->belongsToMany(Proyect::class, 'donations', 'ally_id', 'proyect_id')
            ->whereNull('donations.deleted_at')
            ->distinct();
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'donations', 'ally_id', 'activity_id')
            ->whereNull('donations.deleted_at')
            ->distinct();
    }

}
