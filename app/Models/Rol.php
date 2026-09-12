<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'Activo';

    public const STATUS_INACTIVE = 'Inactivo';

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
    const sorts = ['id', 'name', 'status'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_rols', 'rol_id', 'permission_id')
            ->wherePivotNull('deleted_at');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_rols', 'rol_id', 'menu_id')
            ->where('menus.status', Menu::STATUS_ACTIVE)
            ->orderBy('menus.sort_order')
            ->withTimestamps();
    }

    public function permissionByRol()
    {
        return $this->hasMany(Permission_rol::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
