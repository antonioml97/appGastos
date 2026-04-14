<?php

namespace App\Domain\Movements\Events;

/**
 * Representa la modificacion de un ingreso en el dominio.
 */
class IncomeUpdated
{
    public function __construct(
        public readonly int $ingresoId,
        public readonly float $previousAmount,
        public readonly float $currentAmount,
    ) {}
}
