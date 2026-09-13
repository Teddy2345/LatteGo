@props(['title', 'subtitle' => null, 'data', 'formatter' => null])

@php
    $formatter ??= fn (float $v): string => number_format($v, 0);
    $max = max(1.0, collect($data)->max('value'));
    $count = count($data);
    $chartWidth = 640;
    $chartHeight = 160;
    $gap = 10;
    $barWidth = $count > 0 ? ($chartWidth - $gap * ($count - 1)) / $count : 0;
@endphp

<div class="bg-white rounded-2xl border border-linea shadow-sm p-5">
    <div class="flex items-baseline justify-between mb-4">
        <h2 class="font-semibold text-bosque">{{ $title }}</h2>
        @if ($subtitle)
            <p class="text-xs text-piedra">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($count === 0 || $max <= 0)
        <p class="text-sm text-piedra text-center py-10">Sin datos suficientes todavía.</p>
    @else
        <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight + 24 }}" class="w-full" role="img" aria-label="{{ $title }}">
            {{-- linea base --}}
            <line x1="0" y1="{{ $chartHeight }}" x2="{{ $chartWidth }}" y2="{{ $chartHeight }}" stroke="#DFE5DA" stroke-width="1"></line>

            @foreach ($data as $i => $punto)
                @php
                    $alturaBarra = $max > 0 ? max(2, ($punto['value'] / $max) * ($chartHeight - 22)) : 2;
                    $x = $i * ($barWidth + $gap);
                    $y = $chartHeight - $alturaBarra;
                @endphp
                <g>
                    <title>{{ $punto['label'] }}: {{ $formatter($punto['value']) }}</title>
                    <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $alturaBarra }}"
                          rx="4" ry="4" fill="#356B50" class="hover:fill-[#173E32] transition-colors"></rect>
                    @if ($punto['value'] > 0)
                        <text x="{{ $x + $barWidth / 2 }}" y="{{ $y - 6 }}" text-anchor="middle" font-size="11" fill="#173E32" font-weight="600">
                            {{ $formatter($punto['value']) }}
                        </text>
                    @endif
                    <text x="{{ $x + $barWidth / 2 }}" y="{{ $chartHeight + 16 }}" text-anchor="middle" font-size="10" fill="#68746A">
                        {{ $punto['label'] }}
                    </text>
                </g>
            @endforeach
        </svg>
    @endif
</div>
