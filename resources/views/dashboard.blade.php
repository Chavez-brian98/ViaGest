@extends('layouts.app')

@section('titulo', 'Dashboard')

@push('scripts')
    <script>
        window.dashboardCharts = @json(['ventasPorMes' => $ventasPorMes, 'ventasPorServicio' => $ventasPorServicio, 'ventasPorSucursal' => $ventasPorSucursal]);
    </script>
    @vite('resources/js/dashboard.js')
@endpush

@section('contenido')
    <div class="flex flex-col gap-4 lg:h-full lg:min-h-0">
        <div class="grid shrink-0 grid-cols-2 gap-4 xl:grid-cols-4">
            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ventas del mes</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-secondary text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">${{ number_format($metricas['ventasMes'], 2) }}</p>
                <p class="mt-1 text-xs">
                    @if (is_null($metricas['variacionVentas']))
                        <span class="text-ink/45">Sin comparativo del mes anterior</span>
                    @else
                        <span class="{{ $metricas['variacionVentas'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $metricas['variacionVentas'] >= 0 ? '+' : '' }}{{ number_format($metricas['variacionVentas'], 1) }}% vs mes anterior
                        </span>
                    @endif
                </p>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Utilidad del mes</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">${{ number_format($metricas['utilidadMes'], 2) }}</p>
                <p class="mt-1 text-xs text-ink/45">Margen bruto acumulado</p>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Saldo por cobrar</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">${{ number_format($metricas['saldoPorCobrar'], 2) }}</p>
                <p class="mt-1 text-xs text-ink/45">{{ $metricas['ventasAbiertas'] }} ventas abiertas</p>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Cupos disponibles</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h.375c.621 0 1.125.504 1.125 1.125v.75c0 .621-.504 1.125-1.125 1.125h-.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-.375c-.621 0-1.125-.504-1.125-1.125v-.75c0-.621.504-1.125 1.125-1.125h.375c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">{{ number_format($metricas['cuposDisponibles']) }}</p>
                <p class="mt-1 text-xs text-ink/45">Unidades en cupos vigentes</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-3">
            <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm lg:col-span-2">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Ventas por mes</h2>
                    <span class="text-xs text-ink/45">Ultimos 6 meses</span>
                </header>
                <div id="chartVentasMes" class="min-h-[16rem] flex-1 lg:min-h-0"></div>
            </section>

            <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Ventas por servicio</h2>
                    <span class="text-xs text-ink/45">Top 6</span>
                </header>
                <div id="chartVentasServicio" class="min-h-[16rem] flex-1 lg:min-h-0"></div>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-3">
            <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm lg:col-span-2">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Ventas por sucursal</h2>
                    <span class="text-xs text-ink/45">Acumulado</span>
                </header>
                <div id="chartVentasSucursal" class="min-h-[16rem] flex-1 lg:min-h-0"></div>
            </section>

            <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <header class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Proximos cobros</h2>
                    <span class="text-xs text-ink/45">Cuotas abiertas</span>
                </header>
                <ul class="flex-1 space-y-2 overflow-y-auto pr-1">
                    @forelse ($cuotasProximas as $cuota)
                        @php
                            $estadoClase = [
                                'vencida' => 'bg-rose-50 text-rose-600',
                                'parcial' => 'bg-amber-50 text-amber-600',
                                'pendiente' => 'bg-ink/10 text-ink/70',
                            ][$cuota['estado']] ?? 'bg-ink/10 text-ink/70';
                        @endphp
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-background px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-ink">{{ $cuota['cliente'] }}</p>
                                <p class="truncate text-xs text-ink/45">Venta {{ $cuota['venta'] }} &middot; cuota {{ $cuota['cuota'] }} &middot; vence {{ $cuota['vence'] }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-ink">${{ number_format($cuota['saldo'], 2) }}</p>
                                <span class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $estadoClase }}">
                                    {{ $cuota['estado'] }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="flex h-full items-center justify-center rounded-xl bg-background px-3 py-6 text-center text-xs text-ink/45">
                            No hay cuotas pendientes por cobrar.
                        </li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
