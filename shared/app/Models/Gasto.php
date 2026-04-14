<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa un gasto individual registrado por el usuario.
 *
 * @autor Antonio Martin Leon
 */
class Gasto extends Model
{
    protected $table = 'gastos';

    protected $fillable = [
        'titulo',
        'importe',
        'fecha',
        'categoria_id',
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
     * Obtiene la categoria asociada al gasto.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Obtiene el movimiento fijo que genero este gasto, si existe.
     */
    public function movimientoFijo(): BelongsTo
    {
        return $this->belongsTo(MovimientoFijo::class, 'movimiento_fijo_id');
    }
}
