<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa un ingreso registrado dentro del periodo contable.
 *
 * @autor Antonio Martin Leon
 */
class Ingreso extends Model
{
    use BelongsToUser;

    protected $table = 'ingresos';

    protected $fillable = [
        'user_id',
        'titulo',
        'importe',
        'fecha',
        'movimiento_fijo_id',
        'observaciones',
    ];

    /**
     * Define las conversiones automaticas de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'importe' => 'decimal:2',
        ];
    }

    /**
     * Obtiene el movimiento fijo que genero este ingreso, si existe.
     */
    public function movimientoFijo(): BelongsTo
    {
        return $this->belongsTo(MovimientoFijo::class, 'movimiento_fijo_id');
    }
}
