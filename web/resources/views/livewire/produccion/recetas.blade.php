<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-wrap justify-between gap-4">
        <div><h1 class="text-2xl font-semibold text-bosque">Recetas de producción</h1><p class="text-sm text-piedra mt-1">Una plantilla para cada producto: define cuánto obtienes y qué necesitas.</p></div>
        <a href="{{ route('produccion.index') }}" wire:navigate class="text-campo">← Volver a Producción</a>
    </div>
    @if(session('recetaCreada')) <p role="status" class="bg-salvia text-bosque rounded-lg p-4">{{ session('recetaCreada') }}</p> @endif
    <div class="bg-white rounded-2xl border border-linea p-5 space-y-3">
        <h2 class="font-semibold text-bosque">Recetas disponibles</h2>
        @forelse($recetas as $receta)
            <div class="border-t border-linea py-3" wire:key="receta-{{ $receta['id'] }}">
                <p class="font-medium text-bosque">{{ $receta['nombre'] }}</p>
                <p class="text-sm text-piedra">{{ $receta['producto'] }} · @if($receta['cantidad_base']) Obtiene {{ number_format($receta['cantidad_base'], 2) }} {{ $receta['unidad_salida'] }} @else Receta anterior: crea una nueva versión con cantidad de salida. @endif</p>
                <p class="text-sm text-piedra">{{ $receta['descripcion'] }}</p>
            </div>
        @empty <p class="text-sm text-piedra">Aún no hay recetas. Registra los productos e insumos en Almacén y crea tu primera receta aquí.</p> @endforelse
    </div>
    @can('create', App\Infrastructure\Produccion\Models\ProduccionModel::class)
    <form wire:submit="guardar" class="bg-white rounded-2xl border border-linea p-6 space-y-4">
        <h2 class="font-semibold text-bosque">Nueva receta</h2>
        <p class="text-sm text-piedra">Para cambiar una fórmula, crea otra receta con un nombre de versión. Las recetas utilizadas conservan su composición.</p>
        @if($errors->any()) <div role="alert" class="bg-red-50 text-red-700 p-3 rounded-lg"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <div><label for="receta-nombre" class="block text-sm text-bosque">Nombre</label><input id="receta-nombre" wire:model="nombre" required maxlength="120" class="w-full rounded-lg border-linea"></div>
        <div><label for="receta-producto" class="block text-sm text-bosque">Producto terminado</label><select id="receta-producto" wire:model.live="productoId" required class="w-full rounded-lg border-linea"><option value="">Selecciona un producto</option>@foreach($productos as $p)<option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->unidad }})</option>@endforeach</select></div>
        <div><label for="receta-cantidad" class="block text-sm text-bosque">Cantidad base obtenida ({{ $unidad }})</label><input id="receta-cantidad" type="number" min="0.01" max="99999999.99" step="0.01" wire:model="cantidadBase" required class="w-full rounded-lg border-linea"><p class="text-xs text-piedra mt-1">Ejemplo: 2 kg de queso o 10 litros de yogur. La unidad corresponde al producto seleccionado.</p></div>
        @can('gestionarCatalogo', App\Infrastructure\Inventario\Models\ProductoModel::class)
        <p class="text-sm text-piedra">¿Un producto nuevo? <a href="{{ route('inventario.catalogo.crear') }}" wire:navigate class="text-campo underline">Créalo en Almacén</a> como producto terminado, con su unidad, y vuelve a esta receta.</p>
        @else <p class="text-xs text-piedra">Si falta un producto o insumo, solicita su alta al responsable de Almacén.</p> @endcan
        <div><label for="receta-litros" class="block text-sm text-bosque">Leche de acopio para la receta (L, opcional)</label><input id="receta-litros" type="number" min="0" step="0.01" wire:model="litrosBase" required class="w-full rounded-lg border-linea"></div>
        <div><label for="receta-notas" class="block text-sm text-bosque">Instrucciones de elaboración</label><textarea id="receta-notas" wire:model="descripcion" maxlength="1000" class="w-full rounded-lg border-linea"></textarea></div>
        <p class="text-xs text-piedra">Indica 0 si no usas leche de acopio. Si la leche está en Almacén como insumo, inclúyela abajo y no la repitas arriba.</p><h3 class="font-medium text-bosque">Insumos para obtener la cantidad base</h3>
        @foreach($insumos as $i => $fila)
        <div class="flex flex-wrap gap-2" wire:key="receta-insumo-{{ $i }}">
            <select aria-label="Insumo {{ $i + 1 }}" wire:model="insumos.{{ $i }}.productoId" required class="rounded-lg border-linea flex-1"><option value="">Selecciona insumo</option>@foreach($catalogoInsumos as $p)<option value="{{ $p->id }}">{{ $p->nombre }} ({{ $p->unidad }})</option>@endforeach</select>
            <input aria-label="Cantidad de insumo {{ $i + 1 }}" type="number" min="0.01" step="0.01" required wire:model="insumos.{{ $i }}.cantidad" class="w-28 rounded-lg border-linea">
            <button type="button" wire:click="quitarInsumo({{ $i }})" class="text-red-700">Quitar</button>
        </div>
        @endforeach
        <button type="button" wire:click="agregarInsumo" class="text-campo">+ Agregar insumo</button>
        <p class="text-xs text-piedra">Utiliza la unidad indicada en el catálogo. Guarda cantidades con hasta dos decimales; para dosis pequeñas utiliza gramos o mililitros en el catálogo.</p>
        <div><button type="submit" wire:loading.attr="disabled" class="bg-campo text-white px-4 py-2 rounded-lg">Guardar receta</button></div>
    </form>
    @endcan
</div>
