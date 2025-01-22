<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission_rol extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name_permission',
        'name_rol',
        'type',
        'rol_id',
        'permission_id',

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
        'name_permission' => 'like',
        'name_rol'        => 'like',
        'type'            => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'   => 'desc',
        'type' => 'desc',
        'name' => 'desc',
    ];
    public function permission()
    {
        return $this->belongsTo(Rol::class, 'permission_id');
    }
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
}
