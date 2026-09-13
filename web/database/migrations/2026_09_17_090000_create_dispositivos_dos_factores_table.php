<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telefonos que su dueño vinculo desde la web para recibir ahi el codigo de
 * verificacion en dos pasos. Solo un dispositivo vinculado puede pedir el
 * codigo: quien entre a la app movil sabiendo unicamente la contraseña no
 * recibe nada, que es lo que mantiene al telefono como segundo factor real.
 *
 * La vinculacion cuelga del token de ese telefono, asi que cerrar sesion en
 * la app la deshace y hay que volver a vincular.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('dispositivos_dos_factores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('token_id')->constrained('personal_access_tokens')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamp('vinculado_en');
            $table->timestamp('ultimo_uso_en')->nullable();
            $table->timestamps();

            $table->unique('token_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos_dos_factores');
    }
};
