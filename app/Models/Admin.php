<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;


class Admin extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected $hidden = [
        'password',
    ];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'permisos_otorgados');
    }

    public function tienePermiso(string $modulo, string $accion): bool
    {
        return $this->permisos()->where('modulo', $modulo)->where('accion', $accion)->exists();
    }
}
