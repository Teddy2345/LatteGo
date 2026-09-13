<?php

declare(strict_types=1);

use App\Application\Notificacion\DTOs\RegistrarNotificacionData;
use App\Application\Notificacion\UseCases\RegistrarNotificacionUseCase;
use App\Domain\Notificacion\ValueObjects\NivelNotificacion;
use App\Models\User;
use App\Presentation\Web\Livewire\Notificacion\Campana;
use App\Presentation\Web\Livewire\Notificacion\Centro;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

function usuarioNotificacion(string $rol): User
{
    app(RolePermissionSeeder::class)->run();

    $user = User::factory()->create();
    $user->assignRole($rol);

    return $user;
}

function crearNotificacionDePrueba(array $datos = []): void
{
    app(RegistrarNotificacionUseCase::class)->ejecutar(new RegistrarNotificacionData(
        tipo: 'calidad_rechazo',
        titulo: 'Alerta de calidad',
        mensaje: 'Leche de Juan Pérez Mamani rechazada por Adulteración.',
        nivel: NivelNotificacion::Alerta,
        datos: array_merge([
            'Proveedor' => 'Juan Pérez Mamani',
            'Fecha' => '2026-09-11',
            'Zona / ruta' => 'Huata Centro',
            'Motivo' => 'Adulteración',
            'Agua añadida' => '5.20 %',
            'Sanción' => 'Descuento de la semana',
        ], $datos),
    ));
}

test('el administrador ve el centro de notificaciones con el detalle completo', function () {
    crearNotificacionDePrueba();

    Livewire::actingAs(usuarioNotificacion('admin'))
        ->test(Centro::class)
        ->assertOk()
        ->assertSee('Alerta de calidad')
        ->assertSee('Juan Pérez Mamani')
        ->assertSee('5.20 %')
        ->assertSee('Descuento de la semana');
});

test('el acopiador no puede abrir el centro de notificaciones', function () {
    crearNotificacionDePrueba();

    Livewire::actingAs(usuarioNotificacion('acopiador'))
        ->test(Centro::class)
        ->assertForbidden();
});

test('marcar como leida deja de contarla en la campana', function () {
    crearNotificacionDePrueba();
    $admin = usuarioNotificacion('admin');

    // El badge rojo solo se renderiza cuando hay no leidas (ver campana.blade.php).
    Livewire::actingAs($admin)->test(Campana::class)->assertSeeHtml('bg-red-600');

    $id = \App\Infrastructure\Notificacion\Models\NotificacionModel::first()->id;

    Livewire::actingAs($admin)
        ->test(Centro::class)
        ->call('marcarLeida', $id);

    Livewire::actingAs($admin)->test(Campana::class)->assertDontSeeHtml('bg-red-600');
});

test('la campana no muestra el badge cuando no hay notificaciones', function () {
    Livewire::actingAs(usuarioNotificacion('admin'))
        ->test(Campana::class)
        ->assertOk()
        ->assertDontSeeHtml('bg-red-600');
});
