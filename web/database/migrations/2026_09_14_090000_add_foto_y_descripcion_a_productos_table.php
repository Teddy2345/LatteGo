<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * La tienda web publica necesita mostrar una foto y una descripcion
     * corta de cada producto terminado; ambas son opcionales para no exigir
     * este dato en el catalogo interno de insumos, que nunca se muestra al
     * publico.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('categoria');
            $table->text('descripcion')->nullable()->after('foto_path');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['foto_path', 'descripcion']);
        });
    }
};
