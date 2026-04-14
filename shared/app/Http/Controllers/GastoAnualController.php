<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\YearlyReportRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestiona la visualizacion del informe anual de gastos e ingresos.
 *
 * @autor Antonio Martin Leon
 */
class GastoAnualController extends Controller
{
    /**
     * Inyecta el repositorio encargado del resumen anual.
     */
    public function __construct(
        private readonly YearlyReportRepositoryInterface $yearlyReports,
    ) {}

    /**
     * Muestra la vista anual o devuelve su payload en JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->yearlyReports->getPayload($request->integer('anio'));

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }
}
