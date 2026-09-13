@props(['producto'])

<div class="bg-white rounded-2xl border shadow-sm p-5 {{ $producto->activo ? '' : 'opacity-60' }} {{ $producto->stockBajo ? 'border-amber-300' : 'border-linea' }}">
    <div class="flex items-start gap-4">
        @if ($producto->fotoPath)
            <img src="{{ Illuminate\Support\Facades\Storage::url($producto->fotoPath) }}" alt="{{ $producto->nombre }}"
                 class="h-11 w-11 rounded-lg object-cover shrink-0">
        @else
            <div class="h-11 w-11 rounded-lg flex items-center justify-center shrink-0 {{ $producto->stockBajo ? 'bg-amber-100 text-amber-700' : 'bg-salvia text-campo' }}">
                @if ($producto->stockBajo)
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                @else
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3.75 3.75 7.5m16.5 0L12 11.25M20.25 7.5v9L12 20.25m0-9L3.75 7.5m8.25 3.75v9M3.75 7.5v9L12 20.25" /></svg>
                @endif
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm text-piedra">{{ $producto->nombre }}</p>
                @can('gestionarCatalogo', App\Infrastructure\Inventario\Models\ProductoModel::class)
                    <a href="{{ route('inventario.catalogo.editar', $producto->id) }}" wire:navigate
                       class="text-xs text-campo hover:underline shrink-0">Editar</a>
                @endcan
            </div>
            @if ($producto->categoria)
                <p class="text-xs text-piedra">{{ $producto->categoria }}</p>
            @endif
            <p class="text-2xl font-semibold text-bosque mt-0.5">
                {{ number_format($producto->stock, 2) }}
                <span class="text-sm font-normal text-piedra">{{ $producto->unidad }}</span>
            </p>
            <p class="text-xs mt-1 {{ $producto->stockBajo ? 'text-amber-700 font-medium' : 'text-piedra' }}">
                @if ($producto->stockBajo)
                    Por agotarse (mínimo {{ number_format($producto->stockMinimo, 2) }})
                @else
                    Referencia S/ {{ number_format($producto->precioReferencia, 2) }}
                @endif
                @unless ($producto->activo) &middot; dado de baja @endunless
            </p>
        </div>
    </div>
</div>
