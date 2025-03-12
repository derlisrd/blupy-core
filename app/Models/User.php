<?php

namespace App\Models;
//use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'cliente_id',
        'vendedor_id',
        'name',
        'email',
        'password',
        'active',
        'rol',
        'ultimo_ingreso',
        'intentos',
        'changepass',
        'version'
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
        'remember_token',
        'email_verified_at'
    ];

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id','id');
    }
    public function devices(){
        return $this->hasMany(Device::class,'user_id');
    }

    public function notificaciones(){
        return $this->hasMany(Notificacion::class,'user_id');
    }
    public function informaciones(){
        return $this->hasMany(Informacion::class,'user_id');
    }
    public function info(){
        $id = $this->id;
        $info = Informacion::where('user_id', $id)->where('active',1)->where('leido',0)->latest()->first();
        return $info;
    }

    public function notitokens()
    {
        $id = $this->id;
        $tokens = Device::where('user_id', $id)->whereNotNull('notitoken')->pluck('notitoken')->toArray();
        return $tokens;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
