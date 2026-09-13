<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Proveedor;

use App\Application\Proveedor\DTOs\ActualizarProveedorData;
use App\Application\Proveedor\DTOs\RegistrarProveedorData;
use App\Application\Proveedor\UseCases\ActualizarProveedorUseCase;
use App\Application\Proveedor\UseCases\ListarRutasDisponiblesUseCase;
use App\Application\Proveedor\UseCases\ObtenerProveedorUseCase;
use App\Application\Proveedor\UseCases\ReasignarRutaProveedorUseCase;
use App\Application\Proveedor\UseCases\RegistrarProveedorUseCase;
use App\Domain\Proveedor\Exceptions\ProveedorException;
use App\Infrastructure\Proveedor\Models\ProveedorModel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    public ?int $proveedorId = null;

    public ?int $rutaOriginal = null;

    #[Validate('required|string|max:120')]
    public string $nombre = '';

    #[Validate('required|string|min:6|max:30')]
    public string $cedula = '';

    #[Validate('nullable|string|max:20')]
    public ?string $telefono = null;

    #[Validate('nullable|string|max:120')]
    public ?string $finca = null;

    #[Validate('required|integer|min:0')]
    public int $litrosProm = 0;

    #[Validate('required|numeric|min:0.01')]
    public float $precioLitro = 0.0;

    #[Validate('nullable|integer')]
    public ?int $rutaId = null;

    public string $error = '';

    public function mount(ObtenerProveedorUseCase $obtener, ?int $proveedorId = null): void
    {
        if ($proveedorId === null) {
            $this->authorize('create', ProveedorModel::class);

            return;
        }

        $this->authorize('update', ProveedorModel::class);

        $proveedor = $obtener->ejecutar($proveedorId);

        $this->proveedorId = $proveedor->id;
        $this->nombre = $proveedor->nombre;
        $this->cedula = $proveedor->cedula;
        $this->telefono = $proveedor->telefono;
        $this->finca = $proveedor->finca;
        $this->litrosProm = $proveedor->litrosProm;
        $this->precioLitro = $proveedor->precioLitro;
        $this->rutaId = $proveedor->rutaId;
        $this->rutaOriginal = $proveedor->rutaId;
    }

    public function guardar(
        RegistrarProveedorUseCase $registrar,
        ActualizarProveedorUseCase $actualizar,
        ReasignarRutaProveedorUseCase $reasignarRuta,
    ): void {
        $this->authorize($this->proveedorId === null ? 'create' : 'update', ProveedorModel::class);

        $this->validate();
        $this->error = '';

        try {
            if ($this->proveedorId === null) {
                $registrar->ejecutar(new RegistrarProveedorData(
                    nombre: $this->nombre,
                    cedula: $this->cedula,
                    telefono: $this->telefono,
                    finca: $this->finca,
                    litrosProm: $this->litrosProm,
                    precioLitro: $this->precioLitro,
                    rutaId: $this->rutaId,
                ));
            } else {
                $actualizar->ejecutar(new ActualizarProveedorData(
                    id: $this->proveedorId,
                    nombre: $this->nombre,
                    telefono: $this->telefono,
                    finca: $this->finca,
                    litrosProm: $this->litrosProm,
                    precioLitro: $this->precioLitro,
                ));

                if ($this->rutaId !== $this->rutaOriginal) {
                    $reasignarRuta->ejecutar($this->proveedorId, $this->rutaId);
                }
            }
        } catch (ProveedorException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('proveedores.index'), navigate: true);
    }

    public function render(ListarRutasDisponiblesUseCase $listarRutas): View
    {
        return view('livewire.proveedor.formulario', [
            'rutas' => $listarRutas->ejecutar(),
        ]);
    }
}
