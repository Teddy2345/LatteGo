@props(['label', 'value', 'hint' => null, 'tone' => 'campo'])

@php
$tones = [
    'campo' => 'bg-salvia text-campo',
    'amber' => 'bg-amber-50 text-amber-700',
    'red' => 'bg-red-50 text-red-700',
    'green' => 'bg-green-50 text-green-700',
];
$iconTone = $tones[$tone] ?? $tones['campo'];
@endphp

<div class="bg-white rounded-2xl border border-linea shadow-sm p-5 flex items-start gap-4">
    <div class="h-11 w-11 rounded-lg flex items-center justify-center shrink-0 {{ $iconTone }}">
        {{ $icon ?? '' }}
    </div>
    <div class="min-w-0">
        <p class="text-sm text-piedra">{{ $label }}</p>
        <p class="text-2xl font-semibold text-bosque mt-0.5">{{ $value }}</p>
        @if ($hint)
            <p class="text-xs text-piedra mt-1">{{ $hint }}</p>
        @endif
    </div>
</div>
