<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Un pedido hecho desde la tienda web publica. Nace pendiente; el
     * equipo de ventas lo confirma o cancela manualmente. No hay cuenta de
     * cliente: solo se guardan los datos de contacto que dejo al comprar.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nombre', 120);
            $table->string('cliente_telefono', 20);
            $table->string('cliente_direccion', 255);
            $table->date('fecha');
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
