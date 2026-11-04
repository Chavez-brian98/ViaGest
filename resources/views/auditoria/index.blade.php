@extends('layouts.app')

@section('titulo', 'Auditoría')

@push('scripts')
    @vite('resources/js/auditoria.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-3">
        @include('partials.breadcrumb', [
            'items' => [
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Auditoría'],
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-ink">Auditoría</h1>
                    <p class="text-xs text-ink/45">Registro de todas las acciones realizadas en el sistema</p>
                </div>
            </div>
        </section>

        <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-ink/10 px-5 py-3 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" action="{{ route('auditoria.index') }}" id="auditoriaFiltros" class="flex min-w-0 flex-1 flex-col gap-2 lg:flex-row lg:items-center lg:flex-wrap">
                    <div class="relative w-full lg:max-w-xs">
                        <label for="auditoriaBuscar" class="sr-only">Buscar en auditoría</label>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input
                            id="auditoriaBuscar"
                            name="buscar"
                            type="search"
                            value="{{ $buscar }}"
                            data-url="{{ route('auditoria.index') }}"
                            placeholder="Buscar por empleado o IP..."
                            autocomplete="off"
                            class="w-full rounded-xl border border-ink/15 bg-background py-2.5 pl-10 pr-4 text-sm text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <label for="filtroAccion" class="sr-only">Filtrar por acción</label>
                        <select
                            id="filtroAccion"
                            name="accion"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todas las acciones</option>
                            <option value="crear" @selected($filtros['accion'] === 'crear')>Creación</option>
                            <option value="actualizar" @selected($filtros['accion'] === 'actualizar')>Actualización</option>
                            <option value="eliminar" @selected($filtros['accion'] === 'eliminar')>Eliminación</option>
                            <option value="inicio_sesion" @selected($filtros['accion'] === 'inicio_sesion')>Inicio de sesión</option>
                            <option value="cierre_sesion" @selected($filtros['accion'] === 'cierre_sesion')>Cierre de sesión</option>
                            <option value="exportar" @selected($filtros['accion'] === 'exportar')>Exportación</option>
                        </select>

                        @if (count($modulos) > 0)
                            <label for="filtroModulo" class="sr-only">Filtrar por módulo</label>
                            <select
                                id="filtroModulo"
                                name="tabla"
                                data-filtro
                                class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                            >
                                <option value="">Todos los módulos</option>
                                @foreach ($modulos as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($filtros['tabla'] === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        @endif

                        <label for="filtroEmpleado" class="sr-only">Filtrar por empleado</label>
                        <select
                            id="filtroEmpleado"
                            name="empleado_id"
                            data-filtro
                            class="min-w-[10rem] rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todos los empleados</option>
                            @foreach ($empleados as $emp)
                                <option value="{{ $emp->id }}" @selected($filtros['empleado_id'] === (string) $emp->id)>
                                    {{ $emp->apellidos }}, {{ $emp->nombres }}
                                </option>
                            @endforeach
                        </select>

                        <label for="filtroRango" class="sr-only">Filtrar por período</label>
                        <select
                            id="filtroRango"
                            name="rango"
                            data-filtro
                            class="rounded-xl border border-ink/15 bg-background px-3 py-2.5 text-xs font-medium text-ink/70 transition focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20"
                        >
                            <option value="">Todo el período</option>
                            <option value="hoy" @selected($filtros['rango'] === 'hoy')>Hoy</option>
                            <option value="7d" @selected($filtros['rango'] === '7d')>Últimos 7 días</option>
                            <option value="30d" @selected($filtros['rango'] === '30d')>Últimos 30 días</option>
                        </select>

                        @if (count($parametrosListado) > 0)
                            <a
                                href="{{ route('auditoria.index') }}"
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
            </header>

            <div id="auditoriaTabla" class="min-h-0 flex-1 overflow-auto" aria-live="polite">
                @include('auditoria._tabla')
            </div>
        </section>
    </div>
@endsection
