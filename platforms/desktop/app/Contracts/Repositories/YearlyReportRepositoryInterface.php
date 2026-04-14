<?php

namespace App\Contracts\Repositories;

/**
 * Define las operaciones necesarias para generar el informe anual.
 *
 * @autor Antonio Martin Leon
 */
interface YearlyReportRepositoryInterface
{
    /**
     * Construye el payload del resumen anual solicitado.
     *
     * @return array<string, mixed>
     */
    public function getPayload(?int $year): array;
}
