<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-bosque">Notificaciones</h1>
        <p class="text-sm text-piedra mt-1">Alertas del sistema: calidad, cambios de zona y otras incidencias.</p>
    </div>

    <div class="space-y-3">
        @forelse ($notificaciones as $n)
            @php
                $colores = match ($n->nivel) {
                    'alerta' => ['border' => 'border-red-200', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'icon' => 'text-red-600'],
                    'advertencia' => ['border' => 'border-amber-200', 'bg' => 'bg-amber-50', 'text' => 'text-amber-800', 'icon' => 'text-amber-600'],
                    default => ['border' => 'border-linea', 'bg' => 'bg-white', 'text' => 'text-bosque', 'icon' => 'text-piedra'],
                };
            @endphp
            <div class="rounded-2xl border {{ $colores['border'] }} {{ $colores['bg'] }} shadow-sm p-5 {{ $n->leida ? 'opacity-70' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <svg class="h-5 w-5 mt-0.5 shrink-0 {{ $colores['icon'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                        <div class="min-w-0">
                            <p class="font-semibold {{ $colores['text'] }} uppercase text-xs tracking-wide">{{ $n->titulo }}</p>
                            <p class="text-sm text-bosque mt-1">{{ $n->mensaje }}</p>

                            @if (!empty($n->datos))
                                <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    @foreach ($n->datos as $etiqueta => $valor)
                                        @if ($valor !== null && $valor !== '')
                                            <div class="contents">
                                                <dt class="text-piedra">{{ $etiqueta }}</dt>
                                                <dd class="text-bosque font-medium">{{ $valor }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            @endif

                            <p class="text-xs text-piedra mt-2">{{ $n->creadaEn }}</p>
                        </div>
                    </div>

                    @unless ($n->leida)
                        <button type="button" wire:click="marcarLeida({{ $n->id }})"
                                class="shrink-0 text-xs text-campo hover:underline whitespace-nowrap">
                            Marcar leída
                        </button>
                    @endunless
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-linea shadow-sm p-10 text-center text-piedra text-sm">
                No hay notificaciones todavía.
            </div>
        @endforelse
    </div>
</div>
