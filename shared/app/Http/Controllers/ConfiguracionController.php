<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Domain\Accounts\Projectors\NormalAccountProjector;
use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use App\Models\MovimientoFijoExcepcion;
use App\Support\BaseCategoryConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestiona la pantalla de configuracion y los movimientos fijos mensuales.
 *
 * @autor Antonio Martin Leon
 */
class ConfiguracionController extends Controller
{
    public function __construct(
        private readonly NormalAccountProjector $normalAccounts,
    ) {}

    /**
     * Muestra la configuracion o devuelve su payload en JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->buildPayload();

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }

    /**
     * Crea un nuevo movimiento fijo mensual.
     */
    public function storeMovimientoFijo(Request $request): JsonResponse
    {
        $movimientoFijo = MovimientoFijo::query()
            ->create($this->validateMovimientoFijo($request))
            ->load('categoria');

        return response()->json([
            'message' => 'Movimiento fijo guardado correctamente.',
            'fixedEntry' => $this->serializeFixedEntry($movimientoFijo),
        ]);
    }

    /**
     * Actualiza un movimiento fijo mensual existente.
     */
    public function updateMovimientoFijo(Request $request, MovimientoFijo $movimientoFijo): JsonResponse
    {
        $movimientoFijo->update($this->validateMovimientoFijo($request));
        $movimientoFijo->load('categoria');

        return response()->json([
            'message' => 'Movimiento fijo actualizado correctamente.',
            'fixedEntry' => $this->serializeFixedEntry($movimientoFijo),
        ]);
    }

    /**
     * Elimina un movimiento fijo mensual.
     */
    public function destroyMovimientoFijo(MovimientoFijo $movimientoFijo): JsonResponse
    {
        $id = $movimientoFijo->id;
        $movimientoFijo->delete();

        return response()->json([
            'message' => 'Movimiento fijo eliminado correctamente.',
            'id' => $id,
        ]);
    }

