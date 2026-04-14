<?php

namespace App\Application\Movements;

use App\Domain\Movements\Events\IncomeCreated;
use App\Domain\Movements\Events\IncomeDeleted;
use App\Domain\Movements\Events\IncomeUpdated;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use App\Models\MovimientoFijoExcepcion;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta los comandos de aplicacion relacionados con ingresos.
 */
class IncomeApplicationService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $ingreso = Ingreso::query()->create($data);

            event(new IncomeCreated(
                ingresoId: $ingreso->id,
                amount: (float) $ingreso->importe,
            ));

            return $this->serializeIncome($ingreso);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(Ingreso $ingreso, array $data): array
    {
        return DB::transaction(function () use ($ingreso, $data): array {
            $previousAmount = (float) $ingreso->importe;

            $ingreso->update($data);
            $ingreso = $ingreso->fresh();

            event(new IncomeUpdated(
                ingresoId: $ingreso->id,
                previousAmount: $previousAmount,
                currentAmount: (float) $ingreso->importe,
            ));

            return $this->serializeIncome($ingreso);
        });
    }

    public function delete(Ingreso $ingreso): int
    {
        return DB::transaction(function () use ($ingreso): int {
            $ingresoId = $ingreso->id;
            $amount = (float) $ingreso->importe;
            $movimientoFijoId = $ingreso->movimiento_fijo_id ?? $this->resolveFixedIncomeId($ingreso);

            if ($movimientoFijoId !== null) {
                $exceptionDate = $ingreso->fecha->toDateString();
                $exceptionExists = MovimientoFijoExcepcion::query()
                    ->where('movimiento_fijo_id', $movimientoFijoId)
                    ->where('tipo', 'ingreso')
                    ->whereDate('fecha', $exceptionDate)
                    ->exists();

                if (! $exceptionExists) {
                    MovimientoFijoExcepcion::query()->create([
                        'movimiento_fijo_id' => $movimientoFijoId,
                        'tipo' => 'ingreso',
                        'fecha' => $exceptionDate,
                    ]);
                }
            }

            $ingreso->delete();

            event(new IncomeDeleted(
                ingresoId: $ingresoId,
                amount: $amount,
            ));

            return $ingresoId;
        });
    }

    /**
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
     * Intenta localizar el movimiento fijo original para ingresos antiguos sin referencia.
     */
    private function resolveFixedIncomeId(Ingreso $ingreso): ?int
    {
        return MovimientoFijo::query()
            ->where('tipo', 'ingreso')
            ->where('titulo', $ingreso->titulo)
            ->where('dia', $ingreso->fecha->day)
            ->where('activo', true)
            ->where('importe', $ingreso->importe)
            ->value('id');
    }
}
