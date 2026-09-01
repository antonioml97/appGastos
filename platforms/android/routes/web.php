<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/categorias', 'welcome')->name('web.categorias');
Route::view('/gastos-mensuales', 'welcome')->name('web.gastos.mensuales');
Route::view('/gastos-anuales', 'welcome')->name('web.gastos.anuales');
Route::view('/configuracion', 'welcome')->name('web.configuracion');
