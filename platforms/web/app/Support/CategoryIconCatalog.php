<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Centraliza el descubrimiento y listado de iconos disponibles para categorias.
 *
 * @autor Antonio Martin Leon
 */
class CategoryIconCatalog
{
    /**
     * Devuelve todos los iconos detectados en la carpeta publica.
     *
     * @return array<int, array<string, string>>
     */
    public static function all(): array
    {
        $directory = public_path('images/icons');

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::allFiles($directory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['svg', 'png', 'jpg', 'jpeg', 'webp'], true))
            ->map(function ($file) use ($directory) {
                $relativePath = str_replace('\\', '/', Str::after($file->getPathname(), $directory.DIRECTORY_SEPARATOR));

                return [
                    'name' => $relativePath,
                    'label' => Str::of(pathinfo($relativePath, PATHINFO_FILENAME))
                        ->replace(['-', '_'], ' ')
                        ->title()
                        ->toString(),
                    'url' => '/images/icons/'.$relativePath,
                ];
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Devuelve unicamente los nombres internos de los iconos disponibles.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(static::all())
            ->pluck('name')
            ->all();
    }
}
