<?php

declare(strict_types=1);

namespace App\Infrastructure\Movilidad\Models;

use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class MovilidadModel extends Model
{
    use LogsActivity;

    protected $table = 'movilidades';

    protected $fillable = [
        'nombre',
        'tipo',
        'ruta_id',
        'activa',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(RutaModel::class, 'ruta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(MovilidadPersonaModel::class, 'movilidad_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('movilidad');
    }
}
