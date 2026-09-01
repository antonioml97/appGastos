<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una cuenta financiera del usuario.
 *
 * @autor Antonio Martin Leon
 */
class Cuenta extends Model
{
    use BelongsToUser;

    protected $table = 'cuentas';

    protected $fillable = [
        'user_id',
        'nombre',
        'tipo',
        'saldo_inicial',
        'saldo_actual',
        'ahorro_mensual',
        'ultimo_mes_ahorro_aplicado',
    ];

    /**
     * Define las conversiones automaticas de atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'saldo_actual' => 'decimal:2',
            'ahorro_mensual' => 'decimal:2',
            'ultimo_mes_ahorro_aplicado' => 'date',
        ];
    }

    /**
     * Obtiene los saldos mensuales persistidos de la cuenta.
     */
    public function saldosMensuales(): HasMany
    {
        return $this->hasMany(CuentaSaldoMensual::class, 'cuenta_id');
    }
}
