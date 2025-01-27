<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type_document',
        'number_document',
        'names',
        'username',
        'password',
        'address',
        'phone',
        'email',
        'status',
        'rol_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    const filters = [
        'type_document'   => '=',
        'number_document' => 'like',
        'names'           => 'like',
        'username'        => 'like',
        'address'         => 'like',
        'phone'           => 'like',
        'email'           => 'like',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = [
        'id'   => 'desc',
        'name' => 'desc',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
}
