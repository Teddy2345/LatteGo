<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Una notificacion puede ser vista por varios usuarios (admin y
     * supervisor); cada quien marca su propia lectura. La ausencia de fila
     * significa "no leida".
     */
    public function up(): void
    {
        Schema::create('notificacion_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notificacion_id')->constrained('notificaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('leida_en');

            $table->unique(['notificacion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_lecturas');
    }
};
