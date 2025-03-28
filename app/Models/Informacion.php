<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Informacion extends Model
{
    use HasFactory;
    protected $table = 'informaciones';
    protected $fillable = [
        'codigo_info',
        'user_id',
        'title',
        'description',
        'text',
        'img_url',
        'url',
        'active',
        'leido',
        'general',
        'aso',
        'farma',
        'digital'
    ];
}
