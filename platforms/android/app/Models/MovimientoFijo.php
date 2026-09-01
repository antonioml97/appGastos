<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa un gasto o ingreso fijo que se replica cada mes.
 *
 * @autor Antonio Martin Leon
 */
class MovimientoFijo extends Model
{
    use BelongsToUser;

    protected $table = 'movimientos_fijos';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'importe',
        'dia',
        'categoria_id',
        'observaciones',
        'activo',
    ];

    /**
     * Define las conversiones automaticas de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'importe' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /**
     * Obtiene la categoria asociada al movimiento fijo.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Obtiene los gastos generados desde este movimiento fijo.
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'movimiento_fijo_id');
    }

    /**
     * Obtiene los ingresos generados desde este movimiento fijo.
     */
    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'movimiento_fijo_id');
    }

    /**
     * Obtiene las excepciones mensuales del movimiento fijo.
     */
    public function excepciones(): HasMany
    {
        return $this->hasMany(MovimientoFijoExcepcion::class, 'movimiento_fijo_id');
    }
}
