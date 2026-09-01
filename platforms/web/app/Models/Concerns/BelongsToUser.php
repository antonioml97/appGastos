<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Limita los modelos personales al usuario autenticado y asigna su propietario.
 */
trait BelongsToUser
{
    protected static function bootBelongsToUser(): void
    {
        static::addGlobalScope('user', function (Builder $builder): void {
            $userId = request()?->user()?->getAuthIdentifier() ?? auth()->id();

            if ($userId !== null) {
                $builder->where($builder->qualifyColumn('user_id'), $userId);
            }
        });

        static::creating(function ($model): void {
            if ($model->user_id === null) {
                $model->user_id = request()?->user()?->getAuthIdentifier() ?? auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
