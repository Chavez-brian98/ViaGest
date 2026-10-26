@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
    @php
        $fotoUrl = $empleado->fotoPerfilUrl();
        $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
        $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
        $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
        $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
        $ayuda = 'mt-1.5 text-xs text-ink/45';
        $requerido = '<span class="text-rose-600">*</span>';
    @endphp

    <div class="mx-auto flex h-full max-w-6xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Mi perfil'],
            ],
        ])

        <section class="rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Mi perfil</h1>
                    <p class="text-xs text-ink/45">Administra tus datos personales, laborales y de acceso.</p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                <p class="font-semibold">Revisa los datos del perfil.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('perfil.update') }}"
            enctype="multipart/form-data"
            class="grid items-start gap-4 xl:grid-cols-[18rem_minmax(0,1fr)]"
        >
            @csrf
            @method('PUT')

            <aside class="space-y-4">
                <section class="rounded-2xl border border-ink/10 bg-surface p-5 text-center shadow-sm">
                    <div class="relative mx-auto h-28 w-28">
                        <span
                            id="perfilFotoFallback"
                            class="flex h-28 w-28 items-center justify-center rounded-full bg-secondary/15 text-3xl font-semibold text-primary ring-4 ring-secondary/10 @if ($fotoUrl) hidden @endif"
                        >
                            {{ $empleado->iniciales() }}
                        </span>
                        <img
                            id="perfilFotoPreview"
                            @if ($fotoUrl) src="{{ $fotoUrl }}" @endif
                            alt="Foto de perfil de {{ $empleado->nombreCompleto() }}"
                            class="h-28 w-28 rounded-full object-cover shadow-md ring-4 ring-secondary/10 @if (! $fotoUrl) hidden @endif"
                        >
                    </div>

                    <div class="mt-4">
                        <p class="truncate text-base font-semibold text-ink">{{ $empleado->nombreCompleto() }}</p>
                        <p class="truncate text-xs text-ink/45">{{ $empleado->email }}</p>
                    </div>

                    <div class="mt-5">
                        <input
                            id="perfilFoto"
                            name="foto"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="sr-only"
                        >
                        <label
                            for="perfilFoto"
                            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-ink/15 bg-background px-3.5 py-2 text-xs font-semibold text-ink/70 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus-within:outline-none focus-within:ring-4 focus-within:ring-secondary/20"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            Cambiar foto
                        </label>
                        <p id="perfilFotoNombre" class="{{ $ayuda }}">JPG, PNG o WebP. Máximo 2 MB.</p>
                        @error('foto')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror

                        @if ($fotoUrl)
                            <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-rose-600">
                                <input
                                    id="eliminarFoto"
                                    name="eliminar_foto"
                                    type="checkbox"
                                    value="1"
                                    @checked(old('eliminar_foto'))
                                    class="h-3.5 w-3.5 rounded border-ink/20 text-rose-600 focus:ring-rose-500/30"
                                >
                                Eliminar foto actual
                            </label>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-ink">Cuenta</h2>
                    <dl class="mt-4 space-y-3 text-xs">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-ink/45">Código</dt>
                            <dd class="text-right font-semibold text-ink">{{ $empleado->codigo }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-ink/45">Rol</dt>
                            <dd class="text-right font-semibold text-ink">{{ $empleado->rol?->nombre ? ucfirst($empleado->rol->nombre) : 'Sin rol' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-ink/45">Sucursal</dt>
                            <dd class="text-right font-semibold text-ink">{{ $empleado->sucursal?->nombre ?? 'Sin sucursal' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-ink/45">Acceso</dt>
                            <dd class="text-right font-semibold text-ink">{{ ucfirst(str_replace('_', ' ', $empleado->acceso)) }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-ink/45">Último acceso</dt>
                            <dd class="text-right font-semibold text-ink">
                                {{ $empleado->ultimo_acceso_at?->format('d/m/Y H:i') ?? 'Sin accesos' }}
                            </dd>
                        </div>
                    </dl>
                    <p class="mt-4 rounded-xl bg-background px-3 py-2 text-[11px] leading-relaxed text-ink/45">
                        El código, rol, estado y nivel de acceso se administran desde Recursos Humanos.
                    </p>
                </section>
            </aside>

            <div class="space-y-4">
                <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-ink">Datos personales</h2>
                        <p class="text-xs text-ink/45">Información de identificación y contacto.</p>
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
                                required
                                autocomplete="given-name"
                                class="{{ $inputBase }} {{ $errors->has('nombres') ? $inputError : $inputNormal }}"
                            >
                            @error('nombres')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
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
                                required
                                autocomplete="family-name"
                                class="{{ $inputBase }} {{ $errors->has('apellidos') ? $inputError : $inputNormal }}"
                            >
                            @error('apellidos')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tipo_documento" class="{{ $labelBase }}">Tipo de documento {!! $requerido !!}</label>
                            <select
                                id="tipo_documento"
                                name="tipo_documento"
                                required
                                class="{{ $inputBase }} {{ $errors->has('tipo_documento') ? $inputError : $inputNormal }}"
                            >
                                <option value="dui" @selected(old('tipo_documento', $empleado->tipo_documento) === 'dui')>DUI</option>
                                <option value="pasaporte" @selected(old('tipo_documento', $empleado->tipo_documento) === 'pasaporte')>Pasaporte</option>
                                <option value="carnet_residente" @selected(old('tipo_documento', $empleado->tipo_documento) === 'carnet_residente')>Carnet de residente</option>
                                <option value="otro" @selected(old('tipo_documento', $empleado->tipo_documento) === 'otro')>Otro</option>
                            </select>
                            @error('tipo_documento')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="numero_documento" class="{{ $labelBase }}">Número de documento {!! $requerido !!}</label>
                            <input
                                id="numero_documento"
                                name="numero_documento"
                                type="text"
                                value="{{ old('numero_documento', $empleado->numero_documento) }}"
                                maxlength="30"
                                required
                                class="{{ $inputBase }} {{ $errors->has('numero_documento') ? $inputError : $inputNormal }}"
                            >
                            @error('numero_documento')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="{{ $labelBase }}">Correo electrónico {!! $requerido !!}</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $empleado->email) }}"
                                maxlength="150"
                                required
                                autocomplete="email"
                                class="{{ $inputBase }} {{ $errors->has('email') ? $inputError : $inputNormal }}"
                            >
                            @error('email')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="telefono" class="{{ $labelBase }}">Teléfono</label>
                            <input
                                id="telefono"
                                name="telefono"
                                type="tel"
                                value="{{ old('telefono', $empleado->telefono) }}"
                                maxlength="25"
                                autocomplete="tel"
                                class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                            >
                            @error('telefono')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-ink">Información laboral</h2>
                        <p class="text-xs text-ink/45">Puesto, sucursal y condiciones de trabajo.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="cargo" class="{{ $labelBase }}">Cargo {!! $requerido !!}</label>
                            <input
                                id="cargo"
                                name="cargo"
                                type="text"
                                value="{{ old('cargo', $empleado->cargo) }}"
                                maxlength="80"
                                required
                                class="{{ $inputBase }} {{ $errors->has('cargo') ? $inputError : $inputNormal }}"
                            >
                            @error('cargo')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
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
                                    <option value="{{ $sucursal->id }}" @selected((string) old('sucursal_id', $empleado->sucursal_id) === (string) $sucursal->id)>
                                        {{ $sucursal->nombre }}{{ $sucursal->estado === 'inactiva' ? ' (inactiva)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sucursal_id')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tipo_contrato" class="{{ $labelBase }}">Tipo de contrato {!! $requerido !!}</label>
                            <select
                                id="tipo_contrato"
                                name="tipo_contrato"
                                required
                                class="{{ $inputBase }} {{ $errors->has('tipo_contrato') ? $inputError : $inputNormal }}"
                            >
                                <option value="indefinido" @selected(old('tipo_contrato', $empleado->tipo_contrato) === 'indefinido')>Indefinido</option>
                                <option value="plazo_fijo" @selected(old('tipo_contrato', $empleado->tipo_contrato) === 'plazo_fijo')>Plazo fijo</option>
                                <option value="servicios_profesionales" @selected(old('tipo_contrato', $empleado->tipo_contrato) === 'servicios_profesionales')>Servicios profesionales</option>
                            </select>
                            @error('tipo_contrato')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="salario_base" class="{{ $labelBase }}">Salario base</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-ink/40">$</span>
                                <input
                                    id="salario_base"
                                    name="salario_base"
                                    type="number"
                                    value="{{ old('salario_base', $empleado->salario_base) }}"
                                    min="0"
                                    max="99999999.99"
                                    step="0.01"
                                    class="{{ $inputBase }} pl-8 {{ $errors->has('salario_base') ? $inputError : $inputNormal }}"
                                >
                            </div>
                            @error('salario_base')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="fecha_ingreso" class="{{ $labelBase }}">Fecha de ingreso {!! $requerido !!}</label>
                            <input
                                id="fecha_ingreso"
                                name="fecha_ingreso"
                                type="date"
                                value="{{ old('fecha_ingreso', $empleado->fecha_ingreso?->format('Y-m-d')) }}"
                                required
                                class="{{ $inputBase }} {{ $errors->has('fecha_ingreso') ? $inputError : $inputNormal }}"
                            >
                            @error('fecha_ingreso')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
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
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                    <div class="mb-5">
                        <h2 class="text-base font-semibold text-ink">Seguridad</h2>
                        <p class="text-xs text-ink/45">Déjalo en blanco si no deseas cambiar tu contraseña.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div>
                            <label for="password_actual" class="{{ $labelBase }}">Contraseña actual</label>
                            <input
                                id="password_actual"
                                name="password_actual"
                                type="password"
                                autocomplete="current-password"
                                class="{{ $inputBase }} {{ $errors->has('password_actual') ? $inputError : $inputNormal }}"
                            >
                            @error('password_actual')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="{{ $labelBase }}">Nueva contraseña</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                class="{{ $inputBase }} {{ $errors->has('password') ? $inputError : $inputNormal }}"
                            >
                            @error('password')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="{{ $labelBase }}">Confirmar contraseña</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="{{ $inputBase }} {{ $inputNormal }}"
                            >
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a
                        href="{{ route('dashboard') }}"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/55 transition hover:bg-surface hover:text-ink focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        Cancelar
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-secondary/25"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const fotoInput = document.getElementById('perfilFoto');
            const fotoPreview = document.getElementById('perfilFotoPreview');
            const fotoFallback = document.getElementById('perfilFotoFallback');
            const fotoNombre = document.getElementById('perfilFotoNombre');
            const eliminarFoto = document.getElementById('eliminarFoto');
            const fotoOriginal = @json($fotoUrl);

            const mostrarFotoOriginal = () => {
                if (fotoOriginal) {
                    fotoPreview?.setAttribute('src', fotoOriginal);
                    fotoPreview?.classList.remove('hidden');
                    fotoFallback?.classList.add('hidden');
                } else {
                    fotoPreview?.classList.add('hidden');
                    fotoFallback?.classList.remove('hidden');
                }
            };

            fotoInput?.addEventListener('change', () => {
                const archivo = fotoInput.files?.[0];

                if (!archivo) {
                    return;
                }

                if (eliminarFoto) {
                    eliminarFoto.checked = false;
                }

                const url = URL.createObjectURL(archivo);

                if (fotoPreview) {
                    fotoPreview.src = url;
                    fotoPreview.classList.remove('hidden');
                    fotoPreview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                }

                fotoFallback?.classList.add('hidden');

                if (fotoNombre) {
                    fotoNombre.textContent = archivo.name;
                }
            });

            eliminarFoto?.addEventListener('change', () => {
                if (eliminarFoto.checked) {
                    if (fotoInput) {
                        fotoInput.value = '';
                    }

                    fotoPreview?.classList.add('hidden');
                    fotoFallback?.classList.remove('hidden');

                    if (fotoNombre) {
                        fotoNombre.textContent = 'La foto actual se eliminará al guardar.';
                    }

                    return;
                }

                mostrarFotoOriginal();

                if (fotoNombre) {
                    fotoNombre.textContent = 'JPG, PNG o WebP. Máximo 2 MB.';
                }
            });
        })();
    </script>
@endpush
