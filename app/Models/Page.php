<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'sku',
        'user_id',
        'type',
        'content_json',
        'content_text',
    ];

    protected $casts = [
        'content_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
