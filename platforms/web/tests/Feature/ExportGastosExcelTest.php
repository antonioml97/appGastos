<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ExportGastosExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_it_exports_expenses_incomes_and_categories_in_a_single_excel_file(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Supermercado',
            'color' => '#84CC16',
            'icono' => 'comida',
        ]);

        Gasto::query()->create([
            'titulo' => 'Compra del mes',
            'importe' => 145.70,
            'fecha' => '2026-04-10',
            'categoria_id' => $categoria->id,
            'observaciones' => 'Incluye limpieza',
        ]);

        Ingreso::query()->create([
            'titulo' => 'Nomina',
            'importe' => 1900.00,
            'fecha' => '2026-04-01',
            'observaciones' => 'Empresa',
        ]);

        $response = $this->get(route('configuracion.export.gastos'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('content-disposition');
        $response->assertSee('Worksheet ss:Name="Resumen"', false);
        $response->assertSee('Worksheet ss:Name="Gastos"', false);
        $response->assertSee('Worksheet ss:Name="Ingresos"', false);
        $response->assertSee('Worksheet ss:Name="Movimientos fijos"', false);
        $response->assertSee('Worksheet ss:Name="Categorias"', false);
        $response->assertSee('AppGastos - Copia de seguridad', false);
        $response->assertSee('Compra del mes', false);
        $response->assertSee('Nomina', false);
        $response->assertSee('Supermercado', false);
    }

    public function test_it_imports_a_full_workbook_with_the_same_export_format(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Supermercado',
            'color' => '#84CC16',
            'icono' => 'comida',
        ]);

        Gasto::query()->create([
            'titulo' => 'Compra del mes',
            'importe' => 145.70,
            'fecha' => '2026-04-10',
            'categoria_id' => $categoria->id,
            'observaciones' => 'Incluye limpieza',
        ]);

        Ingreso::query()->create([
            'titulo' => 'Nomina',
            'importe' => 1900.00,
            'fecha' => '2026-04-01',
            'observaciones' => 'Empresa',
        ]);

        MovimientoFijo::query()->create([
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'importe' => 650.00,
            'dia' => 1,
            'categoria_id' => $categoria->id,
            'observaciones' => 'Mensual',
            'activo' => true,
        ]);

        $exportResponse = $this->get(route('configuracion.export.gastos'));
        $workbook = (string) $exportResponse->getContent();

        MovimientoFijo::query()->delete();
        Gasto::query()->delete();
        Ingreso::query()->delete();
        Categoria::query()->delete();

        $response = $this->post(route('configuracion.importar-excel'), [
            'archivo' => UploadedFile::fake()->createWithContent('appgastos-export.xls', $workbook),
        ]);

        $response->assertOk();
        $response->assertJsonPath('summary.categories_imported', 1);
        $response->assertJsonPath('summary.expenses_imported', 1);
        $response->assertJsonPath('summary.incomes_imported', 1);
        $response->assertJsonPath('summary.fixed_entries_imported', 1);

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Supermercado',
        ]);
        $this->assertDatabaseHas('gastos', [
            'titulo' => 'Compra del mes',
        ]);
        $this->assertDatabaseHas('ingresos', [
            'titulo' => 'Nomina',
        ]);
        $this->assertDatabaseHas('movimientos_fijos', [
            'titulo' => 'Alquiler',
        ]);
    }

    public function test_it_exports_fixed_entries_in_the_same_excel_file(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Vivienda',
            'color' => '#0EA5E9',
            'icono' => 'casa',
        ]);

        MovimientoFijo::query()->create([
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'importe' => 650.00,
            'dia' => 1,
            'categoria_id' => $categoria->id,
            'observaciones' => 'Mensual',
            'activo' => true,
        ]);

        $response = $this->get(route('configuracion.export.gastos'));

        $response->assertOk();
        $response->assertSee('Worksheet ss:Name="Movimientos fijos"', false);
        $response->assertSee('Alquiler', false);
        $response->assertSee('Vivienda', false);
        $response->assertSee('Mensual', false);
    }

    public function test_it_imports_fixed_entries_from_the_exported_excel_file(): void
    {
        $categoria = Categoria::query()->create([
            'nombre' => 'Vivienda',
            'color' => '#0EA5E9',
            'icono' => 'casa',
        ]);

        MovimientoFijo::query()->create([
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'importe' => 650.00,
            'dia' => 1,
            'categoria_id' => $categoria->id,
            'observaciones' => 'Mensual',
            'activo' => true,
        ]);

        $exportResponse = $this->get(route('configuracion.export.gastos'));
        $workbook = (string) $exportResponse->getContent();

        MovimientoFijo::query()->delete();
        Categoria::query()->where('nombre', 'Vivienda')->delete();

        $response = $this->post(route('configuracion.importar-excel'), [
            'archivo' => UploadedFile::fake()->createWithContent('appgastos-export.xls', $workbook),
        ]);

        $response->assertOk();
        $response->assertJsonPath('summary.fixed_entries_imported', 1);
        $response->assertJsonPath('summary.fixed_entries_skipped', 0);

        $this->assertDatabaseHas('movimientos_fijos', [
            'tipo' => 'gasto',
            'titulo' => 'Alquiler',
            'dia' => 1,
            'activo' => 1,
        ]);
    }

    public function test_it_prepares_the_excel_for_native_mobile_sharing(): void
    {
        if (! class_exists('Native\Mobile\Facades\Share')) {
            $this->markTestSkipped('NativePHP Mobile no esta disponible en este wrapper.');
        }

        Categoria::query()->create([
            'nombre' => 'Hogar',
            'color' => '#F59E0B',
            'icono' => 'casa',
        ]);

        config([
            'nativephp-internal.running' => true,
            'nativephp-internal.tempdir' => storage_path('framework/cache/native-export-tests'),
            'filesystems.disks.temp' => [
                'driver' => 'local',
                'root' => storage_path('framework/cache/native-export-tests'),
                'throw' => false,
            ],
        ]);

        $response = $this->postJson(route('configuracion.export.gastos.share'));

        $response->assertOk();
        $response->assertJsonPath('message', 'Se ha abierto el panel para exportar el Excel.');
        $response->assertJsonPath('filename', 'appgastos-export-'.now()->format('Y-m-d').'.xls');

        $exportPath = storage_path('framework/cache/native-export-tests/appgastos-exports/appgastos-export-'.now()->format('Y-m-d').'.xls');

        $this->assertFileExists($exportPath);
        $this->assertStringContainsString('Worksheet ss:Name="Categorias"', (string) file_get_contents($exportPath));
    }
}
