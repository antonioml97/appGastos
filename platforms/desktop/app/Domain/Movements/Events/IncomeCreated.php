<?php

namespace App\Domain\Movements\Events;

/**
 * Representa el alta de un ingreso en el dominio.
 */
class IncomeCreated
{
    public function __construct(
        public readonly int $ingresoId,
        public readonly float $amount,
    ) {}
}
