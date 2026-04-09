<?php

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Categoria;
use App\Support\CategoryIconCatalog;

/**
 * Implementa la gestion de categorias usando Eloquent.
 *
 * @autor Antonio Martin Leon
 */
class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Construye el payload usado por la pantalla de categorias.
     *
     * @return array<string, mixed>
     */
    public function getIndexPayload(): array
    {
        return [
            'page' => 'categories',
            'title' => 'Categorias',
            'categoryIcons' => CategoryIconCatalog::all(),
            'categories' => Categoria::query()
                ->withCount('gastos')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Categoria $categoria) => $this->serializeCategory($categoria))
                ->values(),
        ];
    }

    /**
     * Crea una categoria y devuelve el modelo enriquecido con el conteo de gastos.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Categoria
    {
        return Categoria::query()
            ->create($data)
            ->loadCount('gastos');
    }

    /**
     * Actualiza una categoria existente.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Categoria $categoria, array $data): Categoria
    {
        $categoria->update($data);

        return $categoria->loadCount('gastos');
    }

    /**
     * Elimina una categoria persistida.
     */
    public function delete(Categoria $categoria): void
    {
        $categoria->delete();
    }

    /**
     * Transforma una categoria en un array listo para la interfaz.
     *
     * @return array<string, mixed>
     */
    private function serializeCategory(Categoria $categoria): array
    {
        return [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'color' => $categoria->color,
            'icono' => $categoria->icono,
            'gastos_count' => $categoria->gastos_count,
        ];
    }
}
