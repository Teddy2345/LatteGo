<?php

declare(strict_types=1);

use App\Infrastructure\Acopio\Models\AcopioModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Calidad\Formulario as CalidadFormulario;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioCalidad(): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole('calidad');

    return $user;
}

/** Una entrega sin analizar, de un proveedor con nombre reconocible. */
function entregaSinAnalizar(string $proveedor): AcopioModel
{
    $ficha = ProveedorModel::factory()->create(['nombre' => $proveedor, 'activo' => true]);

    return AcopioModel::factory()->create([
        'proveedor_id' => $ficha->id,
        'fecha' => now()->format('Y-m-d'),
        'cantidad_litros' => 25.5,
    ]);
}

test('al elegir la entrega que se analiza se ve de quien es la leche', function () {
    // Elegirla solo por su numero es a ciegas: quien analiza necesita el nombre.
    $acopio = entregaSinAnalizar('Rosa Quispe');

    Livewire::actingAs(usuarioCalidad())
        ->test(CalidadFormulario::class)
        ->assertOk()
        ->assertSee('Rosa Quispe')
        ->assertSee('25.50')
        ->assertSee('#'.$acopio->id);
});

test('la entrega ya analizada deja de ofrecerse', function () {
    $usuario = usuarioCalidad();
    $acopio = entregaSinAnalizar('Juan Mamani');

    Livewire::actingAs($usuario)
        ->test(CalidadFormulario::class)
        ->assertSee('Juan Mamani')
        ->set('acopioId', $acopio->id)
        ->call('guardar')
        ->assertHasNoErrors();

    Livewire::actingAs($usuario)
        ->test(CalidadFormulario::class)
        ->assertDontSee('Juan Mamani');
});
