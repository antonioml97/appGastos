<?php

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Categoria;
use App\Support\CategoryIconCatalog;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
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

    public function create(array $data): Categoria
    {
        return Categoria::query()
            ->create($data)
            ->loadCount('gastos');
    }

    public function update(Categoria $categoria, array $data): Categoria
    {
        $categoria->update($data);

        return $categoria->loadCount('gastos');
    }

    public function delete(Categoria $categoria): void
    {
        $categoria->delete();
    }

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
