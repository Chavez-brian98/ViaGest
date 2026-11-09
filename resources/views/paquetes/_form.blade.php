@php
    $esEdicion = $paquete->exists;
    $estadoSeleccionado = old('estado', $paquete->estado ?? 'borrador');
    $tipoSeleccionado = old('tipo', $paquete->tipo ?? 'nacional');
    $margenTipoSeleccionado = old('margen_tipo', $paquete->margen_tipo ?? 'porcentaje');
    $destinoSeleccionado = (string) old('ciudad_destino_id', $paquete->ciudad_destino_id ?? '');

    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('paquetes.update', $paquete->id) : route('paquetes.store') }}"
    class="space-y-5"
    id="formPaquete"
    data-url-buscar-servicios="{{ route('paquetes.buscar-servicios') }}"
>
    @csrf
    @if ($esEdicion)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
            <p class="font-semibold">Revisa los datos del formulario.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Datos Generales --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Datos generales</h2>
                <p class="text-xs text-ink/45">Identificación, tipo y destino del paquete turístico.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <label for="estado" class="text-xs font-medium text-ink/60">Estado {!! $requerido !!}</label>
                <select
                    id="estado"
                    name="estado"
                    class="rounded-xl border border-ink/15 bg-background px-3 py-1.5 text-xs font-semibold text-ink transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                >
                    <option value="borrador" @selected($estadoSeleccionado === 'borrador')>Borrador</option>
                    <option value="activo" @selected($estadoSeleccionado === 'activo')>Activo</option>
                    <option value="inactivo" @selected($estadoSeleccionado === 'inactivo')>Inactivo</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="codigo" class="{{ $labelBase }}">Código {!! $requerido !!}</label>
                <input
                    id="codigo"
                    name="codigo"
                    type="text"
                    value="{{ old('codigo', $paquete->codigo) }}"
                    maxlength="30"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} uppercase {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
                >
                @error('codigo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="nombre" class="{{ $labelBase }}">Nombre del paquete {!! $requerido !!}</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    value="{{ old('nombre', $paquete->nombre) }}"
                    maxlength="150"
                    required
                    placeholder="Ej. Escapada Mágica a Roatán 4D/3N"
                    class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}"
                >
                @error('nombre')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tipo" class="{{ $labelBase }}">Tipo {!! $requerido !!}</label>
                <select
                    id="tipo"
                    name="tipo"
                    required
                    class="{{ $inputBase }} {{ $errors->has('tipo') ? $inputError : $inputNormal }}"
                >
                    <option value="nacional" @selected($tipoSeleccionado === 'nacional')>Nacional</option>
                    <option value="internacional" @selected($tipoSeleccionado === 'internacional')>Internacional</option>
                </select>
                @error('tipo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="ciudad_destino_id" class="{{ $labelBase }}">Ciudad de destino</label>
                <select
                    id="ciudad_destino_id"
                    name="ciudad_destino_id"
                    class="{{ $inputBase }} {{ $errors->has('ciudad_destino_id') ? $inputError : $inputNormal }}"
                >
                    <option value="">Sin destino específico</option>
                    @foreach ($ciudades as $c)
                        <option value="{{ $c->id }}" @selected($destinoSeleccionado == $c->id)>
                            {{ $c->nombre }} ({{ $c->pais_codigo }})
                        </option>
                    @endforeach
                </select>
                @error('ciudad_destino_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duracion_dias" class="{{ $labelBase }}">Duración (días) {!! $requerido !!}</label>
                <input
                    id="duracion_dias"
                    name="duracion_dias"
                    type="number"
                    min="1"
                    max="365"
                    value="{{ old('duracion_dias', $paquete->duracion_dias) }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('duracion_dias') ? $inputError : $inputNormal }}"
                >
                @error('duracion_dias')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duracion_noches" class="{{ $labelBase }}">Duración (noches) {!! $requerido !!}</label>
                <input
                    id="duracion_noches"
                    name="duracion_noches"
                    type="number"
                    min="0"
                    max="365"
                    value="{{ old('duracion_noches', $paquete->duracion_noches) }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('duracion_noches') ? $inputError : $inputNormal }}"
                >
                @error('duracion_noches')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2 lg:col-span-4">
                <label for="descripcion" class="{{ $labelBase }}">Descripción e itinerario base</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="3"
                    placeholder="Resumen del itinerario, qué incluye y observaciones comerciales..."
                    class="{{ $inputBase }} {{ $errors->has('descripcion') ? $inputError : $inputNormal }}"
                >{{ old('descripcion', $paquete->descripcion) }}</textarea>
                @error('descripcion')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Servicios Turísticos Incluidos (Composición) --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Servicios incluidos en el paquete</h2>
                <p class="text-xs text-ink/45">Busca en tiempo real por categoría y añade servicios a este paquete.</p>
            </div>
            <button
                type="button"
                id="btnAgregarServicio"
                class="inline-flex items-center gap-1.5 rounded-xl border border-secondary/20 bg-secondary/10 px-3.5 py-1.5 text-xs font-semibold text-secondary transition hover:bg-secondary/20 focus:outline-none focus:ring-4 focus:ring-secondary/20"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Añadir línea de servicio</span>
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-ink/10">
            <table class="w-full min-w-[42rem] text-sm" id="tablaServiciosPaquete">
                <thead class="bg-background">
                    <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
                        <th class="px-4 py-2.5">Servicio turístico</th>
                        <th class="w-28 px-4 py-2.5 text-center">Día inicio</th>
                        <th class="w-28 px-4 py-2.5 text-center">Cantidad</th>
                        <th class="w-32 px-4 py-2.5 text-right">Costo unitario</th>
                        <th class="w-32 px-4 py-2.5 text-right">Subtotal</th>
                        <th class="w-16 px-4 py-2.5 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5" id="contenedorFilasServicios">
                    @forelse ($serviciosAsignados as $indice => $item)
                        @php
                            $itemId = is_array($item) ? ($item['servicio_id'] ?? null) : ($item->servicio_id ?? null);
                            $itemDia = is_array($item) ? ($item['dia_inicio'] ?? 1) : ($item->dia_inicio ?? 1);
                            $itemCant = is_array($item) ? ($item['cantidad'] ?? 1) : ($item->cantidad ?? 1);
                            $servicioObj = $serviciosDisponibles->firstWhere('id', (int)$itemId);
                            $costoNeto = $servicioObj ? (float)$servicioObj->costo_neto : (is_object($item) ? (float)($item->costo_neto ?? 0) : 0);
                            $nombreMostrar = $servicioObj
                                ? "[{$servicioObj->codigo}] {$servicioObj->nombre} (" . ucfirst($servicioObj->tipo) . ')'
                                : (is_object($item) ? "[{$item->servicio_codigo}] {$item->servicio_nombre} (" . ucfirst($item->servicio_tipo) . ')' : '');
                        @endphp
                        <tr class="fila-servicio transition-colors hover:bg-background/50" data-indice="{{ $indice }}">
                            <td class="px-4 py-2.5">
                                <div class="relative autocomplete-wrapper">
                                    <input
                                        type="hidden"
                                        name="servicios[{{ $indice }}][servicio_id]"
                                        value="{{ $itemId }}"
                                        class="input-servicio-id"
                                        required
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            autocomplete="off"
                                            value="{{ $nombreMostrar }}"
                                            placeholder="Escribe para buscar por código, nombre o categoría..."
                                            class="input-buscar-servicio block w-full rounded-lg border border-ink/15 bg-background py-1.5 pl-3 pr-8 text-xs text-ink placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none"
                                        >
                                        <button
                                            type="button"
                                            class="btn-limpiar-seleccion absolute right-2 top-1/2 -translate-y-1/2 text-ink/30 hover:text-ink/70 {{ $itemId ? '' : 'hidden' }}"
                                            title="Cambiar servicio"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="dropdown-resultados absolute left-0 right-0 z-30 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-ink/15 bg-surface py-1 shadow-lg"></div>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <input
                                    type="number"
                                    min="1"
                                    name="servicios[{{ $indice }}][dia_inicio]"
                                    value="{{ $itemDia }}"
                                    required
                                    class="w-20 rounded-lg border border-ink/15 bg-background px-2 py-1 text-center text-xs text-ink focus:border-secondary focus:bg-surface focus:outline-none"
                                >
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <input
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    name="servicios[{{ $indice }}][cantidad]"
                                    value="{{ $itemCant }}"
                                    required
                                    class="input-cantidad w-20 rounded-lg border border-ink/15 bg-background px-2 py-1 text-center text-xs text-ink focus:border-secondary focus:bg-surface focus:outline-none"
                                >
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs text-ink/60 cell-costo" data-costo="{{ $costoNeto }}">
                                ${{ number_format($costoNeto, 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold text-ink cell-subtotal">
                                ${{ number_format($costoNeto * (float)$itemCant, 2) }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <button
                                    type="button"
                                    class="btn-quitar-fila inline-flex h-7 w-7 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700"
                                    title="Quitar línea"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        {{-- Inicializa vacío si no hay servicios asignados --}}
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Costeo y Margen de Utilidad --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-ink mb-1">Estructura de precios y margen</h2>
        <p class="text-xs text-ink/45 mb-4">Cálculo del precio final de venta sugerido al público.</p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="margen_tipo" class="{{ $labelBase }}">Tipo de margen {!! $requerido !!}</label>
                <select
                    id="margen_tipo"
                    name="margen_tipo"
                    required
                    class="{{ $inputBase }} {{ $errors->has('margen_tipo') ? $inputError : $inputNormal }}"
                >
                    <option value="porcentaje" @selected($margenTipoSeleccionado === 'porcentaje')>Porcentaje (%)</option>
                    <option value="monto_fijo" @selected($margenTipoSeleccionado === 'monto_fijo')>Monto fijo ($)</option>
                </select>
                @error('margen_tipo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="margen_valor" class="{{ $labelBase }}">Valor de margen {!! $requerido !!}</label>
                <input
                    id="margen_valor"
                    name="margen_valor"
                    type="number"
                    step="0.01"
                    min="0"
                    value="{{ old('margen_valor', $paquete->margen_valor) }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('margen_valor') ? $inputError : $inputNormal }}"
                >
                @error('margen_valor')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Resumen de Costeo Dinámico --}}
            <div class="rounded-xl border border-ink/10 bg-background/50 p-3">
                <span class="text-xs text-ink/45">Costo neto total</span>
                <div class="text-base font-semibold text-ink" id="resumenCostoNeto">$0.00</div>
                <span class="text-[10px] text-ink/40">Suma de servicios asignados</span>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                <span class="text-xs text-emerald-800">Precio venta calculado</span>
                <div class="text-lg font-bold text-emerald-700" id="resumenPrecioVenta">$0.00</div>
                <span class="text-[10px] text-emerald-600/70" id="resumenUtilidad">+ Utilidad: $0.00</span>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
        <a
            href="{{ route('paquetes.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 bg-surface px-5 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background hover:text-ink focus:outline-none focus:ring-4 focus:ring-ink/10"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/20"
        >
            {{ $esEdicion ? 'Guardar cambios' : 'Registrar paquete' }}
        </button>
    </div>
</form>

{{-- Plantilla oculta para nuevas filas de servicio --}}
<template id="tplFilaServicio">
    <tr class="fila-servicio transition-colors hover:bg-background/50" data-indice="__INDEX__">
        <td class="px-4 py-2.5">
            <div class="relative autocomplete-wrapper">
                <input
                    type="hidden"
                    name="servicios[__INDEX__][servicio_id]"
                    value=""
                    class="input-servicio-id"
                    required
                >
                <div class="relative">
                    <input
                        type="text"
                        autocomplete="off"
                        value=""
                        placeholder="Escribe para buscar por código, nombre o categoría..."
                        class="input-buscar-servicio block w-full rounded-lg border border-ink/15 bg-background py-1.5 pl-3 pr-8 text-xs text-ink placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none"
                    >
                    <button
                        type="button"
                        class="btn-limpiar-seleccion absolute right-2 top-1/2 -translate-y-1/2 text-ink/30 hover:text-ink/70 hidden"
                        title="Cambiar servicio"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
                <div class="dropdown-resultados absolute left-0 right-0 z-30 mt-1 hidden max-h-60 overflow-y-auto rounded-xl border border-ink/15 bg-surface py-1 shadow-lg"></div>
            </div>
        </td>
        <td class="px-4 py-2.5 text-center">
            <input
                type="number"
                min="1"
                name="servicios[__INDEX__][dia_inicio]"
                value="1"
                required
                class="w-20 rounded-lg border border-ink/15 bg-background px-2 py-1 text-center text-xs text-ink focus:border-secondary focus:bg-surface focus:outline-none"
            >
        </td>
        <td class="px-4 py-2.5 text-center">
            <input
                type="number"
                min="0.01"
                step="0.01"
                name="servicios[__INDEX__][cantidad]"
                value="1"
                required
                class="input-cantidad w-20 rounded-lg border border-ink/15 bg-background px-2 py-1 text-center text-xs text-ink focus:border-secondary focus:bg-surface focus:outline-none"
            >
        </td>
        <td class="px-4 py-2.5 text-right font-mono text-xs text-ink/60 cell-costo" data-costo="0">
            $0.00
        </td>
        <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold text-ink cell-subtotal">
            $0.00
        </td>
        <td class="px-4 py-2.5 text-center">
            <button
                type="button"
                class="btn-quitar-fila inline-flex h-7 w-7 items-center justify-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700"
                title="Quitar línea"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </td>
    </tr>
</template>
