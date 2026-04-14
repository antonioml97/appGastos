<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla de gastos con su relacion hacia categorias.
 *
 * @autor Antonio Martin Leon
 */
return new class extends Migration
{
    /**
     * Ejecuta la migracion creando la tabla de gastos.
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->decimal('importe', 12, 2);
            $table->date('fecha');
            $table->foreignId('categoria_id')
                ->constrained('categorias')
                ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('fecha');
            $table->index(['categoria_id', 'fecha']);
        });
    }

    /**
     * Revierte la migracion eliminando la tabla de gastos.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
