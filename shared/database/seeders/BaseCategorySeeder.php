<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Support\BaseCategoryConfig;
use Illuminate\Database\Seeder;

/**
 * Inserta o actualiza las categorias base definidas en el fichero compartido.
 *
 * @autor Antonio Martin Leon
 */
class BaseCategorySeeder extends Seeder
{
    /**
     * Ejecuta el seeder de categorias base.
     */
    public function run(): void
    {
        foreach (BaseCategoryConfig::all() as $category) {
            Categoria::query()->updateOrCreate(
                ['nombre' => $category['nombre']],
                [
                    'color' => $category['color'],
                    'icono' => $category['icono'],
                ]
            );
        }
    }
}
