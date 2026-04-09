<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Models\Gasto;
use App\Models\Ingreso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoMensualController extends Controller
{
    public function __construct(
        private readonly MonthlyReportRepositoryInterface $monthlyReports,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->monthlyReports->getPayload($request->string('mes')->toString());

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }

    public function storeGasto(Request $request): JsonResponse
    {
        $validated = $this->validateGasto($request);

        return response()->json([
            'message' => 'Gasto anadido correctamente.',
            'gasto' => $this->monthlyReports->createExpense($validated),
        ]);
    }

    public function updateGasto(Request $request, Gasto $gasto): JsonResponse
    {
        $validated = $this->validateGasto($request);

        return response()->json([
            'message' => 'Gasto actualizado correctamente.',
            'gasto' => $this->monthlyReports->updateExpense($gasto, $validated),
        ]);
    }

    public function destroyGasto(Gasto $gasto): JsonResponse
    {
        return response()->json([
            'message' => 'Gasto eliminado correctamente.',
            'id' => $this->monthlyReports->deleteExpense($gasto),
        ]);
    }

    public function storeIngreso(Request $request): JsonResponse
    {
        $validated = $this->validateIngreso($request);

        return response()->json([
            'message' => 'Ingreso anadido correctamente.',
            'ingreso' => $this->monthlyReports->createIncome($validated),
        ]);
    }

    public function destroyIngreso(Ingreso $ingreso): JsonResponse
    {
        return response()->json([
            'message' => 'Ingreso eliminado correctamente.',
            'id' => $this->monthlyReports->deleteIncome($ingreso),
        ]);
    }

    private function validateGasto(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'importe' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'observaciones' => ['nullable', 'string'],
        ]);
    }

    private function validateIngreso(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'importe' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);
    }
}
