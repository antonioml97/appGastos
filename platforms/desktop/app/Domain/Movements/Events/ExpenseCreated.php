<?php

namespace App\Domain\Movements\Events;

/**
 * Representa el alta de un gasto en el dominio.
 */
class ExpenseCreated
{
    public function __construct(
        public readonly int $gastoId,
        public readonly float $amount,
        public readonly ?string $categoryName,
        public readonly ?string $categoryIcon,
    ) {}
}
