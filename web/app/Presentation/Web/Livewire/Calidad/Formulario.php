<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Calidad;

use App\Application\Acopio\UseCases\ListarAcopiosUseCase;
use App\Application\Acopio\UseCases\ObtenerAcopioUseCase;
use App\Application\Calidad\DTOs\RegistrarCalidadData;
use App\Application\Calidad\UseCases\AnalizarFotoCalidadUseCase;
use App\Application\Calidad\UseCases\RegistrarCalidadUseCase;
use App\Domain\Calidad\Exceptions\CalidadException;
use App\Infrastructure\Calidad\Models\CalidadModel;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
final class Formulario extends Component
{
    use WithFileUploads;

    #[Validate('required|integer')]
    public ?int $acopioId = null;

    #[Validate('required|numeric')]
    public float $temperatura = 4.0;

    #[Validate('required|numeric')]
    public float $grasa = 3.5;

    #[Validate('required|numeric')]
    public float $solidosNoGrasos = 8.5;

    #[Validate('required|numeric')]
    public float $densidad = 30.0;

    #[Validate('required|numeric')]
    public float $proteina = 3.2;

    #[Validate('required|numeric')]
    public float $lactosa = 4.6;

    #[Validate('required|numeric')]
    public float $sales = 0.7;

    #[Validate('required|numeric|min:0')]
    public float $aguaAgregada = 0.0;

    #[Validate('required|numeric|min:0|max:14')]
    public float $ph = 6.7;

    public bool $pruebaAlcoholAceptada = true;

    #[Validate('nullable|in:acidez,otro')]
    public string $motivoRechazoManual = '';

    #[Validate('nullable|image|max:5120')]
    public $fotoEquipo = null;

    /** Ruta ya guardada de forma permanente (tras "Analizar foto"), para no subirla dos veces. */
    public ?string $fotoPath = null;

    public string $mensajeOcr = '';

    public string $error = '';

    public function mount(): void
    {
        $this->authorize('create', CalidadModel::class);
    }

    public function updatedFotoEquipo(): void
    {
        $this->fotoPath = null;
        $this->mensajeOcr = '';
    }

    /**
     * Guarda la foto de forma permanente y le pide al proveedor de OCR
     * configurado que intente reconocer los valores. Nunca completa el
     * formulario sin que el usuario vea y confirme lo que se detecto: hoy,
     * sin un proveedor de OCR real conectado, no se detecta nada y se pide
     * cargar los valores a mano.
     */
    public function analizarFoto(AnalizarFotoCalidadUseCase $analizar): void
    {
        $this->authorize('create', CalidadModel::class);

        if (! $this->fotoEquipo instanceof UploadedFile) {
            return;
        }

        $this->validateOnly('fotoEquipo');

        $this->fotoPath = $this->fotoEquipo->store('calidad', 'public');
        $lectura = $analizar->ejecutar(storage_path('app/public/'.$this->fotoPath));

        if (! $lectura->huboReconocimiento) {
            $this->mensajeOcr = 'No se reconocieron valores automáticamente. Completa el formulario manualmente.';

            return;
        }

        foreach ($lectura->valores as $campo => $valor) {
            if (property_exists($this, $campo)) {
                $this->{$campo} = $valor;
            }
        }

        $this->mensajeOcr = 'Valores detectados. Revísalos antes de guardar.';
    }

    public function guardar(ObtenerAcopioUseCase $obtenerAcopio, RegistrarCalidadUseCase $registrar): void
    {
        $this->authorize('create', CalidadModel::class);

        $this->validate();
        $this->error = '';

        if ($this->fotoPath === null && $this->fotoEquipo instanceof UploadedFile) {
            $this->fotoPath = $this->fotoEquipo->store('calidad', 'public');
        }

        try {
            $acopio = $obtenerAcopio->ejecutar($this->acopioId);

            $registrar->ejecutar(new RegistrarCalidadData(
                proveedorId: $acopio->proveedorId,
                acopioId: $this->acopioId,
                fecha: now()->format('Y-m-d'),
                temperatura: $this->temperatura,
                grasa: $this->grasa,
                solidosNoGrasos: $this->solidosNoGrasos,
                densidad: $this->densidad,
                proteina: $this->proteina,
                lactosa: $this->lactosa,
                sales: $this->sales,
                aguaAgregada: $this->aguaAgregada,
                ph: $this->ph,
                pruebaAlcoholAceptada: $this->pruebaAlcoholAceptada,
                motivoRechazoManual: $this->motivoRechazoManual === '' ? null : $this->motivoRechazoManual,
                fotoPath: $this->fotoPath,
            ));
        } catch (CalidadException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->redirect(route('calidad.index'), navigate: true);
    }

    public function render(ListarAcopiosUseCase $listarAcopios): View
    {
        return view('livewire.calidad.formulario', [
            'acopios' => $listarAcopios->ejecutar(),
        ]);
    }
}
