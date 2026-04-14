<?php

namespace App\Contracts\Repositories;

use App\Models\Categoria;

/**
 * Define las operaciones de persistencia para la gestion de categorias.
 *
 * @autor Antonio Martin Leon
 */
interface CategoryRepositoryInterface
{
    /**
     * Construye el payload necesario para mostrar el listado de categorias.
     *
     * @return array<string, mixed>
     */
    public function getIndexPayload(): array;

    /**
     * Crea una nueva categoria a partir de los datos validados.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Categoria;

    /**
     * Actualiza una categoria existente con los datos recibidos.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Categoria $categoria, array $data): Categoria;

    /**
     * Elimina una categoria existente.
     */
    public function delete(Categoria $categoria): void;
}
