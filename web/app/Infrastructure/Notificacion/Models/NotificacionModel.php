<?php

declare(strict_types=1);

namespace App\Infrastructure\Notificacion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NotificacionModel extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = ['tipo', 'titulo', 'mensaje', 'nivel', 'datos'];

    protected function casts(): array
    {
        return ['datos' => 'array'];
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(NotificacionLecturaModel::class, 'notificacion_id');
    }
}
