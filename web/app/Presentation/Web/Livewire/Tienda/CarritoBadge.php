<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Tienda;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Contador del carrito en el header de la tienda. Se embebe directo en el
 * layout publico y se refresca cuando otro componente de la pagina avisa
 * que el carrito cambio, sin depender de una navegacion completa.
 */
final class CarritoBadge extends Component
{
    #[On('carrito-actualizado')]
    public function refrescar(): void
    {
        // El re-render solo necesita volver a leer la sesion; no hay estado propio.
    }

    public function render(): View
    {
        return view('livewire.tienda.carrito-badge', [
            'cantidad' => count(session('carrito', [])),
        ]);
    }
}
