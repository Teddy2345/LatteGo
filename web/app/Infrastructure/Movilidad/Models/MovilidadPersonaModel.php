<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovilidadPersonaModel extends Model
{
    protected $table = 'movilidad_personas';

    protected $fillable = ['movilidad_id', 'nombre'];

    public function movilidad(): BelongsTo
    {
        return $this->belongsTo(MovilidadModel::class, 'movilidad_id');
    }
}
