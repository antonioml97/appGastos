<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una categoria usada para clasificar los gastos.
 *
 * @autor Antonio Martin Leon
 */
class Categoria extends Model
{
    use BelongsToUser;

    protected $table = 'categorias';

    protected $fillable = [
        'user_id',
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

    /**
     * Obtiene los movimientos fijos asociados a la categoria.
     */
    public function movimientosFijos(): HasMany
    {
        return $this->hasMany(MovimientoFijo::class, 'categoria_id');
    }
}
