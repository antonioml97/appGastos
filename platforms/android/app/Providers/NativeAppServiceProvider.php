<?php

namespace App\Providers;

use Native\Desktop\Facades\Window;
use Native\Desktop\Contracts\ProvidesPhpIni;

/**
 * Configura el arranque de la aplicacion cuando se ejecuta en entorno nativo.
 *
 * @autor Antonio Martin Leon
 */
class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Abre la ventana principal una vez iniciada la aplicacion nativa.
     */
    public function boot(): void
    {
        Window::open();
    }

    /**
     * Devuelve directivas adicionales de php.ini para el runtime nativo.
     *
     * @return array<int, string>
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
