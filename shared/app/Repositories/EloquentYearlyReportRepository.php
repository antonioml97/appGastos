<?php

namespace App\Repositories;

use App\Contracts\Repositories\YearlyReportRepositoryInterface;
use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Implementa la construccion del informe anual mediante consultas Eloquent.
 *
 * @autor Antonio Martin Leon
 */
class EloquentYearlyReportRepository implements YearlyReportRepositoryInterface
{
    /**
     * Construye el payload del resumen anual para el ano solicitado.
     *
     * @return array<string, mixed>
     */
    public function getPayload(?int $year): array
    {
        $selectedYear = $this->resolveYear($year);
        $yearStart = Carbon::create($selectedYear, 1, 1)->startOfDay();
        $yearEnd = $yearStart->copy()->endOfYear();

        $gastosPorMes = Gasto::query()
            ->selectRaw('strftime("%m", fecha) as mes')
            ->selectRaw('SUM(importe) as total')
            ->whereBetween('fecha', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $ingresosPorMes = Ingreso::query()
            ->selectRaw('strftime("%m", fecha) as mes')
            ->selectRaw('SUM(importe) as total')
            ->whereBetween('fecha', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $monthly = collect(range(1, 12))
            ->map(function (int $month) use ($selectedYear, $gastosPorMes, $ingresosPorMes) {
                $monthKey = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
                $expense = round((float) ($gastosPorMes[$monthKey] ?? 0), 2);
                $income = round((float) ($ingresosPorMes[$monthKey] ?? 0), 2);

                return [
                    'month' => $month,
                    'label' => Carbon::create($selectedYear, $month, 1)->translatedFormat('M'),
                    'expense' => $expense,
                    'income' => $income,
                    'balance' => round($income - $expense, 2),
                ];
            })
            ->values();

        $categoryBreakdown = Gasto::query()
            ->join('categorias', 'categorias.id', '=', 'gastos.categoria_id')
            ->whereBetween('fecha', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->groupBy('categorias.id', 'categorias.nombre', 'categorias.color', 'categorias.icono')
            ->orderByRaw('SUM(gastos.importe) DESC')
            ->select([
                'categorias.id',
                'categorias.nombre',
                'categorias.color',
                'categorias.icono',
                DB::raw('SUM(gastos.importe) as total'),
                DB::raw('COUNT(gastos.id) as movimientos'),
            ])
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'color' => $item->color,
                'icono' => $item->icono,
                'total' => round((float) $item->total, 2),
                'movimientos' => (int) $item->movimientos,
            ])
            ->values();

        $totalGastado = round((float) $monthly->sum('expense'), 2);
        $totalIngresado = round((float) $monthly->sum('income'), 2);
        $balance = round($totalIngresado - $totalGastado, 2);
        $totalMovimientos = Gasto::query()
            ->whereBetween('fecha', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->count();

        return [
            'page' => 'yearly',
            'title' => 'Gastos anuales',
            'selectedYear' => $selectedYear,
            'years' => $this->buildYearOptions($selectedYear)->all(),
            'summary' => [
                'totalGastado' => $totalGastado,
                'totalIngresado' => $totalIngresado,
                'balance' => $balance,
                'totalMovimientos' => $totalMovimientos,
            ],
            'monthly' => $monthly->all(),
            'categoryBreakdown' => $categoryBreakdown->all(),
        ];
    }

    /**
     * Genera las opciones de anos disponibles alrededor del ano seleccionado.
     *
     * @return Collection<int, int>
     */
    private function buildYearOptions(int $selectedYear): Collection
    {
        $currentYear = now()->year;
        $startYear = min($selectedYear, $currentYear) - 2;
        $endYear = max($selectedYear, $currentYear) + 2;

        return collect(range($startYear, $endYear))
            ->reverse()
            ->values();
    }

    /**
     * Resuelve el ano solicitado o usa el ano actual por defecto.
     */
    private function resolveYear(?int $year): int
    {
        if ($year !== null && $year >= 2000 && $year <= 2100) {
            return $year;
        }

        return now()->year;
    }
}
