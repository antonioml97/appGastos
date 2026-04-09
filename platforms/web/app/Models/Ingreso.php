<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Representa un ingreso registrado dentro del periodo contable.
 *
 * @autor Antonio Martin Leon
 */
class Ingreso extends Model
{
    protected $table = 'ingresos';

    protected $fillable = [
        'titulo',
        'importe',
        'fecha',
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
}
