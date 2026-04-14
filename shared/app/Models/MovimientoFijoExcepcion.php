<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guarda excepciones mensuales para movimientos fijos que no deben generarse.
 *
 * @autor Antonio Martin Leon
 */
class MovimientoFijoExcepcion extends Model
{
    protected $table = 'movimientos_fijos_excepciones';
    protected $dateFormat = 'Y-m-d';

    protected $fillable = [
        'movimiento_fijo_id',
        'tipo',
        'fecha',
    ];

    /**
     * Define las conversiones automaticas del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Obtiene el movimiento fijo asociado a la excepcion.
     */
    public function movimientoFijo(): BelongsTo
    {
        return $this->belongsTo(MovimientoFijo::class, 'movimiento_fijo_id');
    }
}