    /**
     * Importa un fichero Excel/XML previamente exportado por la app.
     */
    public function importExcel(Request $request, MonthlyReportRepositoryInterface $monthlyReports): JsonResponse
    {
        $validated = $request->validate(
            [
                'archivo' => ['required', 'file', 'max:5120'],
            ],
            [
                'archivo.required' => 'Debes seleccionar un fichero para importar.',
                'archivo.file' => 'El fichero seleccionado no es valido.',
                'archivo.max' => 'El fichero no puede superar los 5 MB.',
            ]
        );

        $file = $validated['archivo'];
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['xls', 'xml'], true)) {
            return response()->json([
                'message' => 'Selecciona un fichero .xls o .xml generado por la exportacion de AppGastos.',
            ], 422);
        }

        $summary = $monthlyReports->importWorkbook((string) file_get_contents($file->getRealPath()));
        $this->normalAccounts->rebuild();

        return response()->json([
            'message' => sprintf(
                'Importacion completada. Categorias sincronizadas: %d. Gastos importados: %d, omitidos: %d. Ingresos importados: %d, omitidos: %d. Movimientos fijos importados: %d, omitidos: %d. Cuentas importadas: %d, actualizadas: %d.',
                $summary['categories_imported'],
                $summary['expenses_imported'],
                $summary['expenses_skipped'],
                $summary['incomes_imported'],
                $summary['incomes_skipped'],
                $summary['fixed_entries_imported'],
                $summary['fixed_entries_skipped'],
                $summary['accounts_imported'],
                $summary['accounts_skipped']
            ),
            'summary' => $summary,
        ]);
    }

    /**
     * Crea una nueva cuenta.
     */
    public function storeCuenta(Request $request): JsonResponse
    {
        $validated = $this->validateCuenta($request);
        $cuenta = Cuenta::query()->create($this->buildCuentaPayload($validated));
        $this->syncNormalAccountsIfNeeded($cuenta->tipo);

        return response()->json([
            'message' => 'Cuenta guardada correctamente.',
            'account' => $this->serializeAccount($cuenta->fresh()),
        ]);
    }

    /**
     * Actualiza una cuenta existente.
     */
    public function updateCuenta(Request $request, Cuenta $cuenta): JsonResponse
    {
        $previousType = $cuenta->tipo;
        $validated = $this->validateCuenta($request);
        $cuenta->update($this->buildCuentaPayload($validated, $cuenta));
        $this->syncNormalAccountsIfNeeded($previousType, $cuenta->fresh()->tipo);

        return response()->json([
            'message' => 'Cuenta actualizada correctamente.',
            'account' => $this->serializeAccount($cuenta->fresh()),
        ]);
    }

    /**
     * Elimina una cuenta.
     */
    public function destroyCuenta(Cuenta $cuenta): JsonResponse
    {
        $id = $cuenta->id;
        $type = $cuenta->tipo;
        $cuenta->delete();
        $this->syncNormalAccountsIfNeeded($type);

        return response()->json([
            'message' => 'Cuenta eliminada correctamente.',
            'id' => $id,
        ]);
    }

    /**
     * Retira una cantidad de una cuenta de ahorro.
     */
    public function retirarCuenta(Request $request, Cuenta $cuenta): JsonResponse
    {
        if ($cuenta->tipo !== 'ahorro') {
            return response()->json([
                'message' => 'Solo puedes retirar dinero de una cuenta de ahorro.',
            ], 422);
        }

        $validated = $request->validate(
            [
                'importe' => ['required', 'numeric', 'min:0.01'],
            ],
            [
                'importe.required' => 'Debes indicar la cantidad a retirar.',
                'importe.numeric' => 'La cantidad a retirar debe ser un numero valido.',
                'importe.min' => 'La cantidad a retirar debe ser mayor que 0.',
            ]
        );

        $importe = round((float) $validated['importe'], 2);

        if ((float) $cuenta->saldo_actual < $importe) {
            return response()->json([
                'message' => 'No puedes retirar mas dinero del saldo disponible en la cuenta de ahorro.',
            ], 422);
        }

        $cuenta->update([
            'saldo_actual' => round((float) $cuenta->saldo_actual - $importe, 2),
        ]);

        return response()->json([
            'message' => 'Cantidad retirada correctamente.',
            'account' => $this->serializeAccount($cuenta->fresh()),
        ]);
    }

    /**
     * Borra todos los datos generados por el usuario y conserva las categorias base.
     */
    public function destroyData(): JsonResponse
    {
        DB::transaction(function (): void {
            Gasto::query()->delete();
            Ingreso::query()->delete();
            MovimientoFijoExcepcion::query()->delete();
            MovimientoFijo::query()->delete();
            Cuenta::query()->delete();
            Categoria::query()
                ->whereNotIn('nombre', BaseCategoryConfig::names())
                ->delete();
        });

        BaseCategoryConfig::syncToDatabase();

        return response()->json([
            'message' => 'Todos los datos se han borrado correctamente. Las categorias base siguen disponibles.',
        ]);
    }

    /**
     * Construye el payload de configuracion.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        BaseCategoryConfig::syncToDatabase();

        return [
            'page' => 'settings',
            'title' => 'Configuracion',
            'settings' => [
                'exportUrl' => route('configuracion.export.gastos'),
                'exportShareUrl' => route('configuracion.export.gastos.share'),
                'nativeShareExportEnabled' => $this->supportsNativeWorkbookShare(),
                'clearDataUrl' => route('configuracion.datos.destroy'),
                'fixedEntries' => MovimientoFijo::query()
                    ->with('categoria')
                    ->orderBy('tipo')
                    ->orderBy('titulo')
                    ->get()
                    ->map(fn (MovimientoFijo $movimientoFijo) => $this->serializeFixedEntry($movimientoFijo))
                    ->values()
                    ->all(),
                'categories' => Categoria::query()
                    ->orderBy('nombre')
                    ->get()
                    ->map(fn (Categoria $categoria) => [
                        'id' => $categoria->id,
                        'nombre' => $categoria->nombre,
                        'color' => $categoria->color,
                        'icono' => $categoria->icono,
                    ])
                    ->values()
                    ->all(),
                'accounts' => Cuenta::query()
                    ->orderBy('tipo')
                    ->orderBy('nombre')
                    ->get()
                    ->map(fn (Cuenta $cuenta) => $this->serializeAccount($cuenta))
                    ->values()
                    ->all(),
                'baseCategories' => BaseCategoryConfig::all(),
            ],
        ];
    }

    /**
     * Indica si el wrapper actual puede compartir ficheros con NativePHP Mobile.
     */
    private function supportsNativeWorkbookShare(): bool
    {
        return (bool) config('nativephp-internal.running')
            && filled(config('nativephp-internal.tempdir'))
            && class_exists('Native\Mobile\Facades\Share');
    }

    /**
     * Valida los datos necesarios para crear o actualizar un movimiento fijo.
     *
     * @return array<string, mixed>
     */
    private function validateMovimientoFijo(Request $request): array
    {
        return $request->validate(
            [
                'tipo' => ['required', Rule::in(['gasto', 'ingreso'])],
                'titulo' => ['required', 'string', 'max:255'],
                'importe' => ['required', 'numeric', 'min:0.01'],
                'dia' => ['required', 'integer', 'min:1', 'max:31'],
                'categoria_id' => ['nullable', 'exists:categorias,id', Rule::requiredIf($request->input('tipo') === 'gasto')],
                'observaciones' => ['nullable', 'string'],
                'activo' => ['nullable', 'boolean'],
            ],
            [
                'tipo.required' => 'Debes seleccionar si el movimiento fijo es un gasto o un ingreso.',
                'tipo.in' => 'El tipo de movimiento fijo no es valido.',
                'titulo.required' => 'El titulo del movimiento fijo es obligatorio.',
                'titulo.max' => 'El titulo del movimiento fijo no puede superar los 255 caracteres.',
                'importe.required' => 'El importe del movimiento fijo es obligatorio.',
                'importe.numeric' => 'El importe del movimiento fijo debe ser un numero valido.',
                'importe.min' => 'El importe del movimiento fijo debe ser mayor que 0.',
                'dia.required' => 'El dia de aplicacion es obligatorio.',
                'dia.integer' => 'El dia de aplicacion debe ser un numero entero.',
                'dia.min' => 'El dia de aplicacion debe estar entre 1 y 31.',
                'dia.max' => 'El dia de aplicacion debe estar entre 1 y 31.',
                'categoria_id.required' => 'Debes seleccionar una categoria para el gasto fijo.',
                'categoria_id.exists' => 'La categoria seleccionada no es valida.',
                'observaciones.string' => 'Las observaciones deben ser texto.',
            ]
        );
    }

    /**
     * Valida los datos necesarios para crear o actualizar una cuenta.
     *
     * @return array<string, mixed>
     */
    private function validateCuenta(Request $request): array
    {
        return $request->validate(
            [
                'nombre' => ['required', 'string', 'max:255'],
                'tipo' => ['required', Rule::in(['normal', 'ahorro'])],
                'saldo_inicial' => ['required', 'numeric', 'min:0'],
            ],
            [
                'nombre.required' => 'El nombre de la cuenta es obligatorio.',
                'nombre.max' => 'El nombre de la cuenta no puede superar los 255 caracteres.',
                'tipo.required' => 'Debes seleccionar el tipo de cuenta.',
                'tipo.in' => 'El tipo de cuenta no es valido.',
                'saldo_inicial.required' => 'El importe inicial es obligatorio.',
                'saldo_inicial.numeric' => 'El importe inicial debe ser un numero valido.',
                'saldo_inicial.min' => 'El importe inicial no puede ser negativo.',
            ]
        );
    }

    /**
     * Convierte un movimiento fijo a un formato listo para la interfaz.
     *
     * @return array<string, mixed>
     */
    private function serializeFixedEntry(MovimientoFijo $movimientoFijo): array
    {
        return [
            'id' => $movimientoFijo->id,
            'tipo' => $movimientoFijo->tipo,
            'titulo' => $movimientoFijo->titulo,
            'importe' => round((float) $movimientoFijo->importe, 2),
            'dia' => $movimientoFijo->dia,
            'categoria_id' => $movimientoFijo->categoria_id,
            'categoria' => $movimientoFijo->categoria ? [
                'id' => $movimientoFijo->categoria->id,
                'nombre' => $movimientoFijo->categoria->nombre,
                'color' => $movimientoFijo->categoria->color,
                'icono' => $movimientoFijo->categoria->icono,
            ] : null,
            'observaciones' => $movimientoFijo->observaciones,
            'activo' => $movimientoFijo->activo,
        ];
    }

    /**
     * Construye la carga util para crear o actualizar una cuenta.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function buildCuentaPayload(array $validated, ?Cuenta $cuenta = null): array
    {
        $tipo = $validated['tipo'];
        $saldoInicial = round((float) $validated['saldo_inicial'], 2);
        if ($cuenta === null) {
            return [
                'nombre' => trim((string) $validated['nombre']),
                'tipo' => $tipo,
                'saldo_inicial' => $saldoInicial,
                'saldo_actual' => $saldoInicial,
                'ahorro_mensual' => null,
                'ultimo_mes_ahorro_aplicado' => null,
            ];
        }

        $deltaSaldoInicial = $saldoInicial - (float) $cuenta->saldo_inicial;
        $saldoActual = round((float) $cuenta->saldo_actual + $deltaSaldoInicial, 2);

        return [
            'nombre' => trim((string) $validated['nombre']),
            'tipo' => $tipo,
            'saldo_inicial' => $saldoInicial,
            'saldo_actual' => $saldoActual,
            'ahorro_mensual' => null,
            'ultimo_mes_ahorro_aplicado' => null,
        ];
    }

    /**
     * Convierte una cuenta a un formato listo para la interfaz.
     *
     * @return array<string, mixed>
     */
    private function serializeAccount(Cuenta $cuenta): array
    {
        return [
            'id' => $cuenta->id,
            'nombre' => $cuenta->nombre,
            'tipo' => $cuenta->tipo,
            'saldo_inicial' => round((float) $cuenta->saldo_inicial, 2),
            'saldo_actual' => round((float) $cuenta->saldo_actual, 2),
            'ahorro_mensual' => $cuenta->ahorro_mensual !== null ? round((float) $cuenta->ahorro_mensual, 2) : null,
            'ultimo_mes_ahorro_aplicado' => $cuenta->ultimo_mes_ahorro_aplicado?->toDateString(),
        ];
    }

    /**
     * Recalcula la proyeccion de la cuenta normal cuando cambia ese agregado.
     */
    private function syncNormalAccountsIfNeeded(string ...$types): void
    {
        if (in_array('normal', $types, true)) {
            $this->normalAccounts->rebuild();
        }
    }
}
