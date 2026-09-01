<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GastoAnualController;
use App\Http\Controllers\GastoMensualController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/user', [AuthController::class, 'user'])->name('api.auth.user');
    Route::patch('/auth/password', [AuthController::class, 'updatePassword'])->name('api.auth.password.update');
    Route::delete('/auth/account', [AuthController::class, 'destroy'])->name('api.auth.account.destroy');
    Route::delete('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/dashboard', DashboardController::class)->name('api.dashboard');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion');
    Route::post('/configuracion/movimientos-fijos', [ConfiguracionController::class, 'storeMovimientoFijo'])->name('configuracion.movimientos-fijos.store');
    Route::put('/configuracion/movimientos-fijos/{movimientoFijo}', [ConfiguracionController::class, 'updateMovimientoFijo'])->name('configuracion.movimientos-fijos.update');
    Route::delete('/configuracion/movimientos-fijos/{movimientoFijo}', [ConfiguracionController::class, 'destroyMovimientoFijo'])->name('configuracion.movimientos-fijos.destroy');
    Route::post('/configuracion/importar-excel', [ConfiguracionController::class, 'importExcel'])->name('configuracion.importar-excel');
    Route::post('/configuracion/cuentas', [ConfiguracionController::class, 'storeCuenta'])->name('configuracion.cuentas.store');
    Route::put('/configuracion/cuentas/{cuenta}', [ConfiguracionController::class, 'updateCuenta'])->name('configuracion.cuentas.update');
    Route::delete('/configuracion/cuentas/{cuenta}', [ConfiguracionController::class, 'destroyCuenta'])->name('configuracion.cuentas.destroy');
    Route::post('/configuracion/cuentas/{cuenta}/retirar', [ConfiguracionController::class, 'retirarCuenta'])->name('configuracion.cuentas.retirar');
    Route::delete('/configuracion/datos', [ConfiguracionController::class, 'destroyData'])->name('configuracion.datos.destroy');

    Route::get('/gastos-mensuales', [GastoMensualController::class, 'index'])->name('gastos.mensuales');
    Route::post('/gastos-mensuales/gastos', [GastoMensualController::class, 'storeGasto'])->name('gastos.mensuales.gastos.store');
    Route::put('/gastos-mensuales/gastos/{gasto}', [GastoMensualController::class, 'updateGasto'])->name('gastos.mensuales.gastos.update');
    Route::delete('/gastos-mensuales/gastos/{gasto}', [GastoMensualController::class, 'destroyGasto'])->name('gastos.mensuales.gastos.destroy');
    Route::post('/gastos-mensuales/ingresos', [GastoMensualController::class, 'storeIngreso'])->name('gastos.mensuales.ingresos.store');
    Route::put('/gastos-mensuales/ingresos/{ingreso}', [GastoMensualController::class, 'updateIngreso'])->name('gastos.mensuales.ingresos.update');
    Route::delete('/gastos-mensuales/ingresos/{ingreso}', [GastoMensualController::class, 'destroyIngreso'])->name('gastos.mensuales.ingresos.destroy');
    Route::get('/configuracion/exportar-gastos', [GastoMensualController::class, 'exportGastosExcel'])->name('configuracion.export.gastos');
    Route::post('/configuracion/exportar-gastos/compartir', [GastoMensualController::class, 'shareGastosExcel'])->name('configuracion.export.gastos.share');

    Route::get('/gastos-anuales', [GastoAnualController::class, 'index'])->name('gastos.anuales');
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
});
