<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('despacho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produccion_id')->constrained('produccion')->restrictOnDelete();
            $table->foreignId('despachador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quesos_recibidos');
            $table->unsignedInteger('quesos_despachados');
            $table->unsignedInteger('merma')->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho');
    }
};
