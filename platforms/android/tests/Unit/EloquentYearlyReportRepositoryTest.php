<?php

namespace Tests\Unit;

use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Repositories\EloquentYearlyReportRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentYearlyReportRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_expenses_and_incomes_by_month(): void
    {
        $category = Categoria::query()->create([
            'nombre' => 'Hogar',
            'color' => '#6366F1',
            'icono' => 'hogar',
        ]);

        Gasto::query()->create([
            'titulo' => 'Alquiler',
            'importe' => 750,
            'fecha' => '2026-02-01',
            'categoria_id' => $category->id,
        ]);

        Ingreso::query()->create([
            'titulo' => 'Nomina',
            'importe' => 2000,
            'fecha' => '2026-02-28',
        ]);

        $payload = app(EloquentYearlyReportRepository::class)->getPayload(2026);
        $february = collect($payload['monthly'])->firstWhere('month', 2);

        $this->assertSame('yearly', $payload['page']);
        $this->assertSame(2026, $payload['selectedYear']);
        $this->assertSame(750.0, $february['expense']);
        $this->assertSame(2000.0, $february['income']);
        $this->assertSame(1250.0, $february['balance']);
        $this->assertSame(750.0, $payload['summary']['totalGastado']);
        $this->assertSame(2000.0, $payload['summary']['totalIngresado']);
    }
}
