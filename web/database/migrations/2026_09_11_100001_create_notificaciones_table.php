<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Centro de notificaciones del sistema: alertas de calidad, y a futuro
     * stock bajo, cambios de zona pendientes, errores de sincronizacion,
     * etc. "datos" guarda el detalle especifico de cada tipo (proveedor,
     * resultado, sancion...) sin forzar una columna por caso de uso.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->string('titulo', 160);
            $table->text('mensaje');
            $table->string('nivel', 20)->default('info');
            $table->json('datos')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
