<?php

namespace App\Contracts\Repositories;

use App\Models\Categoria;

interface CategoryRepositoryInterface
{
    public function getIndexPayload(): array;

    public function create(array $data): Categoria;

    public function update(Categoria $categoria, array $data): Categoria;

    public function delete(Categoria $categoria): void;
}
