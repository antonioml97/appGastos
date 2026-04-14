<?php

namespace App\Domain\Movements\Events;

/**
 * Representa la eliminacion de un ingreso en el dominio.
 */
class IncomeDeleted
{
    public function __construct(
        public readonly int $ingresoId,
        public readonly float $amount,
    ) {}
}
