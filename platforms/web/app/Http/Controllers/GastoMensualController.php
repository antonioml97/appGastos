<?php

namespace App\Http\Controllers;

use App\Application\Movements\ExpenseApplicationService;
use App\Application\Movements\IncomeApplicationService;
use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Models\Gasto;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Gestiona la pantalla mensual y las operaciones sobre gastos e ingresos.
 *
 * @autor Antonio Martin Leon
 */
class GastoMensualController extends Controller
{
    /**
     * Inyecta el repositorio encargado del resumen mensual.
     */
    public function __construct(
        private readonly MonthlyReportRepositoryInterface $monthlyReports,
        private readonly ExpenseApplicationService $expenses,
        private readonly IncomeApplicationService $incomes,
    ) {}

    /**
     * Muestra la vista mensual o devuelve su payload en JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->monthlyReports->getPayload($request->string('mes')->toString());

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }

    /**
     * Crea un nuevo gasto mensual.
     */
    public function storeGasto(Request $request): JsonResponse
    {
        $validated = $this->validateGasto($request);

        return response()->json([
            'message' => 'Gasto anadido correctamente.',
            'gasto' => $this->expenses->create($validated),
        ]);
    }

    /**
     * Actualiza un gasto existente.
     */
    public function updateGasto(Request $request, Gasto $gasto): JsonResponse
    {
        $validated = $this->validateGasto($request);

        return response()->json([
            'message' => 'Gasto actualizado correctamente.',
            'gasto' => $this->expenses->update($gasto, $validated),
        ]);
    }

    /**
     * Elimina un gasto existente.
     */
    public function destroyGasto(Gasto $gasto): JsonResponse
    {
        return response()->json([
            'message' => 'Gasto eliminado correctamente.',
            'id' => $this->expenses->delete($gasto),
        ]);
    }

    /**
     * Crea un nuevo ingreso mensual.
     */
    public function storeIngreso(Request $request): JsonResponse
    {
        $validated = $this->validateIngreso($request);

        return response()->json([
            'message' => 'Ingreso anadido correctamente.',
            'ingreso' => $this->incomes->create($validated),
        ]);
    }

    /**
     * Actualiza un ingreso existente.
     */
    public function updateIngreso(Request $request, Ingreso $ingreso): JsonResponse
    {
        $validated = $this->validateIngreso($request);

        return response()->json([
            'message' => 'Ingreso actualizado correctamente.',
            'ingreso' => $this->incomes->update($ingreso, $validated),
        ]);
    }

    /**
     * Elimina un ingreso existente.
     */
    public function destroyIngreso(Ingreso $ingreso): JsonResponse
    {
        return response()->json([
            'message' => 'Ingreso eliminado correctamente.',
            'id' => $this->incomes->delete($ingreso),
        ]);
    }

    /**
     * Exporta todos los gastos con su categoria a un fichero compatible con Excel.
     */
    public function exportGastosExcel(): Response
    {
        $filename = $this->exportFilename();

        return response()
            ->view('exports.gastos-excel', $this->workbookViewData())
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * Prepara el Excel en el almacenamiento temporal nativo y abre el panel de compartir del movil.
     */
    public function shareGastosExcel(): JsonResponse
    {
        if (! $this->supportsNativeWorkbookShare()) {
            return response()->json([
                'message' => 'La exportacion nativa no esta disponible en este dispositivo.',
            ], 422);
        }

        $filename = $this->exportFilename();
        $relativePath = 'appgastos-exports/'.$filename;
        $disk = Storage::disk('temp');
        $written = $disk->put($relativePath, $this->renderWorkbook());

        if (! $written) {
            return response()->json([
                'message' => 'No se pudo preparar el fichero Excel para compartir en el movil.',
            ], 500);
        }

        $share = 'Native\Mobile\Facades\Share';
        $share::file(
            'Exportacion de AppGastos',
            'Comparte o guarda una copia de tus datos.',
            $disk->path($relativePath),
        );

        return response()->json([
            'message' => 'Se ha abierto el panel para exportar el Excel.',
            'filename' => $filename,
        ]);
    }

    /**
     * Valida los datos necesarios para registrar o actualizar un gasto.
     *
     * @return array<string, mixed>
     */
    private function validateGasto(Request $request): array
    {
        return $request->validate(
            [
                'titulo' => ['required', 'string', 'max:255'],
                'importe' => ['required', 'numeric', 'min:0.01'],
                'fecha' => ['required', 'date'],
                'categoria_id' => ['required', 'exists:categorias,id'],
                'observaciones' => ['nullable', 'string'],
            ],
            [
                'titulo.required' => 'El título del gasto es obligatorio.',
                'titulo.max' => 'El título del gasto no puede superar los 255 caracteres.',
                'importe.required' => 'El importe del gasto es obligatorio.',
                'importe.numeric' => 'El importe del gasto debe ser un número válido.',
                'importe.min' => 'El importe del gasto debe ser mayor que 0.',
                'fecha.required' => 'La fecha del gasto es obligatoria.',
                'fecha.date' => 'La fecha del gasto no es válida.',
                'categoria_id.required' => 'Debes seleccionar una categoría.',
                'categoria_id.exists' => 'La categoría seleccionada no es válida.',
                'observaciones.string' => 'Las observaciones del gasto deben ser texto.',
            ]
        );
    }

    /**
     * Valida los datos necesarios para registrar un ingreso.
     *
     * @return array<string, mixed>
     */
    private function validateIngreso(Request $request): array
    {
        return $request->validate(
            [
                'titulo' => ['required', 'string', 'max:255'],
                'importe' => ['required', 'numeric', 'min:0.01'],
                'fecha' => ['required', 'date'],
                'observaciones' => ['nullable', 'string'],
            ],
            [
                'titulo.required' => 'El título del ingreso es obligatorio.',
                'titulo.max' => 'El título del ingreso no puede superar los 255 caracteres.',
                'importe.required' => 'El importe del ingreso es obligatorio.',
                'importe.numeric' => 'El importe del ingreso debe ser un número válido.',
                'importe.min' => 'El importe del ingreso debe ser mayor que 0.',
                'fecha.required' => 'La fecha del ingreso es obligatoria.',
                'fecha.date' => 'La fecha del ingreso no es válida.',
                'observaciones.string' => 'Las observaciones del ingreso deben ser texto.',
            ]
        );
    }

    /**
     * Construye el contenido de la exportacion Excel.
     *
     * @return array<string, mixed>
     */
    private function workbookViewData(): array
    {
        return [
            'rows' => $this->monthlyReports->getExportRows(),
            'incomeRows' => $this->monthlyReports->getIncomeExportRows(),
            'fixedEntryRows' => $this->monthlyReports->getFixedEntryExportRows(),
            'categoryRows' => $this->monthlyReports->getCategoryExportRows(),
            'accountRows' => $this->monthlyReports->getAccountExportRows(),
        ];
    }

    /**
     * Renderiza el libro Excel para enviarlo o compartirlo.
     */
    private function renderWorkbook(): string
    {
        return view('exports.gastos-excel', $this->workbookViewData())->render();
    }

    /**
     * Genera el nombre del fichero de exportacion.
     */
    private function exportFilename(): string
    {
        return 'appgastos-export-'.now()->format('Y-m-d').'.xls';
    }

    /**
     * Indica si la app puede usar el flujo nativo de compartir ficheros.
     */
    private function supportsNativeWorkbookShare(): bool
    {
        return (bool) config('nativephp-internal.running')
            && filled(config('nativephp-internal.tempdir'))
            && class_exists('Native\Mobile\Facades\Share');
    }
}
