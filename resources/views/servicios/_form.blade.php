@php
    $esEdicion = $servicio->exists;
    $estadoSeleccionado = old('estado', $servicio->estado ?? 'activo');
    $tipoSeleccionado = old('tipo', $servicio->tipo ?? 'actividad');
    $unidadSeleccionada = old('unidad_cobro', $servicio->unidad_cobro ?? 'por_persona');
    $proveedorSeleccionado = (string) old('proveedor_id', $servicio->proveedor_id ?? '');
    $origenSeleccionado = (string) old('ciudad_origen_id', $servicio->ciudad_origen_id ?? '');
    $destinoSeleccionado = (string) old('ciudad_destino_id', $servicio->ciudad_destino_id ?? '');
    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
    $tipos = [
        'vuelo' => 'Vuelo',
        'hospedaje' => 'Hospedaje',
        'traslado' => 'Traslado',
        'actividad' => 'Actividad',
        'guia' => 'Guía',
        'seguro' => 'Seguro',
        'otro' => 'Otro',
    ];
    $unidades = [
        'por_persona' => 'Por persona',
        'por_habitacion_noche' => 'Por habitación / noche',
        'por_vehiculo' => 'Por vehículo',
        'por_dia' => 'Por día',
        'por_grupo' => 'Por grupo',
        'por_unidad' => 'Por unidad',
    ];
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('servicios.update', $servicio->id) : route('servicios.store') }}"
    class="space-y-5"
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

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Datos generales</h2>
                <p class="text-xs text-ink/45">Identificación y clasificación del servicio.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-medium text-ink/45">Estado</span>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="estado" value="inactivo">
                    <input
                        id="servicioEstado"
                        type="checkbox"
                        name="estado"
                        value="activo"
                        role="switch"
                        class="peer sr-only"
                        @checked($estadoSeleccionado === 'activo')
                    >
                    <span class="estado-form-switch">
                        <span id="servicioEstadoTexto" class="estado-texto" data-estado-texto data-estado="{{ $estadoSeleccionado }}">
                            {{ $estadoSeleccionado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </span>
                        <span class="estado-switch-dot" aria-hidden="true"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="codigo" class="{{ $labelBase }}">Código {!! $requerido !!}</label>
                <input
                    id="codigo"
                    name="codigo"
                    type="text"
                    value="{{ old('codigo', $servicio->codigo) }}"
                    maxlength="30"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} uppercase {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
                >
                @error('codigo')
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
                    @foreach ($tipos as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($tipoSeleccionado === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('tipo')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="nombre" class="{{ $labelBase }}">Nombre {!! $requerido !!}</label>
                <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    value="{{ old('nombre', $servicio->nombre) }}"
                    maxlength="150"
                    required
                    placeholder="Ej. Vuelo San Salvador - Miami"
                    class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}"
                >
                @error('nombre')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <label for="proveedor_id" class="{{ $labelBase }}">Proveedor {!! $requerido !!}</label>
                <select
                    id="proveedor_id"
                    name="proveedor_id"
                    required
                    class="{{ $inputBase }} {{ $errors->has('proveedor_id') ? $inputError : $inputNormal }}"
                >
                    <option value="" @selected($proveedorSeleccionado === '')>Seleccione un proveedor</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected($proveedorSeleccionado === (string) $proveedor->id)>
                            {{ $proveedor->nombre }} ({{ $proveedor->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('proveedor_id')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2 lg:col-span-4">
                <label for="descripcion" class="{{ $labelBase }}">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="3"
                    maxlength="2000"
                    placeholder="Detalles, condiciones o inclusiones del servicio..."
                    class="{{ $inputBase }} {{ $errors->has('descripcion') ? $inputError : $inputNormal }}"
                >{{ old('descripcion', $servicio->descripcion) }}</textarea>
                @error('descripcion')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Cobertura y vigencia</h2>
            <p class="text-xs text-ink/45">Origen, destino y periodo en que el servicio está disponible.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="ciudad_origen_id" class="{{ $labelBase }}">Ciudad de origen</label>
                <select
                    id="ciudad_origen_id"
                    name="ciudad_origen_id"
                    class="{{ $inputBase }} {{ $errors->has('ciudad_origen_id') ? $inputError : $inputNormal }}"
                >
                    <option value="" @selected($origenSeleccionado === '')>Sin ciudad de origen</option>
                    @foreach ($ciudades as $ciudad)
                        <option value="{{ $ciudad->id }}" @selected($origenSeleccionado === (string) $ciudad->id)>
                            {{ $ciudad->nombre }} ({{ $ciudad->pais_codigo }})
                        </option>
                    @endforeach
                </select>
                @error('ciudad_origen_id')
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
                    <option value="" @selected($destinoSeleccionado === '')>Sin ciudad de destino</option>
                    @foreach ($ciudades as $ciudad)
                        <option value="{{ $ciudad->id }}" @selected($destinoSeleccionado === (string) $ciudad->id)>
                            {{ $ciudad->nombre }} ({{ $ciudad->pais_codigo }})
                        </option>
                    @endforeach
                </select>
                @error('ciudad_destino_id')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vigente_desde" class="{{ $labelBase }}">Vigente desde</label>
                <input
                    id="vigente_desde"
                    name="vigente_desde"
                    type="date"
                    value="{{ old('vigente_desde', $servicio->vigente_desde) }}"
                    class="{{ $inputBase }} {{ $errors->has('vigente_desde') ? $inputError : $inputNormal }}"
                >
                @error('vigente_desde')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vigente_hasta" class="{{ $labelBase }}">Vigente hasta</label>
                <input
                    id="vigente_hasta"
                    name="vigente_hasta"
                    type="date"
                    value="{{ old('vigente_hasta', $servicio->vigente_hasta) }}"
                    class="{{ $inputBase }} {{ $errors->has('vigente_hasta') ? $inputError : $inputNormal }}"
                >
                @error('vigente_hasta')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Costo</h2>
            <p class="text-xs text-ink/45">Costo neto que se paga al proveedor y unidad de cobro.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="unidad_cobro" class="{{ $labelBase }}">Unidad de cobro {!! $requerido !!}</label>
                <select
                    id="unidad_cobro"
                    name="unidad_cobro"
                    required
                    class="{{ $inputBase }} {{ $errors->has('unidad_cobro') ? $inputError : $inputNormal }}"
                >
                    @foreach ($unidades as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($unidadSeleccionada === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('unidad_cobro')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="costo_neto" class="{{ $labelBase }}">Costo neto {!! $requerido !!}</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-ink/40">$</span>
                    <input
                        id="costo_neto"
                        name="costo_neto"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('costo_neto', $servicio->costo_neto) }}"
                        required
                        placeholder="0.00"
                        class="{{ $inputBase }} pl-8 {{ $errors->has('costo_neto') ? $inputError : $inputNormal }}"
                    >
                </div>
                <p class="{{ $ayuda }}">Monto en dólares estadounidenses (USD).</p>
                @error('costo_neto')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a
            href="{{ route('servicios.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 px-4 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90"
        >
            {{ $esEdicion ? 'Actualizar servicio' : 'Guardar servicio' }}
        </button>
    </div>

    @push('scripts')
        <script>
            (() => {
                const estado = document.getElementById('servicioEstado');
                const estadoTexto = document.getElementById('servicioEstadoTexto');

                const sincronizarEstado = () => {
                    if (!estado || !estadoTexto) return;
                    estadoTexto.dataset.estado = estado.checked ? 'activo' : 'inactivo';
                    estadoTexto.textContent = estado.checked ? 'Activo' : 'Inactivo';
                };

                estado?.addEventListener('change', sincronizarEstado);
                sincronizarEstado();
            })();
        </script>
    @endpush
</form>
