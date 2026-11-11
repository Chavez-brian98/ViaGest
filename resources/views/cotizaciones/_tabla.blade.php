@php
    $parametrosListado = $parametrosListado ?? [];
    $mostrar = fn ($valor) => filled($valor) ? $valor : '—';
    $estadosClases = [
        'borrador' => 'bg-slate-100 text-slate-700 border-slate-200',
        'enviado' => 'bg-sky-50 text-sky-700 border-sky-200',
        'aprobado' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rechazado' => 'bg-rose-50 text-rose-700 border-rose-200',
        'caducado' => 'bg-amber-50 text-amber-700 border-amber-200',
        'convertido' => 'bg-purple-50 text-purple-700 border-purple-200',
    ];
    $estadosNombres = \App\Service\Cotizaciones\CotizacionService::ESTADOS;
@endphp

<table class="w-full min-w-[50rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="whitespace-nowrap px-5 py-3">Código</th>
            <th class="px-5 py-3">Cliente / Prospecto</th>
            <th class="hidden px-5 py-3 md:table-cell">Paquete / Fechas</th>
            <th class="px-5 py-3 text-right">Monto total</th>
            <th class="hidden whitespace-nowrap px-5 py-3 xl:table-cell">Vendedor</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($cotizaciones as $cotizacion)
            @php
                $esEliminable = in_array($cotizacion->estado, ['borrador', 'rechazado', 'caducado'], true);
                $esConvertido = $cotizacion->estado === 'convertido';
                $destinatario = $cotizacion->cliente_id
                    ? ($cotizacion->cliente_razon_social ?: trim($cotizacion->cliente_nombres . ' ' . $cotizacion->cliente_apellidos))
                    : trim($cotizacion->prospecto_nombres . ' ' . $cotizacion->prospecto_apellidos);
                $tipoDestinatario = $cotizacion->cliente_id ? 'Cliente' : 'Prospecto';
                $totalCotizado = max(0, (float) $cotizacion->subtotal_servicios - (float) $cotizacion->descuento_monto);
            @endphp
            <tr class="transition-colors hover:bg-background/70" data-fila-cotizacion="{{ $cotizacion->id }}">
                <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-semibold text-ink/80">
                    {{ $cotizacion->codigo }}
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-sm font-semibold text-primary">
                            {{ Str::upper(Str::substr($destinatario ?: 'C', 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">{{ $destinatario ?: 'Sin asignar' }}</p>
                            <p class="text-xs text-ink/45">
                                <span class="font-semibold text-primary/80">{{ $tipoDestinatario }}:</span>
                                {{ $cotizacion->cliente_codigo ?: $cotizacion->prospecto_codigo ?: '—' }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="hidden px-5 py-3 md:table-cell">
                    <p class="text-xs font-medium text-ink truncate max-w-xs">
                        {{ $cotizacion->paquete_nombre ?: 'Servicios personalizados' }}
                    </p>
                    <p class="text-[11px] text-ink/45">
                        Emisión: {{ \Carbon\Carbon::parse($cotizacion->fecha_emision)->format('d/m/Y') }} ·
                        Caduca: <span class="font-semibold {{ \Carbon\Carbon::parse($cotizacion->fecha_caducidad)->isPast() && $cotizacion->estado !== 'convertido' ? 'text-rose-600' : 'text-ink/70' }}">{{ \Carbon\Carbon::parse($cotizacion->fecha_caducidad)->format('d/m/Y') }}</span>
                    </p>
                </td>
                <td class="px-5 py-3 text-right">
                    <span class="font-mono text-sm font-semibold text-ink">
                        ${{ number_format($totalCotizado, 2) }}
                    </span>
                    @if ((float) $cotizacion->descuento_monto > 0)
                        <p class="text-[10px] text-rose-600">Desc: -${{ number_format($cotizacion->descuento_monto, 2) }}</p>
                    @endif
                </td>
                <td class="hidden whitespace-nowrap px-5 py-3 text-ink/70 xl:table-cell">
                    <p class="text-xs font-medium text-ink">{{ $cotizacion->vendedor_nombres }} {{ $cotizacion->vendedor_apellidos }}</p>
                    <p class="text-[11px] text-ink/45">{{ $cotizacion->sucursal_nombre }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-medium {{ $estadosClases[$cotizacion->estado] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $estadosNombres[$cotizacion->estado] ?? ucfirst($cotizacion->estado) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Ver detalle modal --}}
                        <button
                            type="button"
                            data-modal-open="cotizacion-detalle-{{ $cotizacion->id }}"
                            title="Ver detalle"
                            aria-label="Ver detalle de {{ $cotizacion->codigo }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573 3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        {{-- Editar (si no está convertida a venta) --}}
                        @if (! $esConvertido)
                            <a
                                href="{{ route('cotizaciones.edit', $cotizacion->id) }}"
                                title="Editar"
                                aria-label="Editar {{ $cotizacion->codigo }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/30"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </a>
                        @endif

                        {{-- Eliminar (habilitado si está en borrador, rechazada o caducada) --}}
                        @if ($esEliminable)
                            <form
                                method="POST"
                                action="{{ route('cotizaciones.destroy', $cotizacion->id) }}"
                                data-eliminar-cotizacion
                                data-cotizacion-codigo="{{ $cotizacion->codigo }}"
                                class="inline-flex"
                            >
                                @csrf
                                @method('DELETE')
                                @foreach ($parametrosListado as $parametro => $valor)
                                    <input type="hidden" name="{{ $parametro }}" value="{{ $valor }}">
                                @endforeach
                                <button
                                    type="submit"
                                    title="Eliminar"
                                    aria-label="Eliminar {{ $cotizacion->codigo }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-400/40"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49.208 49.208 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-14 text-center">
                    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background text-ink/35">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">No se encontraron cotizaciones</p>
                            <p class="mt-1 text-xs text-ink/45">
                                {{ $buscar !== '' ? 'Prueba con otro término de búsqueda o cambia los filtros.' : 'Genera la primera cotización para clientes o prospectos.' }}
                            </p>
                        </div>
                        @if ($buscar === '')
                            <a href="{{ route('cotizaciones.create') }}" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40">
                                Nueva cotización
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($cotizaciones->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $cotizaciones, 'etiqueta' => 'cotizaciones'])
    </div>
@endif

{{-- Modales de Detalle --}}
@foreach ($cotizaciones as $cotizacion)
    @php
        $destinatario = $cotizacion->cliente_id
            ? ($cotizacion->cliente_razon_social ?: trim($cotizacion->cliente_nombres . ' ' . $cotizacion->cliente_apellidos))
            : trim($cotizacion->prospecto_nombres . ' ' . $cotizacion->prospecto_apellidos);
        $totalCotizado = max(0, (float) $cotizacion->subtotal_servicios - (float) $cotizacion->descuento_monto);
    @endphp
    <dialog
        id="cotizacion-detalle-{{ $cotizacion->id }}"
        class="m-auto w-[min(94vw,52rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
        aria-labelledby="cotizacion-detalle-titulo-{{ $cotizacion->id }}"
    >
        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ficha de cotización</p>
                <h2 id="cotizacion-detalle-titulo-{{ $cotizacion->id }}" class="truncate text-lg font-semibold text-ink">
                    Cotización {{ $cotizacion->codigo }}
                </h2>
                <p class="mt-0.5 text-xs text-ink/50">
                    Cliente/Prospecto: <strong class="text-ink/80">{{ $destinatario }}</strong> ·
                    Estado: <strong class="text-primary">{{ $estadosNombres[$cotizacion->estado] ?? ucfirst($cotizacion->estado) }}</strong>
                </p>
            </div>
            <button
                type="button"
                data-modal-close
                aria-label="Cerrar detalle"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink/45 transition hover:bg-background hover:text-ink focus:outline-none focus:ring-2 focus:ring-secondary/40"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-5">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3 text-xs">
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Destinatario</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-ink">{{ $destinatario }}</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Paquete base</dt>
                    <dd class="mt-0.5 text-sm text-ink">{{ $cotizacion->paquete_nombre ?: 'Sin paquete (itinerario libre)' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Viajeros (Pax)</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-primary">{{ $cotizacion->numero_viajeros }} persona(s)</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Fecha emisión</dt>
                    <dd class="mt-0.5 text-ink">{{ \Carbon\Carbon::parse($cotizacion->fecha_emision)->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Fecha caducidad</dt>
                    <dd class="mt-0.5 font-semibold text-ink">{{ \Carbon\Carbon::parse($cotizacion->fecha_caducidad)->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Fechas de viaje</dt>
                    <dd class="mt-0.5 text-ink">
                        {{ $cotizacion->fecha_viaje_inicio ? \Carbon\Carbon::parse($cotizacion->fecha_viaje_inicio)->format('d/m/Y') : '—' }}
                        @if ($cotizacion->fecha_viaje_fin)
                            al {{ \Carbon\Carbon::parse($cotizacion->fecha_viaje_fin)->format('d/m/Y') }}
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Asesor / Vendedor</dt>
                    <dd class="mt-0.5 text-ink">{{ $cotizacion->vendedor_nombres }} {{ $cotizacion->vendedor_apellidos }}</dd>
                </div>
                <div>
                    <dt class="font-semibold uppercase tracking-wider text-ink/45 text-[10px]">Sucursal</dt>
                    <dd class="mt-0.5 text-ink">{{ $cotizacion->sucursal_nombre }}</dd>
                </div>
            </dl>

            <div class="rounded-xl border border-ink/10 bg-background/50 p-4">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-ink/60">Subtotal de servicios:</span>
                    <span class="font-mono font-medium">${{ number_format($cotizacion->subtotal_servicios, 2) }}</span>
                </div>
                @if ((float) $cotizacion->descuento_monto > 0)
                    <div class="flex items-center justify-between text-xs text-rose-600 mb-1">
                        <span>Descuento aplicado:</span>
                        <span class="font-mono font-medium">-${{ number_format($cotizacion->descuento_monto, 2) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-base font-bold text-ink border-t border-ink/10 pt-2 mt-2">
                    <span>Monto total cotizado:</span>
                    <span class="font-mono text-primary">${{ number_format($totalCotizado, 2) }}</span>
                </div>
            </div>

            @if ($cotizacion->notas)
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-ink/45 mb-1">Notas / Condiciones comerciales</p>
                    <p class="text-xs text-ink/80 whitespace-pre-line bg-background/30 p-3 rounded-xl border border-ink/10">
                        {{ $cotizacion->notas }}
                    </p>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-ink/10 px-6 py-4 bg-background/30">
            <button
                type="button"
                data-modal-close
                class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
            >
                Cerrar
            </button>
            @if ($cotizacion->estado !== 'convertido')
                <a
                    href="{{ route('cotizaciones.edit', $cotizacion->id) }}"
                    class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                >
                    Editar
                </a>
            @endif
        </div>
    </dialog>
@endforeach
