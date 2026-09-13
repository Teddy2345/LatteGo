<?php

declare(strict_types=1);

namespace App\Infrastructure\Produccion\Models;

use App\Models\User;
use Database\Factories\DespachoModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class DespachoModel extends Model
{
    /** @use HasFactory<DespachoModelFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'despacho';

    protected $fillable = [
        'produccion_id',
        'despachador_id',
        'quesos_recibidos',
        'quesos_despachados',
        'merma',
        'observaciones',
    ];

    protected static function newFactory(): DespachoModelFactory
    {
        return DespachoModelFactory::new();
    }

    public function produccion(): BelongsTo
    {
        return $this->belongsTo(ProduccionModel::class, 'produccion_id');
    }

    public function despachador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'despachador_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('despacho');
    }
}
