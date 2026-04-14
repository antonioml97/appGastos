<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\MovimientoFijo;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Importa ficheros SpreadsheetML generados por la exportacion de la app.
 *
 * @autor Antonio Martin Leon
 */
class ExcelWorkbookImporter
{
    /**
     * Importa el contenido XML del libro Excel y devuelve un resumen.
     *
     * @return array<string, int>
     */
    public static function import(string $contents): array
    {
        $worksheets = self::parseWorkbook($contents);
        $summary = [
            'categories_imported' => 0,
            'expenses_imported' => 0,
            'expenses_skipped' => 0,
            'incomes_imported' => 0,
            'incomes_skipped' => 0,
            'fixed_entries_imported' => 0,
            'fixed_entries_skipped' => 0,
            'accounts_imported' => 0,
            'accounts_skipped' => 0,
        ];

        DB::transaction(function () use ($worksheets, &$summary): void {
            $categoriesByName = [];

            foreach (self::normalizeCategoryRows($worksheets['Categorias'] ?? []) as $row) {
                $categoria = Categoria::query()->updateOrCreate(
                    ['nombre' => $row['nombre']],
                    [
                        'color' => $row['color'],
                        'icono' => $row['icono'],
                    ]
                );

                $categoriesByName[$categoria->nombre] = $categoria;
                $summary['categories_imported']++;
            }

            foreach (self::normalizeExpenseRows($worksheets['Gastos'] ?? []) as $row) {
                $categoria = $categoriesByName[$row['categoria']]
                    ?? Categoria::query()->firstWhere('nombre', $row['categoria']);

                if (! $categoria) {
                    $categoria = Categoria::query()->create([
                        'nombre' => $row['categoria'],
                        'color' => '#64748B',
                        'icono' => 'otros',
                    ]);

                    $categoriesByName[$categoria->nombre] = $categoria;
                    $summary['categories_imported']++;
                }

                if (self::findExistingExpense($row, $categoria->id)) {
                    $summary['expenses_skipped']++;
                    continue;
                }

                Gasto::query()->create([
                    'titulo' => $row['titulo'],
                    'importe' => $row['importe'],
                    'fecha' => $row['fecha'],
                    'categoria_id' => $categoria->id,
                    'observaciones' => $row['observaciones'],
                ]);

                $summary['expenses_imported']++;
            }

            foreach (self::normalizeIncomeRows($worksheets['Ingresos'] ?? []) as $row) {
                if (self::findExistingIncome($row)) {
                    $summary['incomes_skipped']++;
                    continue;
                }

                Ingreso::query()->create([
                    'titulo' => $row['titulo'],
                    'importe' => $row['importe'],
                    'fecha' => $row['fecha'],
                    'observaciones' => $row['observaciones'],
                ]);

                $summary['incomes_imported']++;
            }

            foreach (self::normalizeFixedEntryRows($worksheets['Movimientos fijos'] ?? []) as $row) {
                $categoryId = null;

                if ($row['tipo'] === 'gasto') {
                    $categoria = $categoriesByName[$row['categoria']]
                        ?? Categoria::query()->firstWhere('nombre', $row['categoria']);

                    if (! $categoria) {
                        $categoria = Categoria::query()->create([
                            'nombre' => $row['categoria'],
                            'color' => '#64748B',
                            'icono' => 'otros',
                        ]);

                        $categoriesByName[$categoria->nombre] = $categoria;
                        $summary['categories_imported']++;
                    }

                    $categoryId = $categoria->id;
                }

                $existingFixedEntry = self::findExistingFixedEntry($row, $categoryId);

                if ($existingFixedEntry !== null) {
                    $existingFixedEntry->update([
                        'importe' => $row['importe'],
                        'observaciones' => $row['observaciones'],
                        'activo' => $row['activo'],
                    ]);

                    $summary['fixed_entries_skipped']++;
                    continue;
                }

                MovimientoFijo::query()->create([
                    'tipo' => $row['tipo'],
                    'titulo' => $row['titulo'],
                    'importe' => $row['importe'],
                    'dia' => $row['dia'],
                    'categoria_id' => $categoryId,
                    'observaciones' => $row['observaciones'],
                    'activo' => $row['activo'],
                ]);

                $summary['fixed_entries_imported']++;
            }

            foreach (self::normalizeAccountRows($worksheets['Cuentas'] ?? []) as $row) {
                $existingAccount = self::findExistingAccount($row);

                if ($existingAccount !== null) {
                    $existingAccount->update([
                        'saldo_inicial' => $row['saldo_inicial'],
                        'saldo_actual' => $row['saldo_actual'],
                        'ahorro_mensual' => $row['ahorro_mensual'],
                        'ultimo_mes_ahorro_aplicado' => $row['ultimo_mes_ahorro_aplicado'],
                    ]);

                    $summary['accounts_skipped']++;
                    continue;
                }

                Cuenta::query()->create([
                    'nombre' => $row['nombre'],
                    'tipo' => $row['tipo'],
                    'saldo_inicial' => $row['saldo_inicial'],
                    'saldo_actual' => $row['saldo_actual'],
                    'ahorro_mensual' => $row['ahorro_mensual'],
                    'ultimo_mes_ahorro_aplicado' => $row['ultimo_mes_ahorro_aplicado'],
                ]);

                $summary['accounts_imported']++;
            }
        });

        return $summary;
    }

