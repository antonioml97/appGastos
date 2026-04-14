<?php

namespace App\Contracts\Repositories;

use App\Models\Gasto;
use App\Models\Ingreso;

/**
 * Define las operaciones necesarias para construir y mantener el informe mensual.
 *
 * @autor Antonio Martin Leon
 */
interface MonthlyReportRepositoryInterface
{
    /**
     * Obtiene el payload completo del mes solicitado.
     *
     * @return array<string, mixed>
     */
    public function getPayload(?string $month): array;

    /**
     * Obtiene las filas necesarias para exportar los gastos con su categoria.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExportRows(): array;

    /**
     * Obtiene las filas necesarias para exportar los ingresos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIncomeExportRows(): array;

    /**
     * Obtiene las filas necesarias para exportar los movimientos fijos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFixedEntryExportRows(): array;

    /**
     * Obtiene las filas necesarias para exportar las categorias.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryExportRows(): array;

    /**
     * Obtiene las filas necesarias para exportar las cuentas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAccountExportRows(): array;

    /**
     * Importa un fichero Excel/XML previamente exportado por la aplicacion.
     *
     * @return array<string, int>
     */
    public function importWorkbook(string $contents): array;

    /**
     * Registra un nuevo gasto mensual.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createExpense(array $data): array;

    /**
     * Actualiza un gasto existente.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateExpense(Gasto $gasto, array $data): array;

    /**
     * Elimina un gasto y devuelve su identificador.
     */
    public function deleteExpense(Gasto $gasto): int;

    /**
     * Registra un nuevo ingreso mensual.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createIncome(array $data): array;

    /**
     * Actualiza un ingreso existente.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateIncome(Ingreso $ingreso, array $data): array;

    /**
     * Elimina un ingreso y devuelve su identificador.
     */
    public function deleteIncome(Ingreso $ingreso): int;
}
