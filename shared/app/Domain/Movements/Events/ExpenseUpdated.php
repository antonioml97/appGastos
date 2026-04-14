<?php

namespace App\Domain\Movements\Events;

/**
 * Representa la modificacion de un gasto en el dominio.
 */
class ExpenseUpdated
{
    public function __construct(
        public readonly int $gastoId,
        public readonly float $previousAmount,
        public readonly float $currentAmount,
        public readonly ?string $previousCategoryName,
        public readonly ?string $currentCategoryName,
        public readonly ?string $previousCategoryIcon,
        public readonly ?string $currentCategoryIcon,
    ) {}
}
