<?php

namespace App\Domain\Accounts\Projectors;

use App\Models\Cuenta;
use App\Models\CuentaSaldoMensual;
use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Proyecta el saldo de la cuenta normal a partir del ledger de movimientos.
 */
class NormalAccountProjector
{
    /**
     * Reconstruye el saldo actual agregado de las cuentas normales.
     */
    public function rebuild(): void
    {
        $this->rebuildUntil();
    }

    /**
     * Reconstruye los saldos mensuales de las cuentas normales hasta el mes indicado.
     */
    public function rebuildUntil(?Carbon $targetMonth = null): void
    {
        $accounts = Cuenta::query()
            ->where('tipo', 'normal')
            ->orderBy('id')
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        $primaryAccount = $accounts->shift();

        if ($primaryAccount === null) {
            return;
        }

        $monthBounds = $this->resolveMonthBounds($targetMonth);
        $latestBalance = round((float) $primaryAccount->saldo_inicial, 2);
        $canPersistMonthlyBalances = Schema::hasTable('cuenta_saldos_mensuales');

        if ($monthBounds !== null && $canPersistMonthlyBalances) {
            [$startMonth, $endMonth] = $monthBounds;

            CuentaSaldoMensual::query()
                ->where('cuenta_id', $primaryAccount->id)
                ->where('mes', '>=', $startMonth->format('Y-m'))
                ->delete();

            $cursor = $startMonth->copy();

            while ($cursor->lessThanOrEqualTo($endMonth)) {
                $monthStart = $cursor->copy()->startOfMonth()->toDateString();
                $monthEnd = $cursor->copy()->endOfMonth()->toDateString();
                $totalIngresos = round((float) Ingreso::query()
                    ->whereBetween('fecha', [$monthStart, $monthEnd])
                    ->sum('importe'), 2);
                $totalGastos = round((float) Gasto::query()
                    ->whereBetween('fecha', [$monthStart, $monthEnd])
                    ->sum('importe'), 2);
                $saldoInicialMes = $latestBalance;
                $saldoFinal = round($saldoInicialMes + $totalIngresos - $totalGastos, 2);

                CuentaSaldoMensual::query()->updateOrCreate(
                    [
                        'cuenta_id' => $primaryAccount->id,
                        'mes' => $cursor->format('Y-m'),
                    ],
                    [
                        'saldo_inicial_mes' => $saldoInicialMes,
                        'total_ingresos' => $totalIngresos,
                        'total_gastos' => $totalGastos,
                        'saldo_final' => $saldoFinal,
                    ]
                );

                $latestBalance = $saldoFinal;
                $cursor->addMonthNoOverflow();
            }
        }

        if ($monthBounds !== null && ! $canPersistMonthlyBalances) {
            [, $endMonth] = $monthBounds;
            $latestBalance = $this->calculateBalanceUntil($primaryAccount, $endMonth);
        }

        $primaryAccount->update([
            'saldo_actual' => $latestBalance,
        ]);

        foreach ($accounts as $account) {
            $account->update([
                'saldo_actual' => round((float) $account->saldo_inicial, 2),
            ]);
        }
    }

    /**
     * Obtiene el saldo final de la cuenta normal principal para el mes indicado.
     */
    public function balanceForMonth(Carbon $month): float
    {
        $primaryAccount = Cuenta::query()
            ->where('tipo', 'normal')
            ->orderBy('id')
            ->first();

        if ($primaryAccount === null) {
            return 0;
        }

        if (! Schema::hasTable('cuenta_saldos_mensuales')) {
            return $this->calculateBalanceUntil($primaryAccount, $month);
        }

        $this->rebuildUntil($month->copy()->startOfMonth());

        $monthlyBalance = CuentaSaldoMensual::query()
            ->where('cuenta_id', $primaryAccount->id)
            ->where('mes', $month->format('Y-m'))
            ->value('saldo_final');

        if ($monthlyBalance === null) {
            return round((float) $primaryAccount->saldo_inicial, 2);
        }

        return round((float) $monthlyBalance, 2);
    }

    /**
     * Determina el rango mensual que debe persistirse.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function resolveMonthBounds(?Carbon $targetMonth): ?array
    {
        $firstExpenseDate = Gasto::query()->min('fecha');
        $firstIncomeDate = Ingreso::query()->min('fecha');
        $firstMovementDate = collect([$firstExpenseDate, $firstIncomeDate])
            ->filter()
            ->sort()
            ->first();

        if ($firstMovementDate === null && $targetMonth === null) {
            return null;
        }

        $startMonth = $firstMovementDate !== null
            ? Carbon::parse($firstMovementDate)->startOfMonth()
            : $targetMonth->copy()->startOfMonth();

        $endMonth = $targetMonth?->copy()->startOfMonth() ?? now()->startOfMonth();

        if ($startMonth->greaterThan($endMonth)) {
            $startMonth = $endMonth->copy();
        }

        return [$startMonth, $endMonth];
    }

    /**
     * Calcula el saldo acumulado de la cuenta normal hasta el fin del mes indicado.
     */
    private function calculateBalanceUntil(Cuenta $primaryAccount, Carbon $month): float
    {
        $monthEnd = $month->copy()->endOfMonth()->toDateString();
        $totalIngresos = round((float) Ingreso::query()
            ->whereDate('fecha', '<=', $monthEnd)
            ->sum('importe'), 2);
        $totalGastos = round((float) Gasto::query()
            ->whereDate('fecha', '<=', $monthEnd)
            ->sum('importe'), 2);

        return round((float) $primaryAccount->saldo_inicial + $totalIngresos - $totalGastos, 2);
    }
}
