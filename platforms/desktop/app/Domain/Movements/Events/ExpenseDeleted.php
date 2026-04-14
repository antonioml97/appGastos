<?php

namespace App\Domain\Movements\Events;

/**
 * Representa la eliminacion de un gasto en el dominio.
 */
class ExpenseDeleted
{
    public function __construct(
        public readonly int $gastoId,
        public readonly float $amount,
        public readonly ?string $categoryName,
        public readonly ?string $categoryIcon,
    ) {}
}
