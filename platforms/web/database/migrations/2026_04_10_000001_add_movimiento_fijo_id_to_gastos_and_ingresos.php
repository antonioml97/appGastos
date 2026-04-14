<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anade la relacion con movimientos fijos en gastos e ingresos.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion anadiendo las claves foraneas.
     */
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('movimiento_fijo_id')
                ->nullable()
                ->after('categoria_id')
                ->constrained('movimientos_fijos')
                ->nullOnDelete();
        });

        Schema::table('ingresos', function (Blueprint $table) {
            $table->foreignId('movimiento_fijo_id')
                ->nullable()
                ->after('fecha')
                ->constrained('movimientos_fijos')
                ->nullOnDelete();
        });
    }

    /**
     * Revierte la migracion eliminando las claves foraneas.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movimiento_fijo_id');
        });

        Schema::table('ingresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movimiento_fijo_id');
        });
    }
};
