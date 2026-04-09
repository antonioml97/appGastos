<?php

namespace App\Repositories;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\Ingreso;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Implementa la logica de consulta y mutacion del resumen mensual.
 *
 * @autor Antonio Martin Leon
 */
class EloquentMonthlyReportRepository implements MonthlyReportRepositoryInterface
{
    /**
     * Construye el payload del informe mensual para el mes solicitado.
     *
     * @return array<string, mixed>
     */
    public function getPayload(?string $month): array
    {
        $selectedMonth = $this->resolveMonth($month);
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $categorias = Categoria::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (Categoria $categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'color' => $categoria->color,
                'icono' => $categoria->icono,
            ])
            ->values();

        $gastos = Gasto::query()
            ->with('categoria')
            ->whereBetween('fecha', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Gasto $gasto) => $this->serializeExpense($gasto))
            ->values();

        $ingresos = Ingreso::query()
            ->whereBetween('fecha', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Ingreso $ingreso) => $this->serializeIncome($ingreso))
            ->values();

        $desglose = Gasto::query()
            ->join('categorias', 'categorias.id', '=', 'gastos.categoria_id')
            ->whereBetween('fecha', [$monthStart->toDateString(), $monthEnd->toDateString()])
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

        $totalGastado = (float) $gastos->sum('importe');
        $totalIngresado = (float) $ingresos->sum('importe');
        $totalMovimientos = $gastos->count();
        $importeMedio = $totalMovimientos > 0 ? $totalGastado / $totalMovimientos : 0;
        $balance = $totalIngresado - $totalGastado;

        return [
            'page' => 'monthly',
            'title' => 'Gastos mensuales',
            'selectedMonthLabel' => $selectedMonth->translatedFormat('F Y'),
            'selectedMonthValue' => $selectedMonth->format('Y-m'),
            'summary' => [
                'totalGastado' => round($totalGastado, 2),
                'totalIngresado' => round($totalIngresado, 2),
                'totalMovimientos' => $totalMovimientos,
                'importeMedio' => round($importeMedio, 2),
                'balance' => round($balance, 2),
            ],
            'categorias' => $categorias,
            'gastos' => $gastos,
            'ingresos' => $ingresos,
            'desglose' => $desglose,
        ];
    }

    /**
     * Registra un gasto y devuelve su representacion serializada.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createExpense(array $data): array
    {
        $gasto = Gasto::query()
            ->create($data)
            ->load('categoria');

        return $this->serializeExpense($gasto);
    }

    /**
     * Actualiza un gasto existente y devuelve su representacion serializada.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateExpense(Gasto $gasto, array $data): array
    {
        $gasto->update($data);
        $gasto->load('categoria');

        return $this->serializeExpense($gasto);
    }

    /**
     * Elimina un gasto y devuelve su identificador.
     */
    public function deleteExpense(Gasto $gasto): int
    {
        $gastoId = $gasto->id;
        $gasto->delete();

        return $gastoId;
    }

    /**
     * Registra un ingreso y devuelve su representacion serializada.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createIncome(array $data): array
    {
        $ingreso = Ingreso::query()->create($data);

        return $this->serializeIncome($ingreso);
    }

    /**
     * Elimina un ingreso y devuelve su identificador.
     */
    public function deleteIncome(Ingreso $ingreso): int
    {
        $ingresoId = $ingreso->id;
        $ingreso->delete();

        return $ingresoId;
    }

    /**
     * Convierte un gasto en un array listo para la interfaz.
     *
     * @return array<string, mixed>
     */
    private function serializeExpense(Gasto $gasto): array
    {
        return [
            'id' => $gasto->id,
            'titulo' => $gasto->titulo,
            'importe' => round((float) $gasto->importe, 2),
            'fecha' => $gasto->fecha->format('Y-m-d'),
            'fecha_label' => $gasto->fecha->format('d/m/Y'),
            'categoria_id' => $gasto->categoria_id,
            'categoria' => [
                'id' => $gasto->categoria->id,
                'nombre' => $gasto->categoria->nombre,
                'color' => $gasto->categoria->color,
                'icono' => $gasto->categoria->icono,
            ],
            'observaciones' => $gasto->observaciones,
        ];
    }

    /**
     * Convierte un ingreso en un array listo para la interfaz.
     *
     * @return array<string, mixed>
     */
    private function serializeIncome(Ingreso $ingreso): array
    {
        return [
            'id' => $ingreso->id,
            'titulo' => $ingreso->titulo,
            'importe' => round((float) $ingreso->importe, 2),
            'fecha' => $ingreso->fecha->format('Y-m-d'),
            'fecha_label' => $ingreso->fecha->format('d/m/Y'),
            'observaciones' => $ingreso->observaciones,
        ];
    }

    /**
     * Resuelve el mes solicitado o usa el mes actual por defecto.
     */
    private function resolveMonth(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
