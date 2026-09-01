<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guarda el saldo mensual persistido de una cuenta.
 *
 * @autor Antonio Martin Leon
 */
class CuentaSaldoMensual extends Model
{
    use BelongsToUser;

    protected $table = 'cuenta_saldos_mensuales';

    protected $fillable = [
        'user_id',
        'cuenta_id',
        'mes',
        'saldo_inicial_mes',
        'total_ingresos',
        'total_gastos',
        'saldo_final',
    ];

    /**
     * Define las conversiones automaticas del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saldo_inicial_mes' => 'decimal:2',
            'total_ingresos' => 'decimal:2',
            'total_gastos' => 'decimal:2',
            'saldo_final' => 'decimal:2',
        ];
    }

    /**
     * Obtiene la cuenta asociada al saldo mensual.
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }
}
