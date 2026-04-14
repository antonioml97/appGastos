<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de ingresos utilizada por el resumen financiero.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion creando la tabla de ingresos.
     */
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->decimal('importe', 12, 2);
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    /**
     * Revierte la migracion eliminando la tabla de ingresos.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
