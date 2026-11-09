@php
    $parametrosListado = $parametrosListado ?? [];
    $tipoEtiquetas = [
        'nacional' => 'Nacional',
        'internacional' => 'Internacional',
    ];
    $tipoBadges = [
        'nacional' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'internacional' => 'bg-blue-50 text-blue-700 border-blue-200/60',
    ];
    $estadoBadges = [
        'activo' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'borrador' => 'bg-amber-50 text-amber-700 border-amber-200',
        'inactivo' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $estadoEtiquetas = [
        'activo' => 'Activo',
        'borrador' => 'Borrador',
        'inactivo' => 'Inactivo',
    ];
    $formatoMoneda = fn ($valor) => '$' . number_format((float) $valor, 2);
@endphp

<table class="w-full min-w-[56rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="whitespace-nowrap px-5 py-3">Código</th>
            <th class="px-5 py-3">Paquete / Destino</th>
            <th class="whitespace-nowrap px-5 py-3">Tipo</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Duración</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Servicios</th>
            <th class="whitespace-nowrap px-5 py-3 text-right">Costo neto</th>
            <th class="whitespace-nowrap px-5 py-3 text-right">Precio venta</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($paquetes as $paquete)
            <tr class="transition-colors hover:bg-background/70">
                <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-semibold text-ink/70">
                    {{ $paquete->codigo }}
                </td>
                <td class="px-5 py-3">
                    <div class="font-medium text-ink">{{ $paquete->nombre }}</div>
                    <div class="text-xs text-ink/45 flex items-center gap-1.5 mt-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 text-ink/40">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        <span>{{ $paquete->destino_nombre ? $paquete->destino_nombre . ' (' . $paquete->destino_pais . ')' : 'Sin destino asignado' }}</span>
                    </div>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-medium {{ $tipoBadges[$paquete->tipo] ?? 'bg-ink/5 text-ink/70 border-ink/10' }}">
                        {{ $tipoEtiquetas[$paquete->tipo] ?? ucfirst($paquete->tipo) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center text-xs text-ink/70">
                    <span class="font-medium text-ink">{{ $paquete->duracion_dias }}D / {{ $paquete->duracion_noches }}N</span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center">
                    <span class="inline-flex items-center justify-center rounded-full bg-ink/5 px-2.5 py-0.5 text-xs font-semibold text-ink/70">
                        {{ $paquete->servicios_count }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-ink/60">
                    {{ $formatoMoneda($paquete->costo_neto_total) }}
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-right">
                    <span class="font-semibold text-emerald-700">
                        {{ $formatoMoneda($paquete->precio_venta) }}
                    </span>
                    <div class="text-[10px] text-ink/40">
                        Margen: {{ $paquete->margen_tipo === 'porcentaje' ? '+' . number_format($paquete->margen_valor, 0) . '%' : '+$' . number_format($paquete->margen_valor, 2) }}
                    </div>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $estadoBadges[$paquete->estado] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $estadoEtiquetas[$paquete->estado] ?? ucfirst($paquete->estado) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-right">
                    <div class="inline-flex items-center justify-end gap-1">
                        {{-- Dropdown / Cambiar estado rápido --}}
                        <div class="relative inline-block text-left">
                            <button
                                type="button"
                                data-btn-cambiar-estado
                                data-id="{{ $paquete->id }}"
                                data-estado-actual="{{ $paquete->estado }}"
                                data-url="{{ route('paquetes.estado', $paquete->id) }}"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-ink/10 bg-surface text-ink/60 transition hover:bg-background hover:text-ink focus:outline-none"
                                title="Cambiar estado"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </button>
                        </div>

                        {{-- Editar --}}
                        <a
                            href="{{ route('paquetes.edit', $paquete->id) }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-ink/10 bg-surface text-ink/60 transition hover:bg-background hover:text-ink focus:outline-none"
                            title="Editar paquete"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>

                        {{-- Eliminar --}}
                        <form
                            action="{{ route('paquetes.destroy', $paquete->id) }}"
                            method="POST"
                            class="inline-block"
                            data-form-eliminar
                            data-nombre="{{ $paquete->codigo }} - {{ $paquete->nombre }}"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200/70 bg-rose-50/50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700 focus:outline-none"
                                title="Eliminar paquete"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="px-5 py-12 text-center text-ink/45">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="h-10 w-10 text-ink/30">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                        <p class="text-sm">No se encontraron paquetes registrados con los criterios seleccionados.</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($paquetes->hasPages())
    <div class="border-t border-ink/10 px-5 py-3">
        {{ $paquetes->appends($parametrosListado)->links('partials.paginacion') }}
    </div>
@endif
