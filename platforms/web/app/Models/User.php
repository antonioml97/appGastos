<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
/**
 * Representa al usuario autenticable de la aplicacion.
 *
 * @autor Antonio Martin Leon
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** @var array<int, string> */
    protected $fillable = ['name', 'email', 'password'];

    /** @var array<int, string> */
    protected $hidden = ['password', 'remember_token'];

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class);
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    /**
     * Define los casteos automaticos de los atributos del usuario.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
