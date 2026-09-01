<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;

/**
 * Sirve como controlador base para el resto de controladores de la aplicacion.
 *
 * @autor Antonio Martin Leon
 */
abstract class Controller
{
    protected function ensureOwned(Model $model): void
    {
        abort_unless(
            request()->user() !== null
                && (int) $model->getAttribute('user_id') === (int) request()->user()->getAuthIdentifier(),
            404
        );
    }
}
