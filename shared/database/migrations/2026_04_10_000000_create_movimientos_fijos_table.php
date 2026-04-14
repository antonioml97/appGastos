<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de movimientos fijos mensuales.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion creando la tabla de movimientos fijos.
     */
    public function up(): void
    {
        Schema::create('movimientos_fijos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('titulo');
            $table->decimal('importe', 12, 2);
            $table->unsignedTinyInteger('dia');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tipo', 'activo']);
        });
    }

    /**
     * Revierte la migracion eliminando la tabla de movimientos fijos.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_fijos');
    }
};
