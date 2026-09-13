<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * El acopiador titular de cada movilidad: la persona real (camionero,
     * motocarguero o encargado de recepcion en planta) a quien pertenece esa
     * ruta y sus proveedores. Antes cualquier acopiador podia operar
     * cualquier movilidad; esto la fija a una sola persona.
     */
    public function up(): void
    {
        Schema::table('movilidades', function (Blueprint $table) {
            $table->foreignId('usuario_id')->nullable()->after('ruta_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movilidades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_id');
        });
    }
};
