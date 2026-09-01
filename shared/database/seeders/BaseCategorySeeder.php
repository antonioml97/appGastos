<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::query()->each(fn (User $user) => BaseCategoryConfig::syncToDatabase($user->id));
    }
}
