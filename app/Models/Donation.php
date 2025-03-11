<?php
namespace App\Models;

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
        'images',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [

        'proyect_id'        => '=',
        'activity_id'       => '=',
        'date_donation'     => 'between',
        'ally_id'           => '=',
        'details'           => 'like',
        'contribution_type' => 'like',
        'amount'            => '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',

    ];

    public function imagestable()
    {
        return $this->hasMany(Image::class);
    }
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
    public function proyect()
    {
        return $this->belongsTo(Proyect::class);
    }
}
