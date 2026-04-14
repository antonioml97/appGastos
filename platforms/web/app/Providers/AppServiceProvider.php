<?php

namespace App\Providers;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\MonthlyReportRepositoryInterface;
use App\Contracts\Repositories\YearlyReportRepositoryInterface;
use App\Domain\Accounts\Subscribers\AccountBalanceSubscriber;
use App\Repositories\EloquentCategoryRepository;
use App\Repositories\EloquentMonthlyReportRepository;
use App\Repositories\EloquentYearlyReportRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

/**
 * Registra dependencias de aplicacion y prepara recursos base al arrancar.
 *
 * @autor Antonio Martin Leon
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los bindings principales del contenedor de servicios.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(MonthlyReportRepositoryInterface::class, EloquentMonthlyReportRepository::class);
        $this->app->bind(YearlyReportRepositoryInterface::class, EloquentYearlyReportRepository::class);
    }

    /**
     * Ejecuta tareas de inicializacion al arrancar la aplicacion.
     */
    public function boot(): void
    {
        $this->ensureSqliteDatabaseExists();
        Event::subscribe(AccountBalanceSubscriber::class);
    }

    /**
     * Garantiza que exista el fichero SQLite cuando esa conexion esta activa.
     */
    private function ensureSqliteDatabaseExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === '' || $database === ':memory:') {
            return;
        }

        $path = $this->isAbsolutePath($database) ? $database : base_path($database);
        $directory = dirname($path);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (! File::exists($path)) {
            File::put($path, '');
        }
    }

    /**
     * Determina si una ruta del sistema de archivos es absoluta.
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
