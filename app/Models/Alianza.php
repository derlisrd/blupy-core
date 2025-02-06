<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alianza extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'codigo'];
}
