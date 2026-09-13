<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DispositivoDosFactoresModel extends Model
{
    protected $table = 'dispositivos_dos_factores';

    protected $fillable = ['user_id', 'token_id', 'nombre', 'vinculado_en', 'ultimo_uso_en'];

    protected function casts(): array
    {
        return [
            'vinculado_en' => 'datetime',
            'ultimo_uso_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
