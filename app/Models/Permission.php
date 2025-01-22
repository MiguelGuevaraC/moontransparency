<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'id',
        'name',
        'type',
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
        'type' => 'like',
        'status' => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id' => 'desc',
        'type' => 'desc',
        'name' => 'desc',
    ];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'permission_rols', 'permission_id', 'rol_id');
    }
}
