@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    @php
        $inputBase = 'block w-full rounded-xl border bg-background px-4 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:bg-surface focus:outline-none focus:ring-4';
        $inputNormal = 'border-ink/15 focus:border-secondary focus:ring-secondary/20';
        $inputError = 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/15';
        $labelBase = 'mb-1.5 block text-sm font-medium text-ink/80';
        $ayuda = 'mt-1.5 text-xs text-ink/45';
        $requerido = '<span class="text-rose-600">*</span>';
        $colores = [
            'primary' => ['label' => 'Color primario', 'ayuda' => 'Botones, enlaces y elementos activos.'],
            'secondary' => ['label' => 'Color secundario', 'ayuda' => 'Estados de foco y acentos suaves.'],
            'accent' => ['label' => 'Color de acento', 'ayuda' => 'Detalles y llamados a la acción.'],
            'background' => ['label' => 'Color de fondo', 'ayuda' => 'Fondo general de la aplicación.'],
            'surface' => ['label' => 'Color de superficie', 'ayuda' => 'Tarjetas, tablas y paneles.'],
            'ink' => ['label' => 'Color de texto', 'ayuda' => 'Texto principal de la interfaz.'],
        ];
    @endphp

    <div class="mx-auto flex h-full max-w-6xl flex-col gap-4 overflow-y-auto pb-8">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Configuración'],
            ],
        ])

        <section class="rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Configuración del sistema</h1>
                    <p class="text-xs text-ink/45">Personaliza la identidad visual y los datos generales de la empresa.</p>
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
                <p class="font-semibold">Revisa los datos de la configuración.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $puedeEditar)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                Tienes acceso de solo lectura. Para modificar la configuración necesitas el permiso de edición.
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('configuracion.update') }}"
            enctype="multipart/form-data"
            data-config-form
            data-logo-original="{{ $logoUrl }}"
            class="grid items-start gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]"
        >
            @csrf
            @method('PUT')

            <fieldset @disabled(! $puedeEditar) class="contents">
                <aside class="space-y-4">
                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-ink">Logo de la empresa</h2>
                        <p class="{{ $ayuda }}">Se muestra en el menú lateral y en el inicio de sesión.</p>

                        <div class="mt-5 flex h-32 items-center justify-center rounded-2xl border border-dashed border-ink/15 bg-background p-4">
                            <img
                                id="configLogoPreview"
                                @if ($logoUrl) src="{{ $logoUrl }}" @endif
                                alt="Logo de {{ $configuracion->nombre_comercial }}"
                                class="max-h-full max-w-full rounded-xl object-contain @if (! $logoUrl) hidden @endif"
                            >
                            <span
                                id="configLogoFallback"
                                class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary text-lg font-semibold text-white shadow-lg shadow-primary/30 @if ($logoUrl) hidden @endif"
                            >
                                {{ $inicialesMarca }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <input
                                id="configLogo"
                                name="logo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="sr-only"
                            >
                            <label
                                for="configLogo"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-ink/15 bg-background px-3.5 py-2 text-xs font-semibold text-ink/70 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus-within:outline-none focus-within:ring-4 focus-within:ring-secondary/20"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                                </svg>
                                Cambiar logo
                            </label>
                            <p id="configLogoNombre" class="{{ $ayuda }}">PNG, JPG o WebP. Máximo 2 MB.</p>
                            @error('logo')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror

                            @if ($logoUrl)
                                <label class="mt-3 inline-flex cursor-pointer items-center gap-2 text-xs font-medium text-rose-600">
                                    <input
                                        id="configEliminarLogo"
                                        name="eliminar_logo"
                                        type="checkbox"
                                        value="1"
                                        @checked(old('eliminar_logo'))
                                        class="h-3.5 w-3.5 rounded border-ink/20 text-rose-600 focus:ring-rose-500/30"
                                    >
                                    Eliminar logo actual
                                </label>
                            @endif
                        </div>
                    </section>

                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-ink">Vista previa</h2>
                        <div
                            id="configTemaPreview"
                            class="mt-4 rounded-2xl border border-ink/10 p-4"
                            style="background-color: {{ $configuracion->color_background }}; color: {{ $configuracion->color_ink }};"
                        >
                            <div class="flex items-center gap-2">
                                <span data-preview-primary class="h-8 w-8 rounded-xl" style="background-color: {{ $configuracion->color_primary }};"></span>
                                <span class="text-sm font-semibold">{{ $configuracion->nombre_comercial }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span data-preview-primary class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white" style="background-color: {{ $configuracion->color_primary }};">Primario</span>
                                <span data-preview-secondary class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white" style="background-color: {{ $configuracion->color_secondary }};">Secundario</span>
                                <span data-preview-accent class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white" style="background-color: {{ $configuracion->color_accent }};">Acento</span>
                            </div>
                            <div
                                data-preview-surface
                                class="mt-3 rounded-xl border border-ink/10 p-3 text-xs"
                                style="background-color: {{ $configuracion->color_surface }};"
                            >
                                Superficie de tarjetas y tablas.
                            </div>
                        </div>
                    </section>
                </aside>

                <div class="space-y-4">
                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-ink">Información de la empresa</h2>
                            <p class="text-xs text-ink/45">Datos que se usan en documentos, correos y reportes.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="nombre_comercial" class="{{ $labelBase }}">Nombre comercial {!! $requerido !!}</label>
                                <input
                                    id="nombre_comercial"
                                    name="nombre_comercial"
                                    type="text"
                                    value="{{ old('nombre_comercial', $configuracion->nombre_comercial) }}"
                                    required
                                    maxlength="150"
                                    autocomplete="organization"
                                    class="{{ $inputBase }} {{ $errors->has('nombre_comercial') ? $inputError : $inputNormal }}"
                                >
                                @error('nombre_comercial')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="razon_social" class="{{ $labelBase }}">Razón social</label>
                                <input
                                    id="razon_social"
                                    name="razon_social"
                                    type="text"
                                    value="{{ old('razon_social', $configuracion->razon_social) }}"
                                    maxlength="150"
                                    class="{{ $inputBase }} {{ $errors->has('razon_social') ? $inputError : $inputNormal }}"
                                >
                                @error('razon_social')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="documento_fiscal" class="{{ $labelBase }}">Documento fiscal</label>
                                <input
                                    id="documento_fiscal"
                                    name="documento_fiscal"
                                    type="text"
                                    value="{{ old('documento_fiscal', $configuracion->documento_fiscal) }}"
                                    maxlength="30"
                                    class="{{ $inputBase }} {{ $errors->has('documento_fiscal') ? $inputError : $inputNormal }}"
                                >
                                @error('documento_fiscal')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email_contacto" class="{{ $labelBase }}">Correo de contacto</label>
                                <input
                                    id="email_contacto"
                                    name="email_contacto"
                                    type="email"
                                    value="{{ old('email_contacto', $configuracion->email_contacto) }}"
                                    maxlength="150"
                                    autocomplete="email"
                                    class="{{ $inputBase }} {{ $errors->has('email_contacto') ? $inputError : $inputNormal }}"
                                >
                                @error('email_contacto')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="telefono" class="{{ $labelBase }}">Teléfono</label>
                                <input
                                    id="telefono"
                                    name="telefono"
                                    type="text"
                                    value="{{ old('telefono', $configuracion->telefono) }}"
                                    maxlength="25"
                                    autocomplete="tel"
                                    class="{{ $inputBase }} {{ $errors->has('telefono') ? $inputError : $inputNormal }}"
                                >
                                @error('telefono')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="direccion" class="{{ $labelBase }}">Dirección</label>
                                <input
                                    id="direccion"
                                    name="direccion"
                                    type="text"
                                    value="{{ old('direccion', $configuracion->direccion) }}"
                                    maxlength="255"
                                    autocomplete="street-address"
                                    class="{{ $inputBase }} {{ $errors->has('direccion') ? $inputError : $inputNormal }}"
                                >
                                @error('direccion')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-ink">Preferencias regionales</h2>
                            <p class="text-xs text-ink/45">Zona horaria y moneda que se usan en el sistema.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="zona_horaria" class="{{ $labelBase }}">Zona horaria {!! $requerido !!}</label>
                                <select
                                    id="zona_horaria"
                                    name="zona_horaria"
                                    required
                                    class="{{ $inputBase }} {{ $errors->has('zona_horaria') ? $inputError : $inputNormal }}"
                                >
                                    @foreach ($zonasHorarias as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('zona_horaria', $configuracion->zona_horaria) === $valor)>
                                            {{ $etiqueta }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('zona_horaria')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="moneda_base" class="{{ $labelBase }}">Moneda base {!! $requerido !!}</label>
                                <select
                                    id="moneda_base"
                                    name="moneda_base"
                                    required
                                    class="{{ $inputBase }} {{ $errors->has('moneda_base') ? $inputError : $inputNormal }}"
                                >
                                    @foreach ($monedas as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(old('moneda_base', $configuracion->moneda_base) === $valor)>
                                            {{ $etiqueta }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('moneda_base')
                                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-base font-semibold text-ink">Colores del sistema</h2>
                                <p class="text-xs text-ink/45">Estos colores se aplican a toda la interfaz.</p>
                            </div>
                            <button
                                type="button"
                                data-reset-colores
                                class="rounded-xl border border-ink/15 bg-background px-3 py-1.5 text-xs font-semibold text-ink/60 transition hover:border-secondary/40 hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-4 focus:ring-secondary/20"
                            >
                                Restablecer
                            </button>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($colores as $clave => $meta)
                                <div>
                                    <label for="color_{{ $clave }}" class="{{ $labelBase }}">{{ $meta['label'] }}</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            data-color-picker="{{ $clave }}"
                                            value="{{ old('color_'.$clave, $configuracion->{'color_'.$clave}) }}"
                                            aria-label="Selector de {{ strtolower($meta['label']) }}"
                                            class="h-11 w-14 shrink-0 cursor-pointer rounded-xl border border-ink/15 bg-background p-1"
                                        >
                                        <input
                                            id="color_{{ $clave }}"
                                            name="color_{{ $clave }}"
                                            type="text"
                                            value="{{ old('color_'.$clave, $configuracion->{'color_'.$clave}) }}"
                                            required
                                            maxlength="7"
                                            pattern="^#[0-9a-fA-F]{6}$"
                                            data-color-text="{{ $clave }}"
                                            class="{{ $inputBase }} {{ $errors->has('color_'.$clave) ? $inputError : $inputNormal }} font-mono uppercase"
                                        >
                                    </div>
                                    <p class="{{ $ayuda }}">{{ $meta['ayuda'] }}</p>
                                    @error('color_'.$clave)
                                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-ink/10 bg-surface p-5 shadow-sm sm:p-6">
                        <div class="mb-5">
                            <h2 class="text-base font-semibold text-ink">Términos y condiciones</h2>
                            <p class="text-xs text-ink/45">Texto que se muestra en contratos y documentos de venta.</p>
                        </div>

                        <textarea
                            id="terminos_condiciones"
                            name="terminos_condiciones"
                            rows="6"
                            maxlength="10000"
                            class="{{ $inputBase }} {{ $errors->has('terminos_condiciones') ? $inputError : $inputNormal }}"
                        >{{ old('terminos_condiciones', $configuracion->terminos_condiciones) }}</textarea>
                        @error('terminos_condiciones')
                            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </section>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a
                            href="{{ route('dashboard') }}"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-ink/55 transition hover:bg-surface hover:text-ink focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            Cancelar
                        </a>
                        @if ($puedeEditar)
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-secondary/25"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Guardar cambios
                            </button>
                        @endif
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/configuracion.js')
@endpush
