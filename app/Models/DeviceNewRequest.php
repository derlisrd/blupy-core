<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceNewRequest extends Model
{
    protected $table ='device_new_requests';
    protected $fillable = [
        'user_id',
        'ip',
        'device',
        'location',
        'celular',
        'email',
        'cedula_frente_url',
        'cedula_dorso_url',
        'cedula_selfie_url',
        'os',
        'model',
        'web',
        'desktop',
        'version',
        'devicetoken',
        'aprobado',

        'build_version',
        'time' ,
        'device_id_app'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'aprobado' => 'boolean',
        'web' => 'boolean',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el usuario asociado a esta solicitud de dispositivo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
