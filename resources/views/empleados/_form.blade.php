@php
    $esEdicion = $empleado->exists;
    $estadoSeleccionado = old('estado', $empleado->estado ?? 'activo');
    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('empleados.update', $empleado) : route('empleados.store') }}"
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
                <p class="text-xs text-ink/45">Identificación y registro del empleado.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-medium text-ink/45">Estado</span>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="estado" value="inactivo">
                    <input
                        id="empleadoEstado"
                        type="checkbox"
                        name="estado"
                        value="activo"
                        role="switch"
                        class="peer sr-only"
                        @checked($estadoSeleccionado === 'activo')
                    >
                    <span class="estado-form-switch">
                        <span id="empleadoEstadoTexto" class="estado-texto" data-estado-texto data-estado="{{ $estadoSeleccionado }}">
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
                    value="{{ old('codigo', $empleado->codigo) }}"
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
                            @selected((string) old('sucursal_id', $empleado->sucursal_id) === (string) $sucursal->id)
                        >
                            {{ $sucursal->nombre }} ({{ $sucursal->codigo }})
                        </option>
                    @endforeach
                </select>
                @error('sucursal_id')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="cargo" class="{{ $labelBase }}">Cargo</label>
                <input
                    id="cargo"
                    name="cargo"
                    type="text"
                    value="{{ old('cargo', $empleado->cargo) }}"
                    maxlength="100"
                    class="{{ $inputBase }} {{ $errors->has('cargo') ? $inputError : $inputNormal }}"
                >
                @error('cargo')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Información personal</h2>
            <p class="text-xs text-ink/45">Datos de identidad y contacto.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="nombres" class="{{ $labelBase }}">Nombres {!! $requerido !!}</label>
                <input
                    id="nombres"
                    name="nombres"
                    type="text"
                    value="{{ old('nombres', $empleado->nombres) }}"
                    maxlength="100"
                    autocomplete="given-name"
                    required
                    class="{{ $inputBase }} {{ $errors->has('nombres') ? $inputError : $inputNormal }}"
                >
                @error('nombres')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="apellidos" class="{{ $labelBase }}">Apellidos {!! $requerido !!}</label>
                <input
                    id="apellidos"
                    name="apellidos"
                    type="text"
                    value="{{ old('apellidos', $empleado->apellidos) }}"
                    maxlength="100"
                    autocomplete="family-name"
                    required
                    class="{{ $inputBase }} {{ $errors->has('apellidos') ? $inputError : $inputNormal }}"
                >
                @error('apellidos')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tipo_documento" class="{{ $labelBase }}">Tipo de documento</label>
                <select
                    id="tipo_documento"
                    name="tipo_documento"
                    class="{{ $inputBase }} {{ $errors->has('tipo_documento') ? $inputError : $inputNormal }}"
                >
                    <option value="">Sin documento</option>
                    <option value="dui" @selected(old('tipo_documento', $empleado->tipo_documento) === 'dui')>DUI</option>
                    <option value="nit" @selected(old('tipo_documento', $empleado->tipo_documento) === 'nit')>NIT</option>
                    <option value="carnet_residente" @selected(old('tipo_documento', $empleado->tipo_documento) === 'carnet_residente')>Carnet de residente</option>
                    <option value="otro" @selected(old('tipo_documento', $empleado->tipo_documento) === 'otro')>Otro</option>
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
                    value="{{ old('numero_documento', $empleado->numero_documento) }}"
                    maxlength="30"
                    autocomplete="off"
                    class="{{ $inputBase }} {{ $errors->has('numero_documento') ? $inputError : $inputNormal }}"
                >
                @error('numero_documento')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="{{ $labelBase }}">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $empleado->email) }}"
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
                    value="{{ old('telefono', $empleado->telefono) }}"
                    maxlength="25"
                    inputmode="tel"
                    autocomplete="tel"
                    class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                >
                @error('telefono')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Información laboral</h2>
            <p class="text-xs text-ink/45">Condiciones contractuales y remuneración.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="tipo_contrato" class="{{ $labelBase }}">Tipo de contrato</label>
                <input
                    id="tipo_contrato"
                    name="tipo_contrato"
                    type="text"
                    value="{{ old('tipo_contrato', $empleado->tipo_contrato) }}"
                    maxlength="50"
                    class="{{ $inputBase }} {{ $errors->has('tipo_contrato') ? $inputError : $inputNormal }}"
                >
                @error('tipo_contrato')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_ingreso" class="{{ $labelBase }}">Fecha de ingreso</label>
                <input
                    id="fecha_ingreso"
                    name="fecha_ingreso"
                    type="date"
                    value="{{ old('fecha_ingreso', $empleado->fecha_ingreso?->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_ingreso') ? $inputError : $inputNormal }}"
                >
                @error('fecha_ingreso')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="fecha_salida" class="{{ $labelBase }}">Fecha de salida</label>
                <input
                    id="fecha_salida"
                    name="fecha_salida"
                    type="date"
                    value="{{ old('fecha_salida', $empleado->fecha_salida?->format('Y-m-d')) }}"
                    class="{{ $inputBase }} {{ $errors->has('fecha_salida') ? $inputError : $inputNormal }}"
                >
                @error('fecha_salida')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="salario_base" class="{{ $labelBase }}">Salario base</label>
                <input
                    id="salario_base"
                    name="salario_base"
                    type="number"
                    step="0.01"
                    value="{{ old('salario_base', $empleado->salario_base) }}"
                    class="{{ $inputBase }} {{ $errors->has('salario_base') ? $inputError : $inputNormal }}"
                >
                @error('salario_base')
                    <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a
            href="{{ route('empleados.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 px-4 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
        >
            {{ $esEdicion ? 'Actualizar empleado' : 'Guardar empleado' }}
        </button>
    </div>

    @push('scripts')
        <script>
            (() => {
                const estado = document.getElementById('empleadoEstado');
                const estadoTexto = document.getElementById('empleadoEstadoTexto');

                const sincronizarEstado = () => {
                    if (!estado || !estadoTexto) {
                        return;
                    }

                    estadoTexto.dataset.estado = estado.checked ? 'activo' : 'inactivo';
                    estadoTexto.textContent = estado.checked ? 'Activo' : 'Inactivo';
                };

                estado?.addEventListener('change', sincronizarEstado);
                sincronizarEstado();
            })();
        </script>
    @endpush
</form>