    /**
     * Parsea el libro SpreadsheetML y lo convierte en hojas con celdas.
     *
     * @return array<string, array<int, array<int, string>>>
     */
    private static function parseWorkbook(string $contents): array
    {
        libxml_use_internal_errors(true);

        $document = new DOMDocument();

        if (! $document->loadXML($contents)) {
            libxml_clear_errors();

            throw ValidationException::withMessages([
                'archivo' => 'El fichero no tiene un formato Excel/XML valido.',
            ]);
        }

        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');

        $worksheets = [];

        foreach ($xpath->query('//ss:Worksheet') as $worksheetNode) {
            if (! $worksheetNode instanceof DOMElement) {
                continue;
            }

            $name = $worksheetNode->getAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Name');

            if ($name === '') {
                $name = $worksheetNode->getAttribute('ss:Name');
            }

            if ($name === '') {
                continue;
            }

            $rows = [];

            foreach ($xpath->query('./ss:Table/ss:Row', $worksheetNode) as $rowNode) {
                $cells = [];

                foreach ($xpath->query('./ss:Cell', $rowNode) as $cellNode) {
                    $index = 0;

                    if ($cellNode instanceof DOMElement) {
                        $index = (int) $cellNode->getAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Index');

                        if ($index === 0) {
                            $index = (int) $cellNode->getAttribute('ss:Index');
                        }
                    }

                    if ($index > 1) {
                        while (count($cells) < $index - 1) {
                            $cells[] = '';
                        }
                    }

                    $value = trim((string) $xpath->evaluate('string(./ss:Data)', $cellNode));
                    $cells[] = $value;
                }

                $rows[] = $cells;
            }

            $worksheets[$name] = $rows;
        }

        if (! isset($worksheets['Gastos'], $worksheets['Ingresos'], $worksheets['Categorias'])) {
            throw ValidationException::withMessages([
                'archivo' => 'El fichero no coincide con el formato de exportacion de AppGastos.',
            ]);
        }

        return $worksheets;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{nombre: string, color: string, icono: string}>
     */
    private static function normalizeCategoryRows(array $rows): array
    {
        return collect(array_slice($rows, 1))
            ->filter(fn (array $row) => ! self::isPlaceholderRow($row))
            ->map(function (array $row): array {
                $nombre = trim((string) ($row[0] ?? ''));
                $color = trim((string) ($row[1] ?? '#64748B'));
                $icono = trim((string) ($row[2] ?? ''));

                if ($nombre === '') {
                    throw ValidationException::withMessages([
                        'archivo' => 'Hay categorias sin nombre dentro del fichero importado.',
                    ]);
                }

                return [
                    'nombre' => $nombre,
                    'color' => $color !== '' ? $color : '#64748B',
                    'icono' => $icono,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{fecha: string, titulo: string, categoria: string, importe: float, observaciones: ?string}>
     */
    private static function normalizeExpenseRows(array $rows): array
    {
        return collect(array_slice($rows, 1))
            ->filter(fn (array $row) => ! self::isPlaceholderRow($row))
            ->map(function (array $row): array {
                $categoria = trim((string) ($row[2] ?? ''));

                if ($categoria === '') {
                    throw ValidationException::withMessages([
                        'archivo' => 'Hay gastos sin categoria dentro del fichero importado.',
                    ]);
                }

                return [
                    'fecha' => self::normalizeDate((string) ($row[0] ?? '')),
                    'titulo' => self::normalizeRequiredText((string) ($row[1] ?? ''), 'gasto'),
                    'categoria' => $categoria,
                    'importe' => self::normalizeAmount((string) ($row[3] ?? '')),
                    'observaciones' => self::normalizeNullableText((string) ($row[4] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{fecha: string, titulo: string, importe: float, observaciones: ?string}>
     */
    private static function normalizeIncomeRows(array $rows): array
    {
        return collect(array_slice($rows, 1))
            ->filter(fn (array $row) => ! self::isPlaceholderRow($row))
            ->map(fn (array $row): array => [
                'fecha' => self::normalizeDate((string) ($row[0] ?? '')),
                'titulo' => self::normalizeRequiredText((string) ($row[1] ?? ''), 'ingreso'),
                'importe' => self::normalizeAmount((string) ($row[2] ?? '')),
                'observaciones' => self::normalizeNullableText((string) ($row[3] ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{tipo: string, titulo: string, categoria: string, importe: float, dia: int, activo: bool, observaciones: ?string}>
     */
    private static function normalizeFixedEntryRows(array $rows): array
    {
        return collect(array_slice($rows, 1))
            ->filter(fn (array $row) => ! self::isPlaceholderRow($row))
            ->map(function (array $row): array {
                $tipo = self::normalizeFixedEntryType((string) ($row[0] ?? ''));
                $categoria = trim((string) ($row[2] ?? ''));

                if ($tipo === 'gasto' && $categoria === '') {
                    throw ValidationException::withMessages([
                        'archivo' => 'Hay gastos fijos sin categoria dentro del fichero importado.',
                    ]);
                }

                return [
                    'tipo' => $tipo,
                    'titulo' => self::normalizeRequiredText((string) ($row[1] ?? ''), 'movimiento fijo'),
                    'categoria' => $categoria,
                    'importe' => self::normalizeAmount((string) ($row[3] ?? '')),
                    'dia' => self::normalizeDay((string) ($row[4] ?? '')),
                    'activo' => self::normalizeBoolean((string) ($row[5] ?? '')),
                    'observaciones' => self::normalizeNullableText((string) ($row[6] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{nombre: string, tipo: string, saldo_inicial: float, saldo_actual: float, ahorro_mensual: ?float, ultimo_mes_ahorro_aplicado: ?string}>
     */
    private static function normalizeAccountRows(array $rows): array
    {
        return collect(array_slice($rows, 1))
            ->filter(fn (array $row) => ! self::isPlaceholderRow($row))
            ->map(function (array $row): array {
                return [
                    'nombre' => self::normalizeRequiredText((string) ($row[0] ?? ''), 'cuenta'),
                    'tipo' => self::normalizeAccountType((string) ($row[1] ?? '')),
                    'saldo_inicial' => self::normalizeAmount((string) ($row[2] ?? '')),
                    'saldo_actual' => self::normalizeAmount((string) ($row[3] ?? '')),
                    'ahorro_mensual' => self::normalizeOptionalAmount((string) ($row[4] ?? '')),
                    'ultimo_mes_ahorro_aplicado' => self::normalizeOptionalDate((string) ($row[5] ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Determina si una fila es el mensaje vacio del export.
     *
     * @param  array<int, string>  $row
     */
    private static function isPlaceholderRow(array $row): bool
    {
        $firstCell = trim((string) ($row[0] ?? ''));

        return $firstCell === ''
            || str_starts_with($firstCell, 'No hay ');
    }

    private static function normalizeDate(string $value): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'archivo' => 'Hay filas sin fecha dentro del fichero importado.',
            ]);
        }

        $formats = ['d/m/Y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->startOfDay()->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        throw ValidationException::withMessages([
            'archivo' => 'El fichero contiene fechas con un formato no valido para la importacion.',
        ]);
    }

    private static function normalizeAmount(string $value): float
    {
        $normalized = preg_replace('/\s+/', '', trim($value)) ?? '';

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'archivo' => 'Hay filas sin importe dentro del fichero importado.',
            ]);
        }

        $lastDot = strrpos($normalized, '.');
        $lastComma = strrpos($normalized, ',');

        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif ($lastComma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            throw ValidationException::withMessages([
                'archivo' => 'El fichero contiene importes con un formato no valido.',
            ]);
        }

        return round((float) $normalized, 2);
    }

    private static function normalizeDay(string $value): int
    {
        $normalized = trim($value);

        if ($normalized === '' || ! ctype_digit($normalized)) {
            throw ValidationException::withMessages([
                'archivo' => 'El fichero contiene movimientos fijos con un dia no valido.',
            ]);
        }

        $day = (int) $normalized;

        if ($day < 1 || $day > 31) {
            throw ValidationException::withMessages([
                'archivo' => 'El fichero contiene movimientos fijos con un dia fuera del rango 1-31.',
            ]);
        }

        return $day;
    }

    private static function normalizeRequiredText(string $value, string $context): string
    {
        $normalized = trim($value);

        if ($normalized !== '') {
            return $normalized;
        }

        throw ValidationException::withMessages([
            'archivo' => sprintf('Hay %s sin titulo dentro del fichero importado.', $context),
        ]);
    }

    private static function normalizeNullableText(string $value): ?string
    {
        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private static function normalizeFixedEntryType(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        if (in_array($normalized, ['gasto', 'ingreso'], true)) {
            return $normalized;
        }

        throw ValidationException::withMessages([
            'archivo' => 'El fichero contiene movimientos fijos con un tipo no valido.',
        ]);
    }

    private static function normalizeBoolean(string $value): bool
    {
        $normalized = mb_strtolower(trim($value));

        if (in_array($normalized, ['1', 'si', 'sí', 'true', 'activo'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'no', 'false', 'inactivo'], true)) {
            return false;
        }

        throw ValidationException::withMessages([
            'archivo' => 'El fichero contiene movimientos fijos con un valor de activo no valido.',
        ]);
    }

    private static function normalizeOptionalAmount(string $value): ?float
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return self::normalizeAmount($normalized);
    }

    private static function normalizeOptionalDate(string $value): ?string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return self::normalizeDate($normalized);
    }

    private static function normalizeAccountType(string $value): string
    {
        $normalized = mb_strtolower(trim($value));

        if (in_array($normalized, ['normal', 'ahorro'], true)) {
            return $normalized;
        }

        throw ValidationException::withMessages([
            'archivo' => 'El fichero contiene cuentas con un tipo no valido.',
        ]);
    }

    /**
     * @param  array{fecha: string, titulo: string, categoria: string, importe: float, observaciones: ?string}  $row
     */
    private static function findExistingExpense(array $row, int $categoryId): ?Gasto
    {
        return Gasto::query()
            ->where('titulo', $row['titulo'])
            ->where('importe', $row['importe'])
            ->whereDate('fecha', $row['fecha'])
            ->where('categoria_id', $categoryId)
            ->where(function ($query) use ($row): void {
                if ($row['observaciones'] === null) {
                    $query->whereNull('observaciones')
                        ->orWhere('observaciones', '');

                    return;
                }

                $query->where('observaciones', $row['observaciones']);
            })
            ->first();
    }

    /**
     * @param  array{fecha: string, titulo: string, importe: float, observaciones: ?string}  $row
     */
    private static function findExistingIncome(array $row): ?Ingreso
    {
        return Ingreso::query()
            ->where('titulo', $row['titulo'])
            ->where('importe', $row['importe'])
            ->whereDate('fecha', $row['fecha'])
            ->where(function ($query) use ($row): void {
                if ($row['observaciones'] === null) {
                    $query->whereNull('observaciones')
                        ->orWhere('observaciones', '');

                    return;
                }

                $query->where('observaciones', $row['observaciones']);
            })
            ->first();
    }

    /**
     * @param  array{tipo: string, titulo: string, categoria: string, importe: float, dia: int, activo: bool, observaciones: ?string}  $row
     */
    private static function findExistingFixedEntry(array $row, ?int $categoryId): ?MovimientoFijo
    {
        return MovimientoFijo::query()
            ->where('tipo', $row['tipo'])
            ->where('titulo', $row['titulo'])
            ->where('dia', $row['dia'])
            ->where(function ($query) use ($categoryId): void {
                if ($categoryId === null) {
                    $query->whereNull('categoria_id');

                    return;
                }

                $query->where('categoria_id', $categoryId);
            })
            ->first();
    }

    /**
     * @param  array{nombre: string, tipo: string, saldo_inicial: float, saldo_actual: float, ahorro_mensual: ?float, ultimo_mes_ahorro_aplicado: ?string}  $row
     */
    private static function findExistingAccount(array $row): ?Cuenta
    {
        return Cuenta::query()
            ->where('nombre', $row['nombre'])
            ->where('tipo', $row['tipo'])
            ->first();
    }
}
