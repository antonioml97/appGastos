<?php

namespace App\Domain\Accounts\Projectors;

use App\Models\Cuenta;

/**
 * Proyecta el saldo de ahorro cuando un movimiento representa una aportacion al ahorro.
 */
class SavingsAccountProjector
{
    public function addContribution(float $amount): void
    {
        $account = $this->resolvePrimarySavingsAccount();

        if ($account === null) {
            return;
        }

        $account->update([
            'saldo_actual' => round((float) $account->saldo_actual + $amount, 2),
        ]);
    }

    public function removeContribution(float $amount): void
    {
        $account = $this->resolvePrimarySavingsAccount();

        if ($account === null) {
            return;
        }

        $account->update([
            'saldo_actual' => round((float) $account->saldo_actual - $amount, 2),
        ]);
    }

    public function isSavingsCategory(?string $categoryName, ?string $categoryIcon): bool
    {
        $normalizedName = mb_strtolower(trim((string) $categoryName));

        return $normalizedName === 'ahorro';
    }

    private function resolvePrimarySavingsAccount(): ?Cuenta
    {
        return Cuenta::query()
            ->where('tipo', 'ahorro')
            ->orderBy('id')
            ->first();
    }
}
