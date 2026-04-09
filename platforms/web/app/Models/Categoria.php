<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una categoria usada para clasificar los gastos.
 *
 * @autor Antonio Martin Leon
 */
class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'color',
        'icono',
    ];

    /**
     * Obtiene los gastos asociados a la categoria.
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'categoria_id');
    }
}
