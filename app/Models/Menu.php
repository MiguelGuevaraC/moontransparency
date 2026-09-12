<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'Activo';

    public const STATUS_INACTIVE = 'Inactivo';

    protected $fillable = [
        'code',
        'name',
        'path',
        'icon',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_permissions');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'menu_rols', 'menu_id', 'rol_id')
            ->withTimestamps();
    }
}
