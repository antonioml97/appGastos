<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de saldos mensuales persistidos para cuentas.
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
        Schema::create('cuenta_saldos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')
                ->constrained('cuentas')
                ->cascadeOnDelete();
            $table->string('mes', 7);
            $table->decimal('saldo_inicial_mes', 12, 2)->default(0);
            $table->decimal('total_ingresos', 12, 2)->default(0);
            $table->decimal('total_gastos', 12, 2)->default(0);
            $table->decimal('saldo_final', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['cuenta_id', 'mes']);
        });
    }

    /**
     * Revierte la migracion.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuenta_saldos_mensuales');
    }
};
