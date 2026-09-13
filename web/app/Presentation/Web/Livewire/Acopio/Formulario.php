<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Acopio;

use App\Application\Acopio\DTOs\RegistrarAcopioData;
use App\Application\Acopio\UseCases\RegistrarAcopioUseCase;
use App\Application\Proveedor\DTOs\ProveedorData;
use App\Application\Proveedor\UseCases\ListarProveedoresActivosUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Domain\Acopio\Exceptions\AcopioException;
use App\Domain\Movilidad\Repositories\MovilidadRepository;
use App\Infrastructure\Acopio\Models\AcopioModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    #[Validate('required|integer')]
    public ?int $proveedorId = null;

    #[Validate('required|date')]
    public string $fecha = '';

    #[Validate('required|numeric|min:0.01')]
    public float $cantidadLitros = 0.0;

    #[Validate('nullable|numeric|min:0')]
    public ?float $perdidaLitros = null;

    #[Validate('nullable|string|max:255')]
    public ?string $motivoPerdida = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $observaciones = null;

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('create', AcopioModel::class);

        $this->fecha = now()->format('Y-m-d');
    }

    public function guardar(
        RegistrarAcopioUseCase $registrar,
        ObtenerProveedorUseCase $obtenerProveedor,
        MovilidadRepository $movilidades,
    ): void {
        $this->authorize('create', AcopioModel::class);

        $this->validate();
        $this->error = '';

        $proveedor = $obtenerProveedor->ejecutar($this->proveedorId);
        $rutaPermitida = $this->rutaPermitida($movilidades);

        if ($rutaPermitida !== null && $proveedor->rutaId !== $rutaPermitida) {
            $this->error = 'Ese proveedor no pertenece a tu ruta.';

            return;
        }

        try {
            $registrar->ejecutar(new RegistrarAcopioData(
                proveedorId: $this->proveedorId,
                acopiadorId: auth()->id(),
                // La zona sale del proveedor: sin esto el acopio cae en
                // "Sin zona asignada" del consolidado diario.
                rutaId: $proveedor->rutaId,
                fecha: $this->fecha,
                cantidadLitros: $this->cantidadLitros,
                observaciones: $this->observaciones,
                perdidaLitros: $this->perdidaLitros,
                motivoPerdida: $this->motivoPerdida,
            ));
        } catch (AcopioException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('acopios.index'), navigate: true);
    }

    public function render(ListarProveedoresActivosUseCase $listarProveedores, MovilidadRepository $movilidades): View
    {
        return view('livewire.acopio.formulario', [
            'proveedores' => $this->proveedoresDeSuRuta($listarProveedores->ejecutar(), $movilidades),
        ]);
    }

    /**
     * Un acopiador de campo solo registra a los proveedores de la ruta de su
     * propia movilidad. Quien ve reportes de toda la planta los ve a todos.
     *
     * @param  ProveedorData[]  $proveedores
     * @return ProveedorData[]
     */
    private function proveedoresDeSuRuta(array $proveedores, MovilidadRepository $movilidades): array
    {
        $rutaPermitida = $this->rutaPermitida($movilidades);

        if ($rutaPermitida === null) {
            return $proveedores;
        }

        return array_values(array_filter(
            $proveedores,
            static fn (ProveedorData $p): bool => $p->rutaId === $rutaPermitida,
        ));
    }

    /**
     * La unica ruta que este usuario puede acopiar, o null si puede acopiar
     * en cualquiera (quien ve reportes de toda la planta). Un acopiador sin
     * movilidad asignada no puede acopiar en ninguna: devuelve 0, que no
     * coincide con ninguna ruta real.
     */
    private function rutaPermitida(MovilidadRepository $movilidades): ?int
    {
        if (auth()->user()->can('reportes.ver')) {
            return null;
        }

        return $movilidades->buscarPorUsuario(auth()->id())?->rutaId ?? 0;
    }
}
