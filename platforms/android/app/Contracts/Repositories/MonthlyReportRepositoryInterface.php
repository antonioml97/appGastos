<?php

namespace App\Contracts\Repositories;

use App\Models\Gasto;
use App\Models\Ingreso;

interface MonthlyReportRepositoryInterface
{
    public function getPayload(?string $month): array;

    public function createExpense(array $data): array;

    public function updateExpense(Gasto $gasto, array $data): array;

    public function deleteExpense(Gasto $gasto): int;

    public function createIncome(array $data): array;

    public function deleteIncome(Ingreso $ingreso): int;
}
