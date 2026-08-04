<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDraft extends Model
{
    use HasFactory;

    protected $table = 'solicitud_drafts';

    /**
     * Campos permitidos para asignación masiva (ej. SolicitudDraft::create($data))
     */
    protected $fillable = [
        'cliente_id',
        'estado',
        'step',
        'json_data',
    ];

    /**
     * Mapeo de tipos de datos de Eloquent.
     */
   /*  protected function casts(): array
    {
        return [
            'json_data' => 'array', // Transforma automáticamente el JSON de MySQL a array PHP
        ];
    }
 */
    /**
     * Relación con el modelo Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
