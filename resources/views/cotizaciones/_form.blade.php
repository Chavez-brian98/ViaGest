@php
    $esEdicion = ! empty($cotizacion->id);
    $estadoSeleccionado = old('estado', $cotizacion->estado ?? 'borrador');
    $clienteSeleccionado = (string) old('cliente_id', $cotizacion->cliente_id ?? '');
    $prospectoSeleccionado = (string) old('prospecto_id', $cotizacion->prospecto_id ?? '');
    $paqueteSeleccionado = (string) old('paquete_id', $cotizacion->paquete_id ?? '');
    $vendedorSeleccionado = old('vendedor_id', $cotizacion->vendedor_id ?? auth()->id());
    $sucursalSeleccionada = old('sucursal_id', $cotizacion->sucursal_id ?? auth()->user()?->sucursal_id);

    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('cotizaciones.update', $cotizacion->id) : route('cotizaciones.store') }}"
    class="space-y-5"
    id="formCotizacion"
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

    {{-- Sección 1: Datos Generales y Destinatario --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Datos generales de la propuesta</h2>
                <p class="text-xs text-ink/45">Identificación, destinatario y validez de la cotización.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-semibold px-2.5 py-1 bg-background rounded-lg border border-ink/10 text-ink/70">
                    {{ $cotizacion->codigo }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="codigo" class="{{ $labelBase }}">Código {!! $requerido !!}</label>
                <input
                    id="codigo"
                    name="codigo"
                    type="text"
                    value="{{ old('codigo', $cotizacion->codigo) }}"
                    maxlength="20"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} uppercase {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Folio único comercial.</p>
                @error('codigo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="estado" class="{{ $labelBase }}">Estado {!! $requerido !!}</label>
                <select
                    id="estado"
                    name="estado"
                    required
                    class="{{ $inputBase }} {{ $errors->has('estado') ? $inputError : $inputNormal }}"
                >
                    @foreach ($estados as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($estadoSeleccionado === $clave)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('estado')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_emision" class="{{ $labelBase }}">Fecha de emisión {!! $requerido !!}</label>
                <input
                    id="fecha_emision"
                    name="fecha_emision"
                    type="date"
                    required
                    value="{{ old('fecha_emision', $cotizacion->fecha_emision ? \Carbon\Carbon::parse($cotizacion->fecha_emision)->format('Y-m-d') : now()->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_emision') ? $inputError : $inputNormal }}"
                >
                @error('fecha_emision')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_caducidad" class="{{ $labelBase }}">Fecha de caducidad {!! $requerido !!}</label>
                <input
                    id="fecha_caducidad"
                    name="fecha_caducidad"
                    type="date"
                    required
                    value="{{ old('fecha_caducidad', $cotizacion->fecha_caducidad ? \Carbon\Carbon::parse($cotizacion->fecha_caducidad)->format('Y-m-d') : now()->addDays(15)->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_caducidad') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Límite de validez del precio.</p>
                @error('fecha_caducidad')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="cliente_id" class="{{ $labelBase }}">Cliente existente (opcional)</label>
                <select
                    id="cliente_id"
                    name="cliente_id"
                    class="{{ $inputBase }} {{ $errors->has('cliente_id') ? $inputError : $inputNormal }}"
                >
                    <option value="">Selecciona un cliente o deja vacío para prospecto...</option>
                    @foreach ($clientes as $cli)
                        @php
                            $nombreCli = $cli->razon_social ?: trim($cli->nombres . ' ' . $cli->apellidos);
                        @endphp
                        <option value="{{ $cli->id }}" @selected($clienteSeleccionado === (string) $cli->id)>
                            {{ $cli->codigo }} — {{ $nombreCli }}
                        </option>
                    @endforeach
                </select>
                <p class="{{ $ayuda }}">Si el cliente ya está registrado en el directorio.</p>
                @error('cliente_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="prospecto_id" class="{{ $labelBase }}">O Prospecto / Lead (opcional)</label>
                <select
                    id="prospecto_id"
                    name="prospecto_id"
                    class="{{ $inputBase }} {{ $errors->has('prospecto_id') ? $inputError : $inputNormal }}"
                >
                    <option value="">Selecciona un prospecto si aún no es cliente...</option>
                    @foreach ($prospectos as $pro)
                        @php
                            $nombrePro = trim($pro->nombres . ' ' . ($pro->apellidos ?? ''));
                        @endphp
                        <option value="{{ $pro->id }}" @selected($prospectoSeleccionado === (string) $pro->id)>
                            {{ $pro->codigo }} — {{ $nombrePro }}
                        </option>
                    @endforeach
                </select>
                <p class="{{ $ayuda }}">Aplica para cotizaciones en fase de captación.</p>
                @error('prospecto_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Sección 2: Paquete Base y Fechas de Viaje --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Itinerario y paquete base</h2>
            <p class="text-xs text-ink/45">Puedes asociar un paquete del catálogo o armar una cotización a medida.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="paquete_id" class="{{ $labelBase }}">Paquete del catálogo</label>
                <select
                    id="paquete_id"
                    name="paquete_id"
                    class="{{ $inputBase }} {{ $errors->has('paquete_id') ? $inputError : $inputNormal }}"
                >
                    <option value="">Sin paquete (Servicios personalizados / a la medida)</option>
                    @foreach ($paquetes as $paq)
                        <option value="{{ $paq->id }}" @selected($paqueteSeleccionado === (string) $paq->id)>
                            {{ $paq->codigo }} — {{ $paq->nombre }}
                        </option>
                    @endforeach
                </select>
                <p class="{{ $ayuda }}">Al seleccionar un paquete puedes importar sus servicios automáticamente.</p>
                @error('paquete_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="numero_viajeros" class="{{ $labelBase }}">Número de personas (Pax) {!! $requerido !!}</label>
                <input
                    id="numero_viajeros"
                    name="numero_viajeros"
                    type="number"
                    min="1"
                    max="500"
                    required
                    value="{{ old('numero_viajeros', $cotizacion->numero_viajeros ?? 1) }}"
                    class="{{ $inputBase }} {{ $errors->has('numero_viajeros') ? $inputError : $inputNormal }}"
                >
                @error('numero_viajeros')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="descuento_monto" class="{{ $labelBase }}">Descuento global ($)</label>
                <input
                    id="descuento_monto"
                    name="descuento_monto"
                    type="number"
                    step="0.01"
                    min="0"
                    value="{{ old('descuento_monto', $cotizacion->descuento_monto ?? '0.00') }}"
                    class="{{ $inputBase }} {{ $errors->has('descuento_monto') ? $inputError : $inputNormal }}"
                >
                @error('descuento_monto')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_viaje_inicio" class="{{ $labelBase }}">Fecha tentativa inicio</label>
                <input
                    id="fecha_viaje_inicio"
                    name="fecha_viaje_inicio"
                    type="date"
                    value="{{ old('fecha_viaje_inicio', $cotizacion->fecha_viaje_inicio ? \Carbon\Carbon::parse($cotizacion->fecha_viaje_inicio)->format('Y-m-d') : '') }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_viaje_inicio') ? $inputError : $inputNormal }}"
                >
                @error('fecha_viaje_inicio')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_viaje_fin" class="{{ $labelBase }}">Fecha tentativa fin</label>
                <input
                    id="fecha_viaje_fin"
                    name="fecha_viaje_fin"
                    type="date"
                    value="{{ old('fecha_viaje_fin', $cotizacion->fecha_viaje_fin ? \Carbon\Carbon::parse($cotizacion->fecha_viaje_fin)->format('Y-m-d') : '') }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_viaje_fin') ? $inputError : $inputNormal }}"
                >
                @error('fecha_viaje_fin')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vendedor_id" class="{{ $labelBase }}">Vendedor {!! $requerido !!}</label>
                <select
                    id="vendedor_id"
                    name="vendedor_id"
                    required
                    class="{{ $inputBase }} {{ $errors->has('vendedor_id') ? $inputError : $inputNormal }}"
                >
                    @foreach ($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}" @selected((string) $vendedorSeleccionado === (string) $vendedor->id)>
                            {{ $vendedor->nombres }} {{ $vendedor->apellidos }}
                        </option>
                    @endforeach
                </select>
                @error('vendedor_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sucursal_id" class="{{ $labelBase }}">Sucursal {!! $requerido !!}</label>
                <select
                    id="sucursal_id"
                    name="sucursal_id"
                    required
                    class="{{ $inputBase }} {{ $errors->has('sucursal_id') ? $inputError : $inputNormal }}"
                >
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected((string) $sucursalSeleccionada === (string) $sucursal->id)>
                            {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('sucursal_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Sección 3: Servicios incluidos en la Cotización --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Servicios cotizados</h2>
                <p class="text-xs text-ink/45">Agrega vuelos, hoteles, tours o traslados con sus precios de venta.</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    id="btnImportarPaquete"
                    class="rounded-xl border border-secondary/30 bg-secondary/10 px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-secondary/20"
                >
                    Importar servicios del paquete
                </button>
            </div>
        </div>

        {{-- Selector rápido de servicio --}}
        <div class="flex flex-col sm:flex-row items-center gap-2 mb-4 bg-background/60 p-3 rounded-xl border border-ink/10">
            <div class="flex-1 w-full">
                <select id="selectServicioAgregar" class="w-full text-xs rounded-xl border border-ink/15 bg-surface px-3 py-2 text-ink">
                    <option value="">Selecciona un servicio para agregar a la cotización...</option>
                    @foreach ($serviciosDisponibles as $srv)
                        <option
                            value="{{ $srv->id }}"
                            data-codigo="{{ $srv->codigo }}"
                            data-nombre="{{ $srv->nombre }}"
                            data-tipo="{{ $srv->tipo }}"
                            data-costo="{{ $srv->costo_neto }}"
                            data-precio="{{ $srv->costo_neto }}"
                            data-proveedor="{{ $srv->proveedor_nombre }}"
                        >
                            [{{ strtoupper($srv->tipo) }}] {{ $srv->nombre }} — Costo: ${{ number_format($srv->costo_neto, 2) }} ({{ $srv->proveedor_nombre }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button
                type="button"
                id="btnAgregarServicio"
                class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-white shadow transition hover:bg-primary/90 shrink-0"
            >
                + Agregar servicio
            </button>
        </div>

        {{-- Tabla de servicios agregados --}}
        <div class="overflow-x-auto rounded-xl border border-ink/10">
            <table class="w-full text-left text-xs" id="tablaServiciosCotizacion">
                <thead class="bg-background border-b border-ink/10 text-ink/60 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-2.5">Servicio</th>
                        <th class="px-3 py-2.5 text-center w-24">Cantidad</th>
                        <th class="px-3 py-2.5 text-right w-28">Costo Neto ($)</th>
                        <th class="px-3 py-2.5 text-right w-28">Precio Venta ($)</th>
                        <th class="px-3 py-2.5 text-right w-28">Subtotal ($)</th>
                        <th class="px-3 py-2.5 text-center w-12">Quitar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5" id="tbodyServiciosCotizacion">
                    @php
                        $serviciosActuales = old('servicios', $cotizacion->servicios ?? []);
                    @endphp
                    @forelse ($serviciosActuales as $idx => $s)
                        @php
                            $s = (object) $s;
                            $cant = (float) ($s->cantidad ?? 1);
                            $pNeto = (float) ($s->costo_neto_unitario ?? 0);
                            $pVenta = (float) ($s->precio_unitario ?? 0);
                            $sub = $cant * $pVenta;
                        @endphp
                        <tr class="fila-servicio hover:bg-background/40" data-idx="{{ $idx }}">
                            <td class="px-4 py-2.5">
                                <input type="hidden" name="servicios[{{ $idx }}][servicio_id]" value="{{ $s->servicio_id }}">
                                <p class="font-medium text-ink">{{ $s->servicio_nombre ?? 'Servicio #' . $s->servicio_id }}</p>
                                <p class="text-[10px] text-ink/45 font-mono">{{ $s->servicio_codigo ?? '' }}</p>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <input
                                    type="number"
                                    name="servicios[{{ $idx }}][cantidad]"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ $cant }}"
                                    class="w-20 text-center rounded-lg border border-ink/15 bg-background p-1 text-xs input-cantidad"
                                >
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <input
                                    type="number"
                                    name="servicios[{{ $idx }}][costo_neto_unitario]"
                                    step="0.01"
                                    min="0"
                                    value="{{ $pNeto }}"
                                    class="w-24 text-right rounded-lg border border-ink/15 bg-background p-1 text-xs input-costo"
                                >
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <input
                                    type="number"
                                    name="servicios[{{ $idx }}][precio_unitario]"
                                    step="0.01"
                                    min="0"
                                    value="{{ $pVenta }}"
                                    class="w-24 text-right rounded-lg border border-ink/15 bg-background p-1 text-xs input-precio font-semibold text-primary"
                                >
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-ink span-subtotal">
                                ${{ number_format($sub, 2) }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <button type="button" class="text-ink/40 hover:text-rose-600 btn-eliminar-fila">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr id="filaSinServicios">
                            <td colspan="6" class="px-4 py-8 text-center text-xs text-ink/45">
                                No hay servicios agregados a la cotización todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Resumen de totales calculados en vivo --}}
        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 bg-background/50 p-4 rounded-xl border border-ink/10">
            <div class="text-xs text-ink/60 space-y-0.5">
                <p>Costo neto total estimado: <span id="resumenCostoNeto" class="font-mono font-medium">$0.00</span></p>
                <p>Utilidad bruta estimada: <span id="resumenUtilidad" class="font-mono font-medium text-emerald-600">$0.00</span></p>
            </div>
            <div class="text-right">
                <p class="text-xs text-ink/60">Subtotal: <span id="resumenSubtotal" class="font-mono font-medium">$0.00</span></p>
                <p class="text-lg font-bold text-ink">Total cotizado: <span id="resumenTotal" class="font-mono text-primary">$0.00</span></p>
            </div>
        </div>

        <div class="mt-4">
            <label for="notas" class="{{ $labelBase }}">Notas / Observaciones y condiciones comerciales</label>
            <textarea
                id="notas"
                name="notas"
                rows="3"
                placeholder="Incluye políticas de cancelación, requerimientos de pasaporte/visa, o notas para el cliente."
                class="{{ $inputBase }} {{ $errors->has('notas') ? $inputError : $inputNormal }}"
            >{{ old('notas', $cotizacion->notas) }}</textarea>
            @error('notas')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- Botones de Acción --}}
    <div class="flex items-center justify-end gap-3 pt-2">
        <a
            href="{{ route('cotizaciones.index') }}"
            class="rounded-xl border border-ink/15 bg-surface px-5 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
        >
            {{ $esEdicion ? 'Actualizar cotización' : 'Guardar cotización' }}
        </button>
    </div>
</form>
