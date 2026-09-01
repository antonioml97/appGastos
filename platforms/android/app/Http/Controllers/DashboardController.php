<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(MonthlyReportRepositoryInterface $monthlyReports): JsonResponse
    {
        $monthlyPayload = $monthlyReports->getPayload(null);

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
