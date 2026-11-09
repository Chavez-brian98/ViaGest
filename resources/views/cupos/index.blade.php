@extends('layouts.app')

@section('titulo', 'Cupos de Inventario')

@push('scripts')
    @vite('resources/js/cupos.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cupos'],
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Inventario de Cupos</h1>
                    <p class="text-xs text-ink/45">Gestión de bloqueos y compras anticipadas de servicios</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-ink/10 px-5 py-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ route('cupos.index') }}" id="cuposFiltros" class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-center">
                    <div class="relative w-full lg:max-w-sm">
                        <label for="cuposBuscar" class="sr-only">Buscar cupos</label>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            id="cuposBuscar"
                            name="buscar"
                            type="search"
                            value="{{ $buscar }}"
                            data-url="{{ route('cupos.index') }}"
                            placeholder="Buscar por código, servicio o proveedor..."
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
                            <option value="bloqueo" @selected($filtros['tipo'] === 'bloqueo')>Bloqueo</option>
                            <option value="compra_adelantada" @selected($filtros['tipo'] === 'compra_adelantada')>Compra adelantada</option>
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
                            <option value="liberado" @selected($filtros['estado'] === 'liberado')>Liberados</option>
                            <option value="cerrado" @selected($filtros['estado'] === 'cerrado')>Cerrados</option>
                        </select>

                        <label for="filtroServicio" class="sr-only">Filtrar por servicio</label>
                        <select
                            id="filtroServicio"
                            name="servicio_id"
                            data-filtro
                            class="max-w-xs rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20 truncate"
                        >
                            <option value="">Todos los servicios</option>
                            @foreach ($servicios as $s)
                                <option value="{{ $s->id }}" @selected($filtros['servicio_id'] == $s->id)>
                                    {{ $s->codigo }} - {{ $s->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('cupos.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/20"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Nuevo cupo</span>
                    </a>
                </div>
            </header>

            <div id="cuposTabla" class="flex-1 overflow-auto">
                @include('cupos._tabla', ['cupos' => $cupos, 'parametrosListado' => $parametrosListado])
            </div>
        </section>
    </div>
@endsection
