<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\GastoAnualController;
use App\Http\Controllers\GastoMensualController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'appData' => [
            'page' => 'home',
            'title' => 'Panel principal de AppGastos',
        ],
    ]);
});

Route::get('/gastos-mensuales', [GastoMensualController::class, 'index'])->name('gastos.mensuales');
Route::post('/gastos-mensuales/gastos', [GastoMensualController::class, 'storeGasto'])->name('gastos.mensuales.gastos.store');
Route::put('/gastos-mensuales/gastos/{gasto}', [GastoMensualController::class, 'updateGasto'])->name('gastos.mensuales.gastos.update');
Route::delete('/gastos-mensuales/gastos/{gasto}', [GastoMensualController::class, 'destroyGasto'])->name('gastos.mensuales.gastos.destroy');
Route::post('/gastos-mensuales/ingresos', [GastoMensualController::class, 'storeIngreso'])->name('gastos.mensuales.ingresos.store');
Route::delete('/gastos-mensuales/ingresos/{ingreso}', [GastoMensualController::class, 'destroyIngreso'])->name('gastos.mensuales.ingresos.destroy');

Route::get('/gastos-anuales', [GastoAnualController::class, 'index'])->name('gastos.anuales');

Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
