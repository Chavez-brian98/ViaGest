@extends('layouts.app')

@section('titulo', 'Prospectos')

@push('scripts')
    @vite('resources/js/prospectos.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Prospectos'],
            ],
        ])

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

        <section class="shrink-0 rounded-2xl border border-ink/10 bg-surface px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Prospectos</h1>
                    <p class="text-xs text-ink/45">Captación comercial, seguimiento de clientes potenciales y conversión</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-ink/10 px-5 py-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ route('prospectos.index') }}" id="prospectosFiltros" class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-center">
                    <div class="relative w-full lg:max-w-sm">
                        <label for="prospectosBuscar" class="sr-only">Buscar prospectos</label>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            id="prospectosBuscar"
                            name="buscar"
                            type="search"
                            value="{{ $buscar }}"
                            placeholder="Buscar por código, nombre, email, teléfono o destino..."
                            autocomplete="off"
                            class="w-full rounded-xl border border-ink/15 bg-background py-2.5 pl-10 pr-4 text-sm text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label for="filtroEstado" class="sr-only">Filtrar por estado</label>
                        <select
                            id="filtroEstado"
                            name="estado"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los estados</option>
                            @foreach ($estados as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected($filtros['estado'] === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        <label for="filtroOrigen" class="sr-only">Filtrar por origen</label>
                        <select
                            id="filtroOrigen"
                            name="origen"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los orígenes</option>
                            @foreach ($origenes as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected($filtros['origen'] === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        <label for="filtroSucursal" class="sr-only">Filtrar por sucursal</label>
                        <select
                            id="filtroSucursal"
                            name="sucursal_id"
                            data-filtro
                            class="min-w-[9rem] rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todas las sucursales</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" @selected((string) $filtros['sucursal_id'] === (string) $sucursal->id)>
                                    {{ $sucursal->nombre }}
                                </option>
                            @endforeach
                        </select>

                        @if (count($parametrosListado) > 0)
                            <a
                                href="{{ route('prospectos.index') }}"
                                data-limpiar-filtros
                                class="inline-flex items-center gap-1 rounded-xl px-2.5 py-2 text-xs font-semibold text-ink/45 transition hover:bg-background hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="flex items-center justify-end gap-2">
                    <a
                        href="{{ route('prospectos.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo prospecto
                    </a>
                </div>
            </header>

            <div id="prospectosTabla" class="flex-1 overflow-auto">
                @include('prospectos._tabla', compact('prospectos', 'parametrosListado'))
            </div>
        </section>
    </div>
@endsection
