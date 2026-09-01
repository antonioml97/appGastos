<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MonthlyReportRepositoryInterface $monthlyReports): JsonResponse
    {
        $month = $request->query('mes');
        $monthlyPayload = $monthlyReports->getPayload(is_string($month) ? $month : null);

        return response()->json([
            'page' => 'home',
            'title' => '',
            'home' => [
                'selectedMonthLabel' => $monthlyPayload['selectedMonthLabel'],
                'selectedMonthValue' => $monthlyPayload['selectedMonthValue'],
                'summary' => $monthlyPayload['summary'],
                'accountsSummary' => $monthlyPayload['accountsSummary'],
                'topCategories' => collect($monthlyPayload['desglose'] ?? [])->take(4)->values()->all(),
            ],
        ]);
    }
}
