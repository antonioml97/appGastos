<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\YearlyReportRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoAnualController extends Controller
{
    public function __construct(
        private readonly YearlyReportRepositoryInterface $yearlyReports,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $payload = $this->yearlyReports->getPayload($request->integer('anio'));

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return view('welcome', ['appData' => $payload]);
    }
}
