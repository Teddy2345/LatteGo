<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Notificacion;

use App\Application\Notificacion\UseCases\ListarNotificacionesUseCase;
use App\Application\Notificacion\UseCases\MarcarNotificacionLeidaUseCase;
use App\Infrastructure\Notificacion\Models\NotificacionModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Centro extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', NotificacionModel::class);
    }

    public function marcarLeida(int $notificacionId, MarcarNotificacionLeidaUseCase $marcar): void
    {
        $this->authorize('viewAny', NotificacionModel::class);

        $marcar->ejecutar($notificacionId, auth()->id());
    }

    public function render(ListarNotificacionesUseCase $listar): View
    {
        return view('livewire.notificacion.centro', [
            'notificaciones' => $listar->ejecutar(auth()->id()),
        ]);
    }
}
