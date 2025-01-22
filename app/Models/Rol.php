<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name',
        'status',
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
        'name' => 'like',
        'status' => '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
        'name' => 'desc',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_rols', 'rol_id', 'permission_id');
    }

    public function permissionByRol()
    {
        return $this->hasMany(Permission_rol::class);
    }

}
