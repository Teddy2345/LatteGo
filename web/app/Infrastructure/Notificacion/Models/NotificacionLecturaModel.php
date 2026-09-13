<?php

declare(strict_types=1);

namespace App\Infrastructure\Notificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificacionLecturaModel extends Model
{
    public $timestamps = false;

    protected $table = 'notificacion_lecturas';

    protected $fillable = ['notificacion_id', 'user_id', 'leida_en'];

    protected function casts(): array
    {
        return ['leida_en' => 'datetime'];
    }

    public function notificacion(): BelongsTo
    {
        return $this->belongsTo(NotificacionModel::class, 'notificacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
