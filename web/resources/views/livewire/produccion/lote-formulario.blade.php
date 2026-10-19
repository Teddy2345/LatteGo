<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-bosque">Nueva producción</h1>
            <p class="text-sm text-piedra mt-1">Planifica el lote y registra lo que realmente obtuviste.</p>
        </div>
        <a href="{{ route('produccion.index') }}" wire:navigate class="text-campo">← Volver a Producción</a>
    </div>

    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea p-6 space-y-6">
        @if($errors->any())
            <div role="alert" class="bg-red-50 text-red-700 p-3 rounded-lg">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div>
            <label for="lote-fecha" class="block text-sm text-bosque mb-1">Fecha de producción</label>
            <input id="lote-fecha" type="date" wire:model="fecha" max="{{ now()->format('Y-m-d') }}" required
                   class="w-full md:w-1/2 rounded-lg border-linea">
        </div>

        <section class="bg-crema rounded-xl p-4 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-bosque">1. Qué se va a producir</h2>
                <button type="button" wire:click="agregarProducto" class="text-campo text-sm">+ Agregar otro producto</button>
            </div>
            <p class="text-xs text-piedra">
                De una misma tanda pueden salir varios productos. Cada línea lleva su receta y su cantidad;
                los insumos de todas se suman abajo y se descuentan una sola vez.
            </p>

            @foreach($productos as $i => $linea)
                <div class="bg-white rounded-xl border border-linea p-4 space-y-3" wire:key="lote-producto-{{ $i }}">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-piedra uppercase tracking-wide">Producto {{ $i + 1 }}</span>
                        @if(count($productos) > 1)
                            <button type="button" wire:click="quitarProducto({{ $i }})" class="text-red-700 text-sm">Quitar</button>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="lote-receta-{{ $i }}" class="block text-sm text-bosque mb-1">Receta</label>
                            <select id="lote-receta-{{ $i }}" wire:model="productos.{{ $i }}.recetaId" class="w-full rounded-lg border-linea">
                                <option value="">Registro manual, sin receta</option>
                                @foreach($recetas as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['nombre'] }} — {{ $r['cantidad_base'] ? number_format($r['cantidad_base'], 2).' '.$r['unidad_salida'] : 'receta anterior' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="lote-producto-{{ $i }}" class="block text-sm text-bosque mb-1">Producto terminado</label>
                            <select id="lote-producto-{{ $i }}" wire:model.live="productos.{{ $i }}.productoId" required class="w-full rounded-lg border-linea">
                                <option value="">Selecciona el producto</option>
                                @foreach($productosCatalogo as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->unidad }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="lote-plan-{{ $i }}" class="block text-sm text-bosque mb-1">Cantidad planificada ({{ $unidades[$i] }})</label>
                            <input id="lote-plan-{{ $i }}" type="number" min="0.01" max="99999999.99" step="0.01"
                                   wire:model="productos.{{ $i }}.cantidadPlanificada" required class="w-full rounded-lg border-linea">
                        </div>
                        <div>
                            <label for="lote-real-{{ $i }}" class="block text-sm text-bosque mb-1">Cantidad realmente obtenida ({{ $unidades[$i] }})</label>
                            <input id="lote-real-{{ $i }}" type="number" min="0.01" max="99999999.99" step="0.01"
                                   wire:model="productos.{{ $i }}.cantidadProducida" required class="w-full rounded-lg border-linea">
                            <p class="text-xs text-piedra mt-1">Esta cantidad ingresará a Almacén.</p>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap gap-4">
                <button type="button" wire:click="aplicarRecetas" wire:loading.attr="disabled" class="bg-campo text-white rounded-lg px-4 py-2 text-sm">Aplicar recetas</button>
                <a href="{{ route('produccion.recetas') }}" wire:navigate class="text-campo text-sm py-2">Crear o consultar recetas</a>
            </div>
            <p class="text-xs text-piedra">Aplicar recetas selecciona el producto de cada una y calcula los insumos del lote. Si cambias una receta o una cantidad, vuelve a aplicarlas.</p>
        </section>

        <section class="space-y-4">
            <h2 class="font-semibold text-bosque">2. Leche e insumos consumidos</h2>

            <div>
                <label for="lote-leche" class="block text-sm text-bosque mb-1">Leche de acopio utilizada (L)</label>
                <input id="lote-leche" type="number" min="0" step="0.01" wire:model="litrosProcesados" required class="w-full md:w-1/2 rounded-lg border-linea">
                <p class="text-xs text-piedra mt-1">0 si no se utiliza. No repitas la leche que registres como insumo abajo.</p>
            </div>

            <div class="flex flex-wrap justify-between gap-2">
                <h3 class="font-medium text-bosque">Insumos utilizados</h3>
                <button type="button" wire:click="agregarInsumo" class="text-campo text-sm">+ Agregar insumo</button>
            </div>

            @forelse($insumos as $i => $fila)
                <div class="flex flex-wrap gap-2" wire:key="lote-insumo-{{ $i }}">
                    <select aria-label="Insumo {{ $i + 1 }}" wire:model="insumos.{{ $i }}.productoId" required class="rounded-lg border-linea flex-1 min-w-0">
                        <option value="">Selecciona insumo</option>
                        @foreach($catalogoInsumos as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }} — {{ number_format($p->stock, 2) }} {{ $p->unidad }} disponibles</option>
                        @endforeach
                    </select>
                    <input aria-label="Cantidad utilizada de insumo {{ $i + 1 }}" type="number" min="0.01" step="0.01" required wire:model="insumos.{{ $i }}.cantidad" class="w-28 rounded-lg border-linea">
                    <button type="button" wire:click="quitarInsumo({{ $i }})" class="text-red-700 text-sm">Quitar</button>
                </div>
            @empty
                <p class="text-sm text-piedra">Aplica las recetas o agrega los insumos del lote.</p>
            @endforelse

            <p class="text-xs text-piedra">Revisa las cantidades calculadas y ajústalas al consumo real. Se descontarán de Almacén; si falta stock, el lote no se guardará.</p>

            <div>
                <label for="lote-notas" class="block text-sm text-bosque mb-1">Observaciones del lote</label>
                <textarea id="lote-notas" wire:model="observaciones" maxlength="1000" rows="3" class="w-full rounded-lg border-linea"></textarea>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3 border-t border-linea pt-4">
            <a href="{{ route('produccion.index') }}" wire:navigate class="border border-linea px-4 py-2 rounded-lg text-bosque">Cancelar</a>
            <button type="submit" wire:loading.attr="disabled" class="bg-campo text-white px-4 py-2 rounded-lg">Guardar lote y actualizar Almacén</button>
        </div>
    </form>
</div>
