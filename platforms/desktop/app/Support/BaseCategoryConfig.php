<?php

namespace App\Support;

use App\Models\Categoria;

/**
 * Lee y normaliza la configuracion de categorias base compartida por la app.
 *
 * @autor Antonio Martin Leon
 */
class BaseCategoryConfig
{
    /**
     * Localiza la ruta real del fichero de categorias base.
     */
    private static function resolveConfigPath(): ?string
    {
        $candidates = [
            dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config.json',
            dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'shared'.DIRECTORY_SEPARATOR.'config.json',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Devuelve las categorias base definidas en el fichero config.json.
     *
     * @return array<int, array{id: string, nombre: string, icono: string, color: string}>
     */
    public static function all(): array
    {
        $path = self::resolveConfigPath();

        if ($path === null) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded) || ! isset($decoded['categoriasBase']) || ! is_array($decoded['categoriasBase'])) {
            return [];
        }

        return collect($decoded['categoriasBase'])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'id' => (string) ($item['id'] ?? ''),
                'nombre' => (string) ($item['nombre'] ?? ''),
                'icono' => (string) ($item['icono'] ?? ''),
                'color' => (string) ($item['color'] ?? ''),
            ])
            ->filter(fn (array $item) => $item['id'] !== '' && $item['nombre'] !== '' && $item['icono'] !== '' && $item['color'] !== '')
            ->values()
            ->all();
    }

    /**
     * Devuelve solo los nombres de las categorias base configuradas.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(self::all())
            ->pluck('nombre')
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->values()
            ->all();
    }

    /**
     * Indica si una categoria pertenece al catalogo base definido en JSON.
     */
    public static function isBaseCategory(Categoria|string $category): bool
    {
        $name = $category instanceof Categoria ? $category->nombre : $category;

        return in_array(trim($name), self::names(), true);
    }

    /**
     * Garantiza que las categorias base existan tambien en la base de datos.
     */
    public static function syncToDatabase(?int $userId = null): void
    {
        $userId ??= request()?->user()?->getAuthIdentifier() ?? auth()->id();

        foreach (self::all() as $category) {
            Categoria::query()->updateOrCreate(
                ['user_id' => $userId, 'nombre' => $category['nombre']],
                [
                    'color' => $category['color'],
                    'icono' => $category['icono'],
                ]
            );
        }
    }
}
