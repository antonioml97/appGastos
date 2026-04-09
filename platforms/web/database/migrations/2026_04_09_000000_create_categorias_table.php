<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de categorias para clasificar los gastos.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion creando la tabla de categorias.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('color', 20);
            $table->string('icono')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migracion eliminando la tabla de categorias.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
