<?php

namespace App\Repositories;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Domain\Accounts\Projectors\NormalAccountProjector;
use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use App\Models\MovimientoFijoExcepcion;
use App\Support\BaseCategoryConfig;
use App\Support\ExcelWorkbookImporter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentMonthlyReportRepository implements MonthlyReportRepositoryInterface
{
    public function __construct(
        private readonly NormalAccountProjector $normalAccounts,
    ) {}

    public function getPayload(?string $month): array
    {
        BaseCategoryConfig::syncToDatabase();

        $selectedMonth = $this->resolveMonth($month);
        $this->ensureFixedEntriesForMonth($selectedMonth);
        $normalBalance = $this->normalAccounts->balanceForMonth($selectedMonth);
        $monthStart = $selectedMonth->copy()->startOfMonth();
        $monthEnd = $selectedMonth->copy()->endOfMonth();

        $categorias = Categoria::query()->orderBy('nombre')->get()->map(fn (Categoria $categoria) => [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'color' => $categoria->color,
            'icono' => $categoria->icono,
        ])->values();

        $gastos = Gasto::query()->with('categoria')
            ->whereBetween('fecha', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('fecha')->orderByDesc('id')->get()
            ->map(fn (Gasto $gasto) => $this->serializeExpense($gasto))->values();

        $ingresos = Ingreso::query()
            ->whereBetween('fecha', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderByDesc('fecha')->orderByDesc('id')->get()
            ->map(fn (Ingreso $ingreso) => $this->serializeIncome($ingreso))->values();

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
            ])->get()->map(fn ($item) => [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'color' => $item->color,
                'icono' => $item->icono,
                'total' => round((float) $item->total, 2),
                'movimientos' => (int) $item->movimientos,
            ])->values();

        $totalGastado = (float) $gastos->sum('importe');
        $totalIngresado = (float) $ingresos->sum('importe');
        $totalInvertido = (float) $desglose
            ->filter(fn (array $item) => Str::lower(Str::ascii((string) $item['nombre'])) === 'inversiones'
                || Str::lower(Str::ascii((string) $item['icono'])) === 'inversiones')
            ->sum('total');
        $totalMovimientos = $gastos->count();
        $importeMedio = $totalMovimientos > 0 ? $totalGastado / $totalMovimientos : 0;
        $balance = $totalIngresado - $totalGastado;
        $accountsSummary = [
            'normal' => $normalBalance,
            'ahorro' => round((float) Cuenta::query()->where('tipo', 'ahorro')->sum('saldo_actual'), 2),
        ];

        return [
            'page' => 'monthly',
            'title' => 'Gastos mensuales',
            'selectedMonthLabel' => $selectedMonth->translatedFormat('F Y'),
            'selectedMonthValue' => $selectedMonth->format('Y-m'),
            'accountsSummary' => $accountsSummary,
            'summary' => [
                'totalGastado' => round($totalGastado, 2),
                'totalIngresado' => round($totalIngresado, 2),
                'totalInvertido' => round($totalInvertido, 2),
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

    public function getExportRows(): array
    {
        return Gasto::query()->with('categoria')->orderBy('fecha')->orderBy('id')->get()
            ->map(fn (Gasto $gasto) => [
                'fecha' => $gasto->fecha->format('d/m/Y'),
                'titulo' => $gasto->titulo,
                'categoria' => $gasto->categoria?->nombre ?? 'Sin categoria',
                'importe' => round((float) $gasto->importe, 2),
                'observaciones' => $gasto->observaciones ?? '',
            ])->all();
    }

    public function getIncomeExportRows(): array
    {
        return Ingreso::query()->orderBy('fecha')->orderBy('id')->get()
            ->map(fn (Ingreso $ingreso) => [
                'fecha' => $ingreso->fecha->format('d/m/Y'),
                'titulo' => $ingreso->titulo,
                'importe' => round((float) $ingreso->importe, 2),
                'observaciones' => $ingreso->observaciones ?? '',
            ])->all();
    }

    public function getFixedEntryExportRows(): array
    {
        return MovimientoFijo::query()->with('categoria')->orderBy('tipo')->orderBy('dia')->orderBy('titulo')->get()
            ->map(fn (MovimientoFijo $movimientoFijo) => [
                'tipo' => $movimientoFijo->tipo,
                'titulo' => $movimientoFijo->titulo,
                'categoria' => $movimientoFijo->categoria?->nombre ?? '',
                'importe' => round((float) $movimientoFijo->importe, 2),
                'dia' => (int) $movimientoFijo->dia,
                'activo' => $movimientoFijo->activo ? 'Si' : 'No',
                'observaciones' => $movimientoFijo->observaciones ?? '',
            ])->all();
    }

    public function getCategoryExportRows(): array
    {
        return Categoria::query()->withCount('gastos')->orderBy('nombre')->get()
            ->map(fn (Categoria $categoria) => [
                'nombre' => $categoria->nombre,
                'color' => $categoria->color,
                'icono' => $categoria->icono,
                'movimientos' => (int) $categoria->gastos_count,
            ])->all();
    }

    public function getAccountExportRows(): array
    {
        return Cuenta::query()->orderBy('tipo')->orderBy('nombre')->get()
            ->map(fn (Cuenta $cuenta) => [
                'nombre' => $cuenta->nombre,
                'tipo' => $cuenta->tipo,
                'saldo_inicial' => round((float) $cuenta->saldo_inicial, 2),
                'saldo_actual' => round((float) $cuenta->saldo_actual, 2),
                'ahorro_mensual' => $cuenta->ahorro_mensual !== null ? round((float) $cuenta->ahorro_mensual, 2) : null,
                'ultimo_mes_ahorro_aplicado' => $cuenta->ultimo_mes_ahorro_aplicado?->format('Y-m-d') ?? '',
            ])->all();
    }

    public function importWorkbook(string $contents): array
    {
        BaseCategoryConfig::syncToDatabase();

        return ExcelWorkbookImporter::import($contents);
    }

    public function createExpense(array $data): array
    {
        return $this->serializeExpense(Gasto::query()->create($data)->load('categoria'));
    }

    public function updateExpense(Gasto $gasto, array $data): array
    {
        $gasto->update($data);
        $gasto->load('categoria');
        return $this->serializeExpense($gasto);
    }

    public function deleteExpense(Gasto $gasto): int
    {
        $gastoId = $gasto->id;
        $gasto->delete();
        return $gastoId;
    }

    public function createIncome(array $data): array
    {
        return $this->serializeIncome(Ingreso::query()->create($data));
    }

    public function updateIncome(Ingreso $ingreso, array $data): array
    {
        $ingreso->update($data);

        return $this->serializeIncome($ingreso->fresh());
    }

    public function deleteIncome(Ingreso $ingreso): int
    {
        $ingresoId = $ingreso->id;
        $ingreso->delete();
        return $ingresoId;
    }

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

    private function resolveMonth(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * Genera los movimientos del mes a partir de los fijos activos si aun no existen.
     */
    private function ensureFixedEntriesForMonth(Carbon $selectedMonth): void
    {
        $fixedEntries = MovimientoFijo::query()
            ->where('activo', true)
            ->get();
        $monthStart = $selectedMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $selectedMonth->copy()->endOfMonth()->toDateString();
        $exceptions = MovimientoFijoExcepcion::query()
            ->whereBetween('fecha', [$monthStart, $monthEnd])
            ->get()
            ->keyBy(fn (MovimientoFijoExcepcion $exception) => $this->buildExceptionKey(
                $exception->movimiento_fijo_id,
                $exception->tipo,
                $exception->fecha->toDateString()
            ));
        $normalAccountsNeedRebuild = false;

        foreach ($fixedEntries as $fixedEntry) {
            $date = $selectedMonth->copy()->day(min($fixedEntry->dia, $selectedMonth->daysInMonth))->toDateString();
            $exceptionKey = $this->buildExceptionKey($fixedEntry->id, $fixedEntry->tipo, $date);

            if ($exceptions->has($exceptionKey)) {
                continue;
            }

            if ($fixedEntry->tipo === 'gasto') {
                $normalAccountsNeedRebuild = $this->ensureFixedExpense($fixedEntry, $date) || $normalAccountsNeedRebuild;

                continue;
            }

            $normalAccountsNeedRebuild = $this->ensureFixedIncome($fixedEntry, $date) || $normalAccountsNeedRebuild;
        }

        if ($normalAccountsNeedRebuild) {
            $this->normalAccounts->rebuildUntil($selectedMonth);
        }
    }

    /**
     * Construye una clave unica para buscar excepciones del mes.
     */
    private function buildExceptionKey(int $movimientoFijoId, string $tipo, string $date): string
    {
        return $movimientoFijoId.'|'.$tipo.'|'.$date;
    }

    /**
     * Garantiza que exista un unico gasto generado por el movimiento fijo para ese dia.
     */
    private function ensureFixedExpense(MovimientoFijo $fixedEntry, string $date): bool
    {
        $matchingExpenses = Gasto::query()
            ->where('movimiento_fijo_id', $fixedEntry->id)
            ->whereDate('fecha', $date)
            ->orderBy('id')
            ->get();

        $changed = false;

        if ($matchingExpenses->count() > 1) {
            Gasto::query()
                ->whereKey($matchingExpenses->slice(1)->pluck('id')->all())
                ->delete();

            $matchingExpenses = $matchingExpenses->take(1)->values();
            $changed = true;
        }

        if ($matchingExpenses->isNotEmpty()) {
            return $changed;
        }

        $legacyExpense = Gasto::query()
            ->whereNull('movimiento_fijo_id')
            ->whereDate('fecha', $date)
            ->where('titulo', $fixedEntry->titulo)
            ->where('importe', $fixedEntry->importe)
            ->where('categoria_id', $fixedEntry->categoria_id)
            ->when(
                $fixedEntry->observaciones === null,
                fn ($query) => $query->whereNull('observaciones'),
                fn ($query) => $query->where('observaciones', $fixedEntry->observaciones)
            )
            ->first();

        if ($legacyExpense !== null) {
            $legacyExpense->update([
                'movimiento_fijo_id' => $fixedEntry->id,
            ]);

            return true;
        }

        Gasto::query()->create([
            'titulo' => $fixedEntry->titulo,
            'importe' => $fixedEntry->importe,
            'fecha' => $date,
            'categoria_id' => $fixedEntry->categoria_id,
            'movimiento_fijo_id' => $fixedEntry->id,
            'observaciones' => $fixedEntry->observaciones,
        ]);

        return true;
    }

    /**
     * Garantiza que exista un unico ingreso generado por el movimiento fijo para ese dia.
     */
    private function ensureFixedIncome(MovimientoFijo $fixedEntry, string $date): bool
    {
        $matchingIncomes = Ingreso::query()
            ->where('movimiento_fijo_id', $fixedEntry->id)
            ->whereDate('fecha', $date)
            ->orderBy('id')
            ->get();

        $changed = false;

        if ($matchingIncomes->count() > 1) {
            Ingreso::query()
                ->whereKey($matchingIncomes->slice(1)->pluck('id')->all())
                ->delete();

            $matchingIncomes = $matchingIncomes->take(1)->values();
            $changed = true;
        }

        if ($matchingIncomes->isNotEmpty()) {
            return $changed;
        }

        $legacyIncome = Ingreso::query()
            ->whereNull('movimiento_fijo_id')
            ->whereDate('fecha', $date)
            ->where('titulo', $fixedEntry->titulo)
            ->where('importe', $fixedEntry->importe)
            ->when(
                $fixedEntry->observaciones === null,
                fn ($query) => $query->whereNull('observaciones'),
                fn ($query) => $query->where('observaciones', $fixedEntry->observaciones)
            )
            ->first();

        if ($legacyIncome !== null) {
            $legacyIncome->update([
                'movimiento_fijo_id' => $fixedEntry->id,
            ]);

            return true;
        }

        Ingreso::query()->create([
            'titulo' => $fixedEntry->titulo,
            'importe' => $fixedEntry->importe,
            'fecha' => $date,
            'movimiento_fijo_id' => $fixedEntry->id,
            'observaciones' => $fixedEntry->observaciones,
        ]);

        return true;
    }
}
