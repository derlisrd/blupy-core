<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    protected $table = 'devices';
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    protected $fillable = [
        'user_id',
        'device',
        'os',
        'devicetoken',
        'model',
        'web',
        'desktop',
        'ip',
        
        'device_id_app',
        'version',
        'build_version',
        'time'
    ];

    protected $casts = [
        
    ];
}
