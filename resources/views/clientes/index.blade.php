@extends('layouts.app')

@section('titulo', 'Clientes')

@push('scripts')
    @vite('resources/js/clientes.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Clientes'],
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Clientes</h1>
                    <p class="text-xs text-ink/45">Directorio de personas y empresas registradas</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-ink/10 px-5 py-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ route('clientes.index') }}" id="clientesFiltros" class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-center">
                    <div class="relative w-full lg:max-w-sm">
                        <label for="clientesBuscar" class="sr-only">Buscar clientes</label>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            id="clientesBuscar"
                            name="buscar"
                            type="search"
                            value="{{ $buscar }}"
                            data-url="{{ route('clientes.index') }}"
                            placeholder="Buscar por código, nombre, documento, email o teléfono..."
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
                            <option value="activo" @selected($filtros['estado'] === 'activo')>Activos</option>
                            <option value="inactivo" @selected($filtros['estado'] === 'inactivo')>Inactivos</option>
                        </select>

                        <label for="filtroTipo" class="sr-only">Filtrar por tipo</label>
                        <select
                            id="filtroTipo"
                            name="tipo"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los tipos</option>
                            <option value="persona" @selected($filtros['tipo'] === 'persona')>Personas</option>
                            <option value="empresa" @selected($filtros['tipo'] === 'empresa')>Empresas</option>
                        </select>

                        <label for="filtroSucursal" class="sr-only">Filtrar por sucursal</label>
                        <select
                            id="filtroSucursal"
                            name="sucursal_id"
                            data-filtro
                            class="min-w-[10rem] rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
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
                                href="{{ route('clientes.index') }}"
                                data-limpiar-filtros
                                class="inline-flex items-center gap-1 rounded-xl px-2.5 py-2 text-xs font-semibold text-ink/45 transition hover:bg-background hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Limpiar
                            </a>
                        @endif

                        <noscript>
                            <button type="submit" class="rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-white">
                                Aplicar filtros
                            </button>
                        </noscript>
                    </div>
                </form>

                <a
                    href="{{ route('clientes.create') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nuevo cliente
                </a>
            </header>

            <div id="clientesTabla" class="min-h-0 flex-1 overflow-auto" aria-live="polite">
                @include('clientes._tabla')
            </div>
        </section>
    </div>
@endsection
