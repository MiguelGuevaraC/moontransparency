<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ods extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'created_at',
    ];
    protected $hidden = [

        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const filters = [
        'code'        => 'like',
        'name'        => 'like',
        'description' => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
    ];
    public function proyects()
    {
        return $this->belongsToMany(Proyect::class, 'proyect_ods', 'ods_id', 'proyect_id');
    }
}
