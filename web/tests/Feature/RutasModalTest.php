<?php

declare(strict_types=1);
use App\Infrastructure\Proveedor\Models\RutaModel;
use App\Models\User;
use App\Presentation\Web\Livewire\Acopio\Index;
use Livewire\Livewire;

test('rutas modal survives subsequent Livewire requests', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('admin');
    $route = RutaModel::create(['nombre' => 'Achacachi', 'activa' => true]);
    Livewire::actingAs($user)->test(Index::class)
        ->call('abrirModalRutas')
        ->assertSet('showRutasModal', true)
        ->assertSet('rutas.0.nombre', 'Achacachi')
        ->assertSee('Achacachi')
        ->call('agregarCamion', $route->id)
        ->assertSee('Camión 1')
        ->set('showRutasModal', false)
        ->assertSet('showRutasModal', false);
});
