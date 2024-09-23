<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    use HasFactory;
    protected $table = 'notificaciones';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'body'
    ];
}
