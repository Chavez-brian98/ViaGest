@php
    $esEdicion = ! empty($prospecto->id);
    $estadoSeleccionado = old('estado', $prospecto->estado ?? 'nuevo');
    $origenSeleccionado = old('origen', $prospecto->origen ?? 'redes_sociales');
    $vendedorSeleccionado = old('vendedor_id', $prospecto->vendedor_id ?? auth()->id());
    $sucursalSeleccionada = old('sucursal_id', $prospecto->sucursal_id ?? auth()->user()?->sucursal_id);
    $motivoSeleccionado = old('motivo_perdida', $prospecto->motivo_perdida ?? '');

    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('prospectos.update', $prospecto->id) : route('prospectos.store') }}"
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

    {{-- Sección 1: Datos Personales y de Contacto --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-ink">Información del prospecto</h2>
                <p class="text-xs text-ink/45">Datos de contacto de la persona interesada.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-mono text-xs font-semibold px-2.5 py-1 bg-background rounded-lg border border-ink/10 text-ink/70">
                    {{ $prospecto->codigo }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="codigo" class="{{ $labelBase }}">Código {!! $requerido !!}</label>
                <input
                    id="codigo"
                    name="codigo"
                    type="text"
                    value="{{ old('codigo', $prospecto->codigo) }}"
                    maxlength="20"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Identificador único comercial.</p>
                @error('codigo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nombres" class="{{ $labelBase }}">Nombres {!! $requerido !!}</label>
                <input
                    id="nombres"
                    name="nombres"
                    type="text"
                    value="{{ old('nombres', $prospecto->nombres) }}"
                    maxlength="100"
                    required
                    autocomplete="given-name"
                    placeholder="Ej. Carlos Eduardo"
                    class="{{ $inputBase }} {{ $errors->has('nombres') ? $inputError : $inputNormal }}"
                >
                @error('nombres')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="apellidos" class="{{ $labelBase }}">Apellidos</label>
                <input
                    id="apellidos"
                    name="apellidos"
                    type="text"
                    value="{{ old('apellidos', $prospecto->apellidos) }}"
                    maxlength="100"
                    autocomplete="family-name"
                    placeholder="Ej. Mendoza Morales"
                    class="{{ $inputBase }} {{ $errors->has('apellidos') ? $inputError : $inputNormal }}"
                >
                @error('apellidos')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="{{ $labelBase }}">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $prospecto->email) }}"
                    maxlength="150"
                    autocomplete="email"
                    placeholder="cliente@ejemplo.com"
                    class="{{ $inputBase }} {{ $errors->has('email') ? $inputError : $inputNormal }}"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="telefono" class="{{ $labelBase }}">Teléfono / Celular / WhatsApp</label>
                <input
                    id="telefono"
                    name="telefono"
                    type="text"
                    value="{{ old('telefono', $prospecto->telefono) }}"
                    maxlength="25"
                    autocomplete="tel"
                    placeholder="Ej. +503 7123-4567"
                    class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                >
                @error('telefono')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="origen" class="{{ $labelBase }}">Canal de captación {!! $requerido !!}</label>
                <select
                    id="origen"
                    name="origen"
                    required
                    class="{{ $inputBase }} {{ $errors->has('origen') ? $inputError : $inputNormal }}"
                >
                    @foreach ($origenes as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($origenSeleccionado === $clave)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('origen')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Sección 2: Preferencias de Viaje e Interés Comercial --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Interés de viaje</h2>
            <p class="text-xs text-ink/45">Detalles del destino que busca el cliente y fechas tentativas.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="pais_destino" class="{{ $labelBase }}">Destino de interés (ISO 2 letras)</label>
                <input
                    id="pais_destino"
                    name="pais_destino"
                    type="text"
                    value="{{ old('pais_destino', $prospecto->pais_destino) }}"
                    maxlength="2"
                    pattern="[A-Za-z]{2}"
                    placeholder="ES, MX, US, CO, etc."
                    class="{{ $inputBase }} uppercase {{ $errors->has('pais_destino') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Código del país (ej. ES para España, FR para Francia).</p>
                @error('pais_destino')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="numero_viajeros" class="{{ $labelBase }}">Número de personas / viajeros</label>
                <input
                    id="numero_viajeros"
                    name="numero_viajeros"
                    type="number"
                    min="1"
                    max="500"
                    value="{{ old('numero_viajeros', $prospecto->numero_viajeros ?? 1) }}"
                    class="{{ $inputBase }} {{ $errors->has('numero_viajeros') ? $inputError : $inputNormal }}"
                >
                @error('numero_viajeros')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_viaje_estimada" class="{{ $labelBase }}">Fecha de viaje estimada</label>
                <input
                    id="fecha_viaje_estimada"
                    name="fecha_viaje_estimada"
                    type="date"
                    value="{{ old('fecha_viaje_estimada', $prospecto->fecha_viaje_estimada ? \Carbon\Carbon::parse($prospecto->fecha_viaje_estimada)->format('Y-m-d') : '') }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_viaje_estimada') ? $inputError : $inputNormal }}"
                >
                @error('fecha_viaje_estimada')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    {{-- Sección 3: Seguimiento y Asignación --}}
    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Estado y asignación de atención</h2>
            <p class="text-xs text-ink/45">Fase de la negociación, próximo contacto y vendedor a cargo.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="estado" class="{{ $labelBase }}">Estado comercial {!! $requerido !!}</label>
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

            <div id="contenedorMotivoPerdida" class="{{ $estadoSeleccionado === 'perdido' ? '' : 'hidden' }}">
                <label for="motivo_perdida" class="{{ $labelBase }}">Motivo de pérdida</label>
                <select
                    id="motivo_perdida"
                    name="motivo_perdida"
                    class="{{ $inputBase }} {{ $errors->has('motivo_perdida') ? $inputError : $inputNormal }}"
                >
                    <option value="">Selecciona un motivo...</option>
                    @foreach ($motivosPerdida as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($motivoSeleccionado === $clave)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('motivo_perdida')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="proximo_seguimiento" class="{{ $labelBase }}">Próximo seguimiento</label>
                <input
                    id="proximo_seguimiento"
                    name="proximo_seguimiento"
                    type="date"
                    value="{{ old('proximo_seguimiento', $prospecto->proximo_seguimiento ? \Carbon\Carbon::parse($prospecto->proximo_seguimiento)->format('Y-m-d') : '') }}"
                    class="{{ $inputBase }} {{ $errors->has('proximo_seguimiento') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Fecha de recordatorio de contacto.</p>
                @error('proximo_seguimiento')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="vendedor_id" class="{{ $labelBase }}">Vendedor asignado {!! $requerido !!}</label>
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

        <div class="mt-4">
            <label for="notas" class="{{ $labelBase }}">Notas / Observaciones del prospecto</label>
            <textarea
                id="notas"
                name="notas"
                rows="3"
                placeholder="Escribe comentarios sobre las expectativas del cliente, presupuesto aproximado, cotizaciones previas, etc."
                class="{{ $inputBase }} {{ $errors->has('notas') ? $inputError : $inputNormal }}"
            >{{ old('notas', $prospecto->notas) }}</textarea>
            @error('notas')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- Botones de Acción --}}
    <div class="flex items-center justify-end gap-3 pt-2">
        <a
            href="{{ route('prospectos.index') }}"
            class="rounded-xl border border-ink/15 bg-surface px-5 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
        >
            {{ $esEdicion ? 'Actualizar prospecto' : 'Guardar prospecto' }}
        </button>
    </div>
</form>
