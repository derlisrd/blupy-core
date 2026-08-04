<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDraft extends Model
{
    use HasFactory;

    protected $table = 'solicitud_drafts';

    protected $fillable = [
        'cliente_id',
        'estado',
        'step',
        'json_data',
    ];

    /**
     * Define los casteos de atributos.
     */
    protected $casts = [
        'json_data' => 'json', 
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
