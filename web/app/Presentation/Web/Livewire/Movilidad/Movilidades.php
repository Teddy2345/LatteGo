<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Movilidad;

use App\Application\Movilidad\UseCases\ListarMovilidadesParaAcopiadorUseCase;
use App\Infrastructure\Movilidad\Models\MovilidadModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class Movilidades extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', MovilidadModel::class);
    }

    public function render(ListarMovilidadesParaAcopiadorUseCase $listar): View
    {
        $user = auth()->user();
        $usuarioId = $user->can('reportes.ver') ? null : $user->id;

        return view('livewire.movilidad.movilidades', [
            'movilidades' => $listar->ejecutar(usuarioId: $usuarioId),
            'filtradoPorUsuario' => $usuarioId !== null,
        ]);
    }
}
