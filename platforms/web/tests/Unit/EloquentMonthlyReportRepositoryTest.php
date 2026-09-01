<?php

namespace Tests\Unit;

use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use App\Repositories\EloquentMonthlyReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentMonthlyReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_monthly_payload_with_filtered_movements_and_totals(): void
    {
        $inversiones = Categoria::query()->create([
            'nombre' => 'Inversiones',
            'color' => '#84CC16',
            'icono' => 'inversiones',
        ]);

        $hogar = Categoria::query()->create([
            'nombre' => 'Hogar',
            'color' => '#6366F1',
            'icono' => 'hogar',
        ]);

        Gasto::query()->create([
            'titulo' => 'Compra semanal',
            'importe' => 52.40,
            'fecha' => '2026-04-10',
            'categoria_id' => $inversiones->id,
            'observaciones' => 'Mercado',
        ]);

        Gasto::query()->create([
            'titulo' => 'Alquiler',
            'importe' => 800.00,
            'fecha' => '2026-04-05',
            'categoria_id' => $hogar->id,
            'observaciones' => null,
        ]);

        Gasto::query()->create([
            'titulo' => 'Gasto antiguo',
            'importe' => 99.99,
            'fecha' => '2026-03-30',
            'categoria_id' => $hogar->id,
            'observaciones' => null,
        ]);

        Ingreso::query()->create([
            'titulo' => 'Nomina',
            'importe' => 1800.00,
            'fecha' => '2026-04-01',
            'observaciones' => 'Principal',
        ]);

        Ingreso::query()->create([
            'titulo' => 'Ingreso antiguo',
            'importe' => 50.00,
            'fecha' => '2026-03-01',
            'observaciones' => null,
        ]);

        $payload = app(EloquentMonthlyReportRepository::class)->getPayload('2026-04');

        $this->assertSame('monthly', $payload['page']);
        $this->assertSame('2026-04', $payload['selectedMonthValue']);
        $this->assertSame(852.4, $payload['summary']['totalGastado']);
        $this->assertSame(1800.0, $payload['summary']['totalIngresado']);
        $this->assertSame(52.4, $payload['summary']['totalInvertido']);
        $this->assertSame(2, $payload['summary']['totalMovimientos']);
        $this->assertSame(426.2, $payload['summary']['importeMedio']);
        $this->assertSame(947.6, $payload['summary']['balance']);
        $this->assertCount(2, $payload['gastos']);
        $this->assertCount(1, $payload['ingresos']);
        $this->assertSame('Hogar', $payload['desglose'][0]['nombre']);
        $this->assertSame(800.0, $payload['desglose'][0]['total']);
    }

    public function test_it_does_not_duplicate_fixed_expenses_when_the_month_payload_is_loaded_multiple_times(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Hogar',
            'color' => '#6366F1',
            'icono' => 'hogar',
        ]);

        MovimientoFijo::query()->create([
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'importe' => 450.00,
            'dia' => 1,
            'categoria_id' => $categoria->id,
            'observaciones' => null,
            'activo' => true,
        ]);

        $repository = app(EloquentMonthlyReportRepository::class);

        $repository->getPayload('2026-04');
        $repository->getPayload('2026-04');

        $this->assertSame(1, Gasto::query()->count());
        $this->assertSame(1, Gasto::query()->whereDate('fecha', '2026-04-01')->count());

        $gasto = Gasto::query()->first();
        $this->assertNotNull($gasto);
        $this->assertSame('Alquiler', $gasto->titulo);
        $this->assertNotNull($gasto->movimiento_fijo_id);
    }

    public function test_it_collapses_existing_duplicate_fixed_expenses_for_the_same_day(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Hogar',
            'color' => '#6366F1',
            'icono' => 'hogar',
        ]);

        $movimientoFijo = MovimientoFijo::query()->create([
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'importe' => 450.00,
            'dia' => 1,
            'categoria_id' => $categoria->id,
            'observaciones' => null,
            'activo' => true,
        ]);

        Gasto::query()->create([
            'titulo' => 'Alquiler',
            'importe' => 450.00,
            'fecha' => '2026-04-01',
            'categoria_id' => $categoria->id,
            'movimiento_fijo_id' => $movimientoFijo->id,
            'observaciones' => null,
        ]);

        Gasto::query()->create([
            'titulo' => 'Alquiler',
            'importe' => 450.00,
            'fecha' => '2026-04-01',
            'categoria_id' => $categoria->id,
            'movimiento_fijo_id' => $movimientoFijo->id,
            'observaciones' => null,
        ]);

        app(EloquentMonthlyReportRepository::class)->getPayload('2026-04');

        $this->assertSame(1, Gasto::query()->where('movimiento_fijo_id', $movimientoFijo->id)->whereDate('fecha', '2026-04-01')->count());
    }

    public function test_monthly_savings_only_include_the_ahorro_category(): void
    {
        $ahorro = Categoria::query()->create([
            'nombre' => 'Ahorro',
            'color' => '#22C55E',
            'icono' => 'ahorro',
        ]);

        $seguros = Categoria::query()->create([
            'nombre' => 'Seguros',
            'color' => '#06B6D4',
            'icono' => 'ahorro',
        ]);

        Gasto::query()->create([
            'titulo' => 'Aportacion mensual',
            'importe' => 300,
            'fecha' => '2026-04-10',
            'categoria_id' => $ahorro->id,
        ]);

        Gasto::query()->create([
            'titulo' => 'Seguro del coche',
            'importe' => 125,
            'fecha' => '2026-04-11',
            'categoria_id' => $seguros->id,
        ]);

        $payload = app(EloquentMonthlyReportRepository::class)->getPayload('2026-04');

        $this->assertSame(300.0, $payload['summary']['totalAhorrado']);
    }
}
