<?php

declare(strict_types=1);

use App\Infrastructure\Pagos\Models\PagoModel;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Proveedor\Index;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioPrecio(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

test('el listado muestra el precio real de cada proveedor y no uno fijo', function () {
    // El listado traia 1.70 escrito a fuego, asi que el precio que veia el
    // administrador no era el que se estaba pagando.
    ProveedorModel::factory()->create(['nombre' => 'Juan Mamani', 'precio_litro' => 3.45, 'activo' => true]);

    Livewire::actingAs(usuarioPrecio('admin'))
        ->test(Index::class)
        ->assertSee('3.45')
        ->assertDontSee('1.70');
});

test('el administrador fija el precio de la temporada para todo el padron', function () {
    ProveedorModel::factory()->create(['precio_litro' => 3.20]);
    ProveedorModel::factory()->create(['precio_litro' => 4.10]);
    ProveedorModel::factory()->create(['precio_litro' => 2.80, 'activo' => false]);

    Livewire::actingAs(usuarioPrecio('admin'))
        ->test(Index::class)
        ->call('abrirPrecioTemporada')
        ->set('precioTemporada', 3.75)
        ->call('aplicarPrecioTemporada')
        ->assertSet('errorPrecio', '')
        ->assertSee('3 proveedores');

    expect(ProveedorModel::query()->pluck('precio_litro')->map(fn ($p): float => (float) $p)->all())
        ->each->toBe(3.75);
});

test('cambiar el precio no reescribe las planillas ya pagadas', function () {
    $proveedor = ProveedorModel::factory()->create(['precio_litro' => 3.00]);
    $pago = PagoModel::create([
        'proveedor_id' => $proveedor->id,
        'semana_inicio' => '2026-09-03',
        'semana_fin' => '2026-09-09',
        'total_litros' => 100,
        'precio_litro' => 3.00,
        'total_pagar' => 300,
        'estado' => 'pagado',
    ]);

    Livewire::actingAs(usuarioPrecio('admin'))
        ->test(Index::class)
        ->call('abrirPrecioTemporada')
        ->set('precioTemporada', 5.00)
        ->call('aplicarPrecioTemporada');

    $pago->refresh();
    expect((float) $pago->precio_litro)->toBe(3.00)
        ->and((float) $pago->total_pagar)->toBe(300.0)
        ->and((float) $proveedor->fresh()->precio_litro)->toBe(5.00);
});

test('un precio de cero o negativo se rechaza', function () {
    ProveedorModel::factory()->create(['precio_litro' => 3.00]);

    Livewire::actingAs(usuarioPrecio('admin'))
        ->test(Index::class)
        ->call('abrirPrecioTemporada')
        ->set('precioTemporada', 0)
        ->call('aplicarPrecioTemporada')
        ->assertSet('avisoPrecio', '')
        ->assertSee('mayor a 0');

    expect((float) ProveedorModel::query()->value('precio_litro'))->toBe(3.00);
});

test('quien no edita proveedores no puede cambiar el precio', function () {
    ProveedorModel::factory()->create(['precio_litro' => 3.00]);

    // El acopiador ni siquiera abre el listado; ventas tampoco lo edita.
    Livewire::actingAs(usuarioPrecio('acopiador'))
        ->test(Index::class)
        ->assertForbidden();
});
