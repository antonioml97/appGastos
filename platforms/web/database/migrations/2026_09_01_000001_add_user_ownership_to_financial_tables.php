<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [
        'categorias',
        'gastos',
        'ingresos',
        'movimientos_fijos',
        'movimientos_fijos_excepciones',
        'cuentas',
        'cuenta_saldos_mensuales',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            });
        }

        Schema::table('categorias', function (Blueprint $table): void {
            $table->dropUnique('categorias_nombre_unique');
            $table->unique(['user_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'nombre']);
            $table->unique('nombre');
        });

        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
