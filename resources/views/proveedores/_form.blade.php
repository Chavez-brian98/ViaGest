@php
    $esEdicion = $proveedor->exists;
    $estadoSeleccionado = old('estado', $proveedor->estado ?? 'activo');
    $tipoSeleccionado = old('tipo', $proveedor->tipo ?? 'operador');
    $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
    $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
    $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
    $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
    $ayuda = 'mt-1.5 text-xs text-ink/45';
    $requerido = '<span class="text-rose-600">*</span>';
    $tipos = [
        'aerolinea' => 'Aerolínea',
        'hospedaje' => 'Hospedaje',
        'transporte' => 'Transporte',
        'guia' => 'Guía',
        'operador' => 'Operador',
        'otro' => 'Otro',
    ];
@endphp

<form
    method="POST"
    action="{{ $esEdicion ? route('proveedores.update', $proveedor->id) : route('proveedores.store') }}"
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
                <p class="text-xs text-ink/45">Identificación comercial del proveedor.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-medium text-ink/45">Estado</span>
                <label class="relative inline-flex cursor-pointer items-center">
                    <input type="hidden" name="estado" value="inactivo">
                    <input
                        id="proveedorEstado"
                        type="checkbox"
                        name="estado"
                        value="activo"
                        role="switch"
                        class="peer sr-only"
                        @checked($estadoSeleccionado === 'activo')
                    >
                    <span class="estado-form-switch">
                        <span id="proveedorEstadoTexto" class="estado-texto" data-estado-texto data-estado="{{ $estadoSeleccionado }}">
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
                    value="{{ old('codigo', $proveedor->codigo) }}"
                    maxlength="20"
                    required
                    autocomplete="off"
                    class="{{ $inputBase }} {{ $errors->has('codigo') ? $inputError : $inputNormal }}"
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
                    value="{{ old('nombre', $proveedor->nombre) }}"
                    maxlength="150"
                    required
                    class="{{ $inputBase }} {{ $errors->has('nombre') ? $inputError : $inputNormal }}"
                >
                @error('nombre')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="razon_social" class="{{ $labelBase }}">Razón social</label>
                <input
                    id="razon_social"
                    name="razon_social"
                    type="text"
                    value="{{ old('razon_social', $proveedor->razon_social) }}"
                    maxlength="150"
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
            <h2 class="text-base font-semibold text-ink">Información fiscal</h2>
            <p class="text-xs text-ink/45">Datos legales y condiciones de crédito.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="documento_fiscal" class="{{ $labelBase }}">Documento fiscal</label>
                <input
                    id="documento_fiscal"
                    name="documento_fiscal"
                    type="text"
                    value="{{ old('documento_fiscal', $proveedor->documento_fiscal) }}"
                    maxlength="30"
                    class="{{ $inputBase }} {{ $errors->has('documento_fiscal') ? $inputError : $inputNormal }}"
                >
                @error('documento_fiscal')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pais_codigo" class="{{ $labelBase }}">País {!! $requerido !!}</label>
                <input
                    id="pais_codigo"
                    name="pais_codigo"
                    type="text"
                    value="{{ old('pais_codigo', $proveedor->pais_codigo) }}"
                    maxlength="2"
                    required
                    placeholder="SV"
                    autocomplete="off"
                    class="{{ $inputBase }} uppercase {{ $errors->has('pais_codigo') ? $inputError : $inputNormal }}"
                >
                <p class="{{ $ayuda }}">Código ISO de dos letras (ej. SV, GT, US).</p>
                @error('pais_codigo')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="dias_credito" class="{{ $labelBase }}">Días de crédito</label>
                <input
                    id="dias_credito"
                    name="dias_credito"
                    type="number"
                    value="{{ old('dias_credito', $proveedor->dias_credito ?? 0) }}"
                    min="0"
                    max="365"
                    class="{{ $inputBase }} {{ $errors->has('dias_credito') ? $inputError : $inputNormal }}"
                >
                @error('dias_credito')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <h2 class="text-base font-semibold text-ink">Contacto y notas</h2>
            <p class="text-xs text-ink/45">Canales de comunicación y observaciones internas.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="contacto_nombre" class="{{ $labelBase }}">Persona de contacto</label>
                <input
                    id="contacto_nombre"
                    name="contacto_nombre"
                    type="text"
                    value="{{ old('contacto_nombre', $proveedor->contacto_nombre) }}"
                    maxlength="120"
                    class="{{ $inputBase }} {{ $errors->has('contacto_nombre') ? $inputError : $inputNormal }}"
                >
                @error('contacto_nombre')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="telefono" class="{{ $labelBase }}">Teléfono</label>
                <input
                    id="telefono"
                    name="telefono"
                    type="text"
                    value="{{ old('telefono', $proveedor->telefono) }}"
                    maxlength="25"
                    class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                >
                @error('telefono')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="{{ $labelBase }}">Correo electrónico</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $proveedor->email) }}"
                    maxlength="150"
                    class="{{ $inputBase }} {{ $errors->has('email') ? $inputError : $inputNormal }}"
                >
                @error('email')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="notas" class="{{ $labelBase }}">Notas</label>
                <textarea
                    id="notas"
                    name="notas"
                    rows="3"
                    maxlength="2000"
                    class="{{ $inputBase }} {{ $errors->has('notas') ? $inputError : $inputNormal }}"
                >{{ old('notas', $proveedor->notas) }}</textarea>
                @error('notas')
                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a
            href="{{ route('proveedores.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-ink/15 px-4 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90"
        >
            {{ $esEdicion ? 'Actualizar proveedor' : 'Guardar proveedor' }}
        </button>
    </div>

    @push('scripts')
        <script>
            (() => {
                const estado = document.getElementById('proveedorEstado');
                const estadoTexto = document.getElementById('proveedorEstadoTexto');

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
