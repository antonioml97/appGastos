<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de excepciones mensuales para movimientos fijos.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion.
     */
    public function up(): void
    {
        Schema::create('movimientos_fijos_excepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_fijo_id')
                ->constrained('movimientos_fijos')
                ->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->timestamps();

            $table->unique(['movimiento_fijo_id', 'tipo', 'fecha'], 'movimientos_fijos_excepciones_unique');
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_fijos_excepciones');
    }
};
