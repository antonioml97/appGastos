<?php

namespace App\Contracts\Repositories;

interface YearlyReportRepositoryInterface
{
    public function getPayload(?int $year): array;
}
