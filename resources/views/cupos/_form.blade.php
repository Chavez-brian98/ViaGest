@php
    $esEdicion = $cupo->exists;
    $estadoSeleccionado = old('estado', $cupo->estado ?? 'activo');
    $tipoSeleccionado = old('tipo', $cupo->tipo ?? 'bloqueo');
    $servicioSeleccionado = (string) old('servicio_id', $cupo->servicio_id ?? '');

    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('cupos.update', $cupo->id) : route('cupos.store') }}"
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
                <h2 class="text-base font-semibold text-ink">Información del cupo</h2>
                <p class="text-xs text-ink/45">Servicio asignado, tipo y disponibilidad de inventario.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <label for="estado" class="text-xs font-medium text-ink/60">Estado {!! $requerido !!}</label>
                <select
                    id="estado"
                    name="estado"
                    class="rounded-xl border border-ink/15 bg-background px-3 py-1.5 text-xs font-semibold text-ink transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                >
                    <option value="activo" @selected($estadoSeleccionado === 'activo')>Activo</option>
                    <option value="liberado" @selected($estadoSeleccionado === 'liberado')>Liberado</option>
                    <option value="cerrado" @selected($estadoSeleccionado === 'cerrado')>Cerrado</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="servicio_id" class="{{ $labelBase }}">Servicio del catálogo {!! $requerido !!}</label>
                <select
                    id="servicio_id"
                    name="servicio_id"
                    required
                    class="{{ $inputBase }} {{ $errors->has('servicio_id') ? $inputError : $inputNormal }}"
                >
                    <option value="">Selecciona un servicio</option>
                    @foreach ($servicios as $s)
                        <option value="{{ $s->id }}" @selected($servicioSeleccionado == $s->id)>
                            [{{ $s->codigo }}] {{ $s->nombre }} - {{ $s->proveedor_nombre ?? 'Sin proveedor' }} ({{ ucfirst($s->tipo) }})
                        </option>
                    @endforeach
                </select>
                @error('servicio_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="{{ $ayuda }}">Servicio al que se asignarán las plazas o cupos disponibles.</p>
            </div>

            <div>
                <label for="tipo" class="{{ $labelBase }}">Tipo de cupo {!! $requerido !!}</label>
                <select
                    id="tipo"
                    name="tipo"
                    required
                    class="{{ $inputBase }} {{ $errors->has('tipo') ? $inputError : $inputNormal }}"
                >
                    <option value="bloqueo" @selected($tipoSeleccionado === 'bloqueo')>Bloqueo (reserva previa con proveedor)</option>
                    <option value="compra_adelantada" @selected($tipoSeleccionado === 'compra_adelantada')>Compra adelantada (inventario propio)</option>
                </select>
                @error('tipo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cantidad_total" class="{{ $labelBase }}">Cantidad total de plazas {!! $requerido !!}</label>
                <input
                    id="cantidad_total"
                    name="cantidad_total"
                    type="number"
                    min="1"
                    step="1"
                    value="{{ old('cantidad_total', $cupo->cantidad_total) }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('cantidad_total') ? $inputError : $inputNormal }}"
                >
                @error('cantidad_total')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="{{ $ayuda }}">Capacidad total adquirida o bloqueada con el proveedor.</p>
            </div>

            <div>
                <label for="fecha_inicio" class="{{ $labelBase }}">Fecha de inicio {!! $requerido !!}</label>
                <input
                    id="fecha_inicio"
                    name="fecha_inicio"
                    type="date"
                    value="{{ old('fecha_inicio', $cupo->fecha_inicio ? \Carbon\Carbon::parse($cupo->fecha_inicio)->format('Y-m-d') : '') }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('fecha_inicio') ? $inputError : $inputNormal }}"
                >
                @error('fecha_inicio')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="{{ $ayuda }}">Inicio de la vigencia o fecha del servicio.</p>
            </div>

            <div>
                <label for="fecha_fin" class="{{ $labelBase }}">Fecha de fin {!! $requerido !!}</label>
                <input
                    id="fecha_fin"
                    name="fecha_fin"
                    type="date"
                    value="{{ old('fecha_fin', $cupo->fecha_fin ? \Carbon\Carbon::parse($cupo->fecha_fin)->format('Y-m-d') : '') }}"
                    required
                    class="{{ $inputBase }} {{ $errors->has('fecha_fin') ? $inputError : $inputNormal }}"
                >
                @error('fecha_fin')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="{{ $ayuda }}">Fin de vigencia del cupo.</p>
            </div>

            <div>
                <label for="fecha_liberacion" class="{{ $labelBase }}">Fecha límite de liberación</label>
                <input
                    id="fecha_liberacion"
                    name="fecha_liberacion"
                    type="date"
                    value="{{ old('fecha_liberacion', $cupo->fecha_liberacion ? \Carbon\Carbon::parse($cupo->fecha_liberacion)->format('Y-m-d') : '') }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_liberacion') ? $inputError : $inputNormal }}"
                >
                @error('fecha_liberacion')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="{{ $ayuda }}">Fecha tope para devolver los espacios sin penalidad al proveedor (si aplica).</p>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
        <a
            href="{{ route('cupos.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 bg-surface px-5 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background hover:text-ink focus:outline-none focus:ring-4 focus:ring-ink/10"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/20"
        >
            {{ $esEdicion ? 'Guardar cambios' : 'Registrar cupo' }}
        </button>
    </div>
</form>
