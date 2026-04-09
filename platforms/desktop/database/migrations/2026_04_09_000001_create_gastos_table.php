<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
