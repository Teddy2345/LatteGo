<?php

declare(strict_types=1);

namespace App\Presentation\Web\Livewire\Auth;

use App\Infrastructure\Auth\Models\DispositivoDosFactoresModel;
use App\Presentation\Api\V1\Support\VinculacionDosFactores;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class TwoFactorSetup extends Component
{
    public string $code = '';

    public string $error = '';

    public string $aviso = '';

    /** El codigo que muestra la campana del telefono que se quiere vincular. */
    public string $codigoTelefono = '';

    public string $errorVinculacion = '';

    public function habilitar(EnableTwoFactorAuthentication $enable): void
    {
        $enable(Auth::user());
    }

    /**
     * Los codigos son de un solo uso: cuando se acaban (o se extravian) hay
     * que emitir una tanda nueva, que invalida la anterior.
     */
    public function regenerarCodigos(GenerateNewRecoveryCodes $generar): void
    {
        $generar(Auth::user());

        $this->aviso = 'Se generaron códigos nuevos. Los anteriores quedaron anulados.';
    }

    /**
     * Vincula el telefono cuyo codigo se escribio aqui: la app lo genera en
     * la campana, sirve una sola vez y solo para un telefono de esta misma
     * cuenta. A partir de ahi ese telefono recibe el codigo de 6 digitos.
     */
    public function vincularTelefono(): void
    {
        $this->errorVinculacion = '';
        $this->aviso = '';

        $limite = 'vincular-telefono:'.Auth::id();

        if (RateLimiter::tooManyAttempts($limite, 5)) {
            $this->errorVinculacion = 'Demasiados intentos. Espera un minuto y genera un código nuevo en la app.';

            return;
        }

        RateLimiter::hit($limite, 60);

        $telefono = VinculacionDosFactores::vincularConCodigo(Auth::user(), $this->codigoTelefono);

        if ($telefono === null) {
            $this->errorVinculacion = 'El código no es válido o ya venció. Genera uno nuevo en la campana de la app.';

            return;
        }

        RateLimiter::clear($limite);
        $this->codigoTelefono = '';
        $this->aviso = "Teléfono vinculado ({$telefono->nombre}). Ya puedes ver tu código en la campana de la app.";
    }

    /** Desvincular un telefono perdido o que ya no es de confianza. */
    public function desvincularTelefono(int $dispositivoId): void
    {
        DispositivoDosFactoresModel::query()
            ->where('user_id', Auth::id())
            ->where('id', $dispositivoId)
            ->delete();

        $this->aviso = 'El teléfono quedó desvinculado y ya no recibirá códigos.';
    }

    public function confirmar(ConfirmTwoFactorAuthentication $confirm): void
    {
        $this->error = '';

        try {
            $confirm(Auth::user(), $this->code);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error = 'Codigo invalido. Intenta nuevamente.';

            return;
        }

        $this->code = '';
    }

    public function render(): View
    {
        $user = Auth::user()->fresh();

        return view('livewire.auth.two-factor-setup', [
            'pendienteConfirmar' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'confirmado' => $user->two_factor_confirmed_at !== null,
            'qrCode' => $user->two_factor_secret !== null ? $user->twoFactorQrCodeSvg() : null,
            'claveManual' => $user->two_factor_secret !== null ? $this->claveManual($user->two_factor_secret) : null,
            'recoveryCodes' => $user->two_factor_confirmed_at !== null ? $user->recoveryCodes() : [],
            'telefonosVinculados' => DispositivoDosFactoresModel::query()
                ->where('user_id', $user->id)
                ->orderByDesc('vinculado_en')
                ->get(),
        ]);
    }

    /**
     * La misma clave del QR, en texto y por bloques de cuatro: sirve para
     * dar de alta la cuenta escribiendola a mano cuando no hay camara para
     * escanear el codigo.
     */
    private function claveManual(string $secretoCifrado): string
    {
        // Fortify guarda el secreto serializado, asi que hay que
        // deserializarlo (decrypt) y no solo descifrarlo (decryptString).
        return trim(chunk_split(Crypt::decrypt($secretoCifrado), 4, ' '));
    }
}
