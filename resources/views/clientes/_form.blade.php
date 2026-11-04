@php
    $esEdicion = $cliente->exists;
    $tipoSeleccionado = old('tipo', $cliente->tipo ?? 'persona');
    $documentoSeleccionado = old('tipo_documento', $cliente->tipo_documento);
    $estadoSeleccionado = old('estado', $cliente->estado ?? 'activo');
    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('clientes.update', $cliente) : route('clientes.store') }}"
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
                <p class="text-xs text-ink/45">Identificación y registro del cliente.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-medium text-ink/45">Estado</span>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="estado" value="inactivo">
                    <input
                        id="clienteEstado"
                        type="checkbox"
                        name="estado"
                        value="activo"
                        role="switch"
                        class="peer sr-only"
                        @checked($estadoSeleccionado === 'activo')
                    >
                    <span class="estado-form-switch">
                        <span id="clienteEstadoTexto" class="estado-texto" data-estado-texto data-estado="{{ $estadoSeleccionado }}">
                            {{ $estadoSeleccionado === 'activo' ? 'Activo' : 'Inactivo' }}
                        </span>
                        <span class="estado-switch-dot" aria-hidden="true"></span>
                    </span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="codigo" class="{{ $labelBase }}">Código {!! $requerido !!}</label>
                <input
                    id="codigo"
                    name="codigo"
                    type="text"
                    value="{{ old('codigo', $cliente->codigo) }}"
                    maxlength="20"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Se sugiere automáticamente; puedes cambiarlo.</p>
                @error('codigo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="clienteTipo" class="{{ $labelBase }}">Tipo de cliente {!! $requerido !!}</label>
                <select
                    id="clienteTipo"
                    name="tipo"
                    required
                    class="{{ $inputBase }} {{ $errors->has('tipo') ? $inputError : $inputNormal }}"
                >
                    <option value="persona" @selected($tipoSeleccionado === 'persona')>Persona</option>
                    <option value="empresa" @selected($tipoSeleccionado === 'empresa')>Empresa</option>
                </select>
                @error('tipo')
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
                    <option value="">Selecciona una sucursal</option>
                    @foreach ($sucursales as $sucursal)
                        <option
                            value="{{ $sucursal->id }}"
                            @selected((string) old('sucursal_id', $cliente->sucursal_id) === (string) $sucursal->id)
                        >
                            {{ $sucursal->nombre }} ({{ $sucursal->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('sucursal_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div id="camposPersona" class="grid grid-cols-1 gap-4 sm:col-span-2 sm:grid-cols-2">
                <div>
                    <label for="nombres" class="{{ $labelBase }}">Nombres {!! $requerido !!}</label>
                    <input
                        id="nombres"
                        name="nombres"
                        type="text"
                        value="{{ old('nombres', $cliente->nombres) }}"
                        maxlength="100"
                        autocomplete="given-name"
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
                        value="{{ old('apellidos', $cliente->apellidos) }}"
                        maxlength="100"
                        autocomplete="family-name"
                        class="{{ $inputBase }} {{ $errors->has('apellidos') ? $inputError : $inputNormal }}"
                    >
                    @error('apellidos')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div id="camposEmpresa" class="sm:col-span-2">
                <label for="razon_social" class="{{ $labelBase }}">Razón social {!! $requerido !!}</label>
                <input
                    id="razon_social"
                    name="razon_social"
                    type="text"
                    value="{{ old('razon_social', $cliente->razon_social) }}"
                    maxlength="150"
                    autocomplete="organization"
                    class="{{ $inputBase }} {{ $errors->has('razon_social') ? $inputError : $inputNormal }}"
                >
                @error('razon_social')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Documento y nacionalidad</h2>
            <p class="text-xs text-ink/45">Datos de identificación del cliente.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="tipo_documento" class="{{ $labelBase }}">Tipo de documento</label>
                <select
                    id="tipo_documento"
                    name="tipo_documento"
                    class="{{ $inputBase }} {{ $errors->has('tipo_documento') ? $inputError : $inputNormal }}"
                >
                    <option value="">Sin documento</option>
                    <option value="dui" @selected($documentoSeleccionado === 'dui')>DUI</option>
                    <option value="nit" @selected($documentoSeleccionado === 'nit')>NIT</option>
                    <option value="carnet_residente" @selected($documentoSeleccionado === 'carnet_residente')>Carnet de residente</option>
                    <option value="otro" @selected($documentoSeleccionado === 'otro')>Otro</option>
                </select>
                @error('tipo_documento')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="numero_documento" class="{{ $labelBase }}">Número de documento</label>
                <input
                    id="numero_documento"
                    name="numero_documento"
                    type="text"
                    value="{{ old('numero_documento', $cliente->numero_documento) }}"
                    maxlength="30"
                    autocomplete="off"
                    placeholder="00000000-0"
                    class="{{ $inputBase }} {{ $errors->has('numero_documento') ? $inputError : $inputNormal }}"
                >
                @error('numero_documento')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_nacimiento" class="{{ $labelBase }}">Fecha de nacimiento</label>
                <input
                    id="fecha_nacimiento"
                    name="fecha_nacimiento"
                    type="date"
                    value="{{ old('fecha_nacimiento', $cliente->fecha_nacimiento?->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_nacimiento') ? $inputError : $inputNormal }}"
                >
                @error('fecha_nacimiento')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nacionalidad_pais" class="{{ $labelBase }}">Nacionalidad</label>
                <input
                    id="nacionalidad_pais"
                    name="nacionalidad_pais"
                    type="text"
                    value="{{ old('nacionalidad_pais', $cliente->nacionalidad_pais) }}"
                    maxlength="2"
                    pattern="[A-Za-z]{2}"
                    placeholder="SV"
                    autocomplete="country"
                    class="{{ $inputBase }} uppercase {{ $errors->has('nacionalidad_pais') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Código ISO de dos letras.</p>
                @error('nacionalidad_pais')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Contacto</h2>
            <p class="text-xs text-ink/45">Canales para dar seguimiento al cliente.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="{{ $labelBase }}">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $cliente->email) }}"
                    maxlength="150"
                    autocomplete="email"
                    class="{{ $inputBase }} {{ $errors->has('email') ? $inputError : $inputNormal }}"
                >
                @error('email')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="telefono" class="{{ $labelBase }}">Teléfono</label>
                <input
                    id="telefono"
                    name="telefono"
                    type="text"
                    value="{{ old('telefono', $cliente->telefono) }}"
                    maxlength="25"
                    inputmode="tel"
                    autocomplete="tel"
                    class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                >
                @error('telefono')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="direccion" class="{{ $labelBase }}">Dirección</label>
                <input
                    id="direccion"
                    name="direccion"
                    type="text"
                    value="{{ old('direccion', $cliente->direccion) }}"
                    maxlength="255"
                    autocomplete="street-address"
                    class="{{ $inputBase }} {{ $errors->has('direccion') ? $inputError : $inputNormal }}"
                >
                @error('direccion')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Pasaporte</h2>
            <p class="text-xs text-ink/45">Si registras número o vencimiento, completa también el país emisor.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="pasaporte_numero" class="{{ $labelBase }}">Número de pasaporte</label>
                <input
                    id="pasaporte_numero"
                    name="pasaporte_numero"
                    type="text"
                    value="{{ old('pasaporte_numero', $cliente->pasaporte_numero) }}"
                    maxlength="25"
                    autocomplete="off"
                    class="{{ $inputBase }} {{ $errors->has('pasaporte_numero') ? $inputError : $inputNormal }}"
                >
                @error('pasaporte_numero')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pasaporte_pais" class="{{ $labelBase }}">País emisor</label>
                <input
                    id="pasaporte_pais"
                    name="pasaporte_pais"
                    type="text"
                    value="{{ old('pasaporte_pais', $cliente->pasaporte_pais) }}"
                    maxlength="2"
                    pattern="[A-Za-z]{2}"
                    placeholder="SV"
                    autocomplete="country"
                    class="{{ $inputBase }} uppercase {{ $errors->has('pasaporte_pais') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Código ISO de dos letras.</p>
                @error('pasaporte_pais')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pasaporte_vence" class="{{ $labelBase }}">Vencimiento</label>
                <input
                    id="pasaporte_vence"
                    name="pasaporte_vence"
                    type="date"
                    value="{{ old('pasaporte_vence', $cliente->pasaporte_vence?->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('pasaporte_vence') ? $inputError : $inputNormal }}"
                >
                @error('pasaporte_vence')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Preferencias de viaje</h2>
            <p class="text-xs text-ink/45">Información útil para futuras cotizaciones y reservas.</p>
        </div>

        <label for="preferencias_viaje" class="{{ $labelBase }}">Notas y preferencias</label>
        <textarea
            id="preferencias_viaje"
            name="preferencias_viaje"
            rows="4"
            maxlength="2000"
            placeholder="Alimentación, movilidad, intereses, restricciones o cualquier detalle relevante..."
            class="{{ $inputBase }} resize-y {{ $errors->has('preferencias_viaje') ? $inputError : $inputNormal }}"
        >{{ old('preferencias_viaje', $cliente->preferencias_viaje) }}</textarea>
        @error('preferencias_viaje')
            <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </section>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a
            href="{{ route('clientes.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 px-4 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
        >
            {{ $esEdicion ? 'Actualizar cliente' : 'Guardar cliente' }}
        </button>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const tipo = document.getElementById('clienteTipo');
            const persona = document.getElementById('camposPersona');
            const empresa = document.getElementById('camposEmpresa');
            const estado = document.getElementById('clienteEstado');
            const estadoTexto = document.getElementById('clienteEstadoTexto');

            const sincronizarEstado = () => {
                if (!estado || !estadoTexto) {
                    return;
                }

                estadoTexto.dataset.estado = estado.checked ? 'activo' : 'inactivo';
                estadoTexto.textContent = estado.checked ? 'Activo' : 'Inactivo';
            };

            estado?.addEventListener('change', sincronizarEstado);
            sincronizarEstado();

            if (!tipo || !persona || !empresa) {
                return;
            }

            const sincronizar = () => {
                const esEmpresa = tipo.value === 'empresa';
                persona.classList.toggle('hidden', esEmpresa);
                empresa.classList.toggle('hidden', !esEmpresa);
            };

            tipo.addEventListener('change', sincronizar);
            sincronizar();
        })();
    </script>
@endpush
