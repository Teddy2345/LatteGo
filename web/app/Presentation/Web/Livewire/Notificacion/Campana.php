<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Notificacion;

use App\Application\Notificacion\UseCases\ContarNotificacionesNoLeidasUseCase;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Campana del header: solo el numero de no leidas. Se embebe directo en el
 * layout (no depende de una ruta), asi que no repite la autorizacion de la
 * pantalla completa: si el usuario no puede ver notificaciones, el propio
 * layout no la incluye (@can en app.blade.php).
 */
final class Campana extends Component
{
    public function render(ContarNotificacionesNoLeidasUseCase $contar): View
    {
        return view('livewire.notificacion.campana', [
            'noLeidas' => $contar->ejecutar(auth()->id()),
        ]);
    }
}
