@extends('layouts.app')

@section('titulo', 'Proveedores')

@push('scripts')
    @vite('resources/js/proveedores.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Proveedores'],
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.5M21 3.545V21m-18-12h18" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Proveedores</h1>
                    <p class="text-xs text-ink/45">Gestión de proveedores, hoteles, aerolíneas y operadores</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-ink/10 px-5 py-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ route('proveedores.index') }}" id="proveedoresFiltros" class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-center">
                    <div class="relative w-full lg:max-w-sm">
                        <label for="proveedoresBuscar" class="sr-only">Buscar proveedores</label>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            id="proveedoresBuscar"
                            name="buscar"
                            type="search"
                            value="{{ $buscar }}"
                            data-url="{{ route('proveedores.index') }}"
                            placeholder="Buscar por código, nombre, documento o email..."
                            autocomplete="off"
                            class="w-full rounded-xl border border-ink/15 bg-background py-2.5 pl-10 pr-4 text-sm text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label for="filtroTipo" class="sr-only">Filtrar por tipo</label>
                        <select
                            id="filtroTipo"
                            name="tipo"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los tipos</option>
                            <option value="aerolinea" @selected($filtros['tipo'] === 'aerolinea')>Aerolíneas</option>
                            <option value="hospedaje" @selected($filtros['tipo'] === 'hospedaje')>Hospedajes</option>
                            <option value="transporte" @selected($filtros['tipo'] === 'transporte')>Transporte</option>
                            <option value="guia" @selected($filtros['tipo'] === 'guia')>Guías</option>
                            <option value="operador" @selected($filtros['tipo'] === 'operador')>Operadores</option>
                            <option value="otro" @selected($filtros['tipo'] === 'otro')>Otros</option>
                        </select>

                        <label for="filtroEstado" class="sr-only">Filtrar por estado</label>
                        <select
                            id="filtroEstado"
                            name="estado"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los estados</option>
                            <option value="activo" @selected($filtros['estado'] === 'activo')>Activos</option>
                            <option value="inactivo" @selected($filtros['estado'] === 'inactivo')>Inactivos</option>
                        </select>

                        @if (count($parametrosListado) > 0)
                            <a
                                href="{{ route('proveedores.index') }}"
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

                <a
                    href="{{ route('proveedores.create') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo proveedor
                </a>
            </header>

            <div id="proveedoresTabla" class="min-h-0 flex-1 overflow-auto" aria-live="polite">
                @include('proveedores._tabla')
            </div>
        </section>
    </div>
@endsection
