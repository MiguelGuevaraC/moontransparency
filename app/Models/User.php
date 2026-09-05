<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 'Activo';
    public const STATUS_INACTIVE = 'Inactivo';

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
        'status'          => '=',
        'rol_id'          => '=',
    ];

    /**
     * Campos de ordenación disponibles.
     */
    const sorts = ['id', 'names', 'username', 'status'];

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

    public function surveyedCreated()
    {
        return $this->hasMany(Surveyed::class, 'created_by');
    }

    public function surveyedUpdated()
    {
        return $this->hasMany(Surveyed::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return Str::lower(trim((string) $this->status)) === Str::lower(self::STATUS_ACTIVE);
    }

    public function isAdministrator(): bool
    {
        $roleName = Str::lower(Str::ascii(trim((string) $this->rol?->name)));

        return in_array($roleName, ['administrador', 'administrador moon'], true);
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->isActive() || !$this->rol_id) {
            return false;
        }

        return $this->rol()
            ->where('status', Rol::STATUS_ACTIVE)
            ->whereHas('permissions', function ($query) use ($permission) {
                $query
                    ->where('permissions.route', $permission)
                    ->where('permissions.status', Permission::STATUS_ACTIVE);
            })
            ->exists();
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = Hash::needsRehash($password)
            ? Hash::make($password)
            : $password;
    }
}
