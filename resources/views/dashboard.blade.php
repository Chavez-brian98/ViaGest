@extends('layouts.app')

@section('titulo', 'Dashboard')

@push('scripts')
    <script>
        window.dashboardGraficas = @json($graficas);
    </script>
    @vite('resources/js/dashboard.js')
@endpush

@section('contenido')
    <div class="flex h-full min-h-0 flex-col gap-4">
        <div class="grid shrink-0 grid-cols-2 gap-4 xl:grid-cols-6">
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
                        <span class="text-ink/45">Sin comparativo</span>
                    @else
                        <span class="{{ $metricas['variacionVentas'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $metricas['variacionVentas'] >= 0 ? '+' : '' }}{{ number_format($metricas['variacionVentas'], 1) }}% vs mes anterior
                        </span>
                    @endif
                </p>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Utilidad bruta</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">${{ number_format($metricas['utilidadMes'], 2) }}</p>
                <p class="mt-1 text-xs text-ink/45">Mes actual</p>
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
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Cuotas vencidas</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">{{ number_format($metricas['cuotasVencidas']) }}</p>
                <p class="mt-1 text-xs text-ink/45">Requieren seguimiento</p>
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
                <p class="mt-1 text-xs text-ink/45">Vigentes</p>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-surface p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ventas abiertas</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-secondary/15 text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold tracking-tight text-ink">{{ number_format($metricas['ventasAbiertas']) }}</p>
                <p class="mt-1 text-xs text-ink/45">En seguimiento</p>
            </div>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 xl:grid-cols-12">
            <section class="relative overflow-hidden rounded-2xl border border-ink/10 bg-surface shadow-sm xl:col-span-8">
                <div class="flex items-center justify-between px-5 pt-4">
                    <div>
                        <h2 class="text-sm font-semibold text-ink">Análisis comercial</h2>
                        <p class="text-xs text-ink/45">6 gráficos en carrusel</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" data-carousel-prev class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-background hover:text-ink">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                        </button>
                        <button type="button" data-carousel-next class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-background hover:text-ink">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                        </button>
                    </div>
                </div>
                <div data-carousel class="mt-2 flex h-[calc(100%-4rem)] snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Evolución de ventas últimos 12 meses</h3>
                            <span class="text-xs text-ink/45">Mensual</span>
                        </header>
                        <div id="chartEvolucion12Meses" class="min-h-[16rem] flex-1"></div>
                    </article>
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Ventas por sucursal (mes actual)</h3>
                            <span class="text-xs text-ink/45">Comparativo</span>
                        </header>
                        <div id="chartVentasPorSucursalMes" class="min-h-[16rem] flex-1"></div>
                    </article>
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Ventas por tipo de servicio (últimos 3 meses)</h3>
                            <span class="text-xs text-ink/45">Participación</span>
                        </header>
                        <div id="chartVentasPorTipoServicio" class="min-h-[16rem] flex-1"></div>
                    </article>
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Estado de cobranza</h3>
                            <span class="text-xs text-ink/45">Pagado / Parcial / Pendiente / Vencido</span>
                        </header>
                        <div id="chartEstadoCobranza" class="min-h-[16rem] flex-1"></div>
                    </article>
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Top 5 vendedores (mes actual)</h3>
                            <span class="text-xs text-ink/45">Facturación</span>
                        </header>
                        <div id="chartTopVendedores" class="min-h-[16rem] flex-1"></div>
                    </article>
                    <article class="flex h-full min-w-full snap-start flex-col px-5 pb-4">
                        <header class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-ink">Cupos críticos</h3>
                            <span class="text-xs text-ink/45">Disponibles ≤ 6</span>
                        </header>
                        <div id="chartCuposCriticos" class="min-h-[16rem] flex-1"></div>
                    </article>
                </div>
                <div class="absolute bottom-2 left-1/2 flex -translate-x-1/2 items-center gap-1.5" data-carousel-dots></div>
            </section>

            <div class="grid min-h-0 grid-cols-1 gap-4 xl:col-span-4 xl:grid-rows-2">
                <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface shadow-sm">
                    <header class="flex items-center justify-between px-4 py-3">
                        <h2 class="text-sm font-semibold text-ink">Ventas por mes</h2>
                        <span class="text-xs text-ink/45">Últimos 12 meses</span>
                    </header>
                    <div id="chartVentasPorMes" class="min-h-0 flex-1 px-2 pb-2"></div>
                </section>
                <section class="flex min-h-0 flex-col rounded-2xl border border-ink/10 bg-surface shadow-sm">
                    <header class="flex items-center justify-between px-4 py-3">
                        <h2 class="text-sm font-semibold text-ink">Cuotas próximas a vencer</h2>
                        <span class="text-xs text-ink/45">Cobranza</span>
                    </header>
                    <div class="min-h-0 flex-1 overflow-auto px-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-ink/45">
                                    <th class="pb-2">Venta</th>
                                    <th class="pb-2">Cliente</th>
                                    <th class="pb-2">Cuota</th>
                                    <th class="pb-2">Vence</th>
                                    <th class="pb-2 text-right">Saldo</th>
                                    <th class="pb-2 text-right">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/5">
                                @forelse ($cuotasProximas as $cuota)
                                    <tr>
                                        <td class="py-2 pr-2 font-medium text-ink">{{ $cuota['venta'] }}</td>
                                        <td class="py-2 pr-2 text-ink/70">{{ Str::limit($cuota['cliente'], 18) }}</td>
                                        <td class="py-2 pr-2 text-ink/70">{{ $cuota['cuota'] }}</td>
                                        <td class="py-2 pr-2 text-ink/70">{{ $cuota['vence'] }}</td>
                                        <td class="py-2 pr-2 text-right font-medium text-ink">${{ number_format($cuota['saldo'], 2) }}</td>
                                        <td class="py-2 text-right">
                                            @php
                                                $color = ['vencida'=>'bg-rose-100 text-rose-700','parcial'=>'bg-amber-100 text-amber-700','pendiente'=>'bg-ink/10 text-ink/70'][$cuota['estado']] ?? 'bg-ink/10 text-ink/70';
                                            @endphp
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $color }}">{{ $cuota['estado'] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-4 text-center text-xs text-ink/45">Sin cuotas pr��ximas</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($cuotasProximas->total() > 0)
                        <div class="border-t border-ink/10 px-4 py-2">
                            @include('partials.paginacion', ['paginator' => $cuotasProximas, 'etiqueta' => 'cuotas'])
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const carousel = document.querySelector('[data-carousel]');
            const prev = document.querySelector('[data-carousel-prev]');
            const next = document.querySelector('[data-carousel-next]');
            const dots = document.querySelector('[data-carousel-dots]');
            if (carousel && prev && next && dots) {
                const slides = Array.from(carousel.children);
                dots.innerHTML = slides.map((_, i) => `<button class="h-1.5 rounded-full ${i===0?'w-6 bg-primary':'w-2 bg-ink/20'}" data-dot="${i}"></button>`).join('');
                const btns = Array.from(dots.querySelectorAll('[data-dot]'));
                const go = (i) => {
                    carousel.scrollTo({ left: carousel.clientWidth * i, behavior: 'smooth' });
                    btns.forEach((b, idx) => { b.className = `h-1.5 rounded-full ${idx===i?'w-6 bg-primary':'w-2 bg-ink/20'}`; });
                };
                prev.addEventListener('click', () => { const i = Math.max(0, Math.round(carousel.scrollLeft/carousel.clientWidth)-1); go(i); });
                next.addEventListener('click', () => { const i = Math.min(slides.length-1, Math.round(carousel.scrollLeft/carousel.clientWidth)+1); go(i); });
                btns.forEach((b,i)=>b.addEventListener('click',()=>go(i)));
                carousel.addEventListener('scroll', () => {
                    const i = Math.round(carousel.scrollLeft / carousel.clientWidth);
                    btns.forEach((b, idx) => { b.className = `h-1.5 rounded-full ${idx===i?'w-6 bg-primary':'w-2 bg-ink/20'}`; });
                }, { passive: true });
            }
        </script>
    @endpush
@endsection
