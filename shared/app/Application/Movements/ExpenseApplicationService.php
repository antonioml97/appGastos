<?php

namespace App\Application\Movements;

use App\Domain\Movements\Events\ExpenseCreated;
use App\Domain\Movements\Events\ExpenseDeleted;
use App\Domain\Movements\Events\ExpenseUpdated;
use App\Models\Gasto;
use App\Models\MovimientoFijo;
use App\Models\MovimientoFijoExcepcion;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta los comandos de aplicacion relacionados con gastos.
 */
class ExpenseApplicationService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $gasto = Gasto::query()->create($data)->load('categoria');

            event(new ExpenseCreated(
                gastoId: $gasto->id,
                amount: (float) $gasto->importe,
                categoryName: $gasto->categoria?->nombre,
                categoryIcon: $gasto->categoria?->icono,
            ));

            return $this->serializeExpense($gasto);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Gasto $gasto, array $data): array
    {
        return DB::transaction(function () use ($gasto, $data): array {
            $gasto->load('categoria');

            $previousAmount = (float) $gasto->importe;
            $previousCategoryName = $gasto->categoria?->nombre;
            $previousCategoryIcon = $gasto->categoria?->icono;

            $gasto->update($data);
            $gasto->load('categoria');

            event(new ExpenseUpdated(
                gastoId: $gasto->id,
                previousAmount: $previousAmount,
                currentAmount: (float) $gasto->importe,
                previousCategoryName: $previousCategoryName,
                currentCategoryName: $gasto->categoria?->nombre,
                previousCategoryIcon: $previousCategoryIcon,
                currentCategoryIcon: $gasto->categoria?->icono,
            ));

            return $this->serializeExpense($gasto);
        });
    }

    public function delete(Gasto $gasto): int
    {
        return DB::transaction(function () use ($gasto): int {
            $gasto->load('categoria');

            $gastoId = $gasto->id;
            $amount = (float) $gasto->importe;
            $categoryName = $gasto->categoria?->nombre;
            $categoryIcon = $gasto->categoria?->icono;
            $movimientoFijoId = $gasto->movimiento_fijo_id ?? $this->resolveFixedExpenseId($gasto);

            if ($movimientoFijoId !== null) {
                $exceptionDate = $gasto->fecha->toDateString();
                $exceptionExists = MovimientoFijoExcepcion::query()
                    ->where('movimiento_fijo_id', $movimientoFijoId)
                    ->where('tipo', 'gasto')
                    ->whereDate('fecha', $exceptionDate)
                    ->exists();

                if (! $exceptionExists) {
                    MovimientoFijoExcepcion::query()->create([
                        'movimiento_fijo_id' => $movimientoFijoId,
                        'tipo' => 'gasto',
                        'fecha' => $exceptionDate,
                    ]);
                }
            }

            $gasto->delete();

            event(new ExpenseDeleted(
                gastoId: $gastoId,
                amount: $amount,
                categoryName: $categoryName,
                categoryIcon: $categoryIcon,
            ));

            return $gastoId;
        });
    }

    /**
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
     * Intenta localizar el movimiento fijo original para gastos antiguos sin referencia.
     */
    private function resolveFixedExpenseId(Gasto $gasto): ?int
    {
        return MovimientoFijo::query()
            ->where('tipo', 'gasto')
            ->where('titulo', $gasto->titulo)
            ->where('categoria_id', $gasto->categoria_id)
            ->where('dia', $gasto->fecha->day)
            ->where('activo', true)
            ->where('importe', $gasto->importe)
            ->value('id');
    }
}
