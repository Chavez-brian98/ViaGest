@php
    $parametrosListado = $parametrosListado ?? [];
    $tipoEtiquetas = [
        'bloqueo' => 'Bloqueo',
        'compra_adelantada' => 'Compra adelantada',
    ];
    $tipoBadges = [
        'bloqueo' => 'bg-amber-50 text-amber-700 border-amber-200/60',
        'compra_adelantada' => 'bg-blue-50 text-blue-700 border-blue-200/60',
    ];
    $estadoBadges = [
        'activo' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'liberado' => 'bg-amber-50 text-amber-700 border-amber-200',
        'cerrado' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $estadoEtiquetas = [
        'activo' => 'Activo',
        'liberado' => 'Liberado',
        'cerrado' => 'Cerrado',
    ];
    $mostrar = fn ($valor) => filled($valor) ? $valor : '—';
@endphp

<table class="w-full min-w-[56rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="px-5 py-3">Servicio / Proveedor</th>
            <th class="whitespace-nowrap px-5 py-3">Tipo</th>
            <th class="whitespace-nowrap px-5 py-3">Vigencia</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Total</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Vendido</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Disponible</th>
            <th class="whitespace-nowrap px-5 py-3">F. Liberación</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($cupos as $cupo)
            <tr class="transition-colors hover:bg-background/70">
                <td class="px-5 py-3">
                    <div class="font-medium text-ink">{{ $cupo->servicio_nombre }}</div>
                    <div class="text-xs text-ink/45 flex items-center gap-1.5 mt-0.5">
                        <span class="font-mono text-ink/60">{{ $cupo->servicio_codigo }}</span>
                        <span>•</span>
                        <span>{{ $cupo->proveedor_nombre ?? 'Sin proveedor' }}</span>
                    </div>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-medium {{ $tipoBadges[$cupo->tipo] ?? 'bg-ink/5 text-ink/70 border-ink/10' }}">
                        {{ $tipoEtiquetas[$cupo->tipo] ?? ucfirst($cupo->tipo) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-xs text-ink/70">
                    <div><span class="text-ink/40">Del:</span> {{ \Carbon\Carbon::parse($cupo->fecha_inicio)->format('d/m/Y') }}</div>
                    <div><span class="text-ink/40">Al:</span> {{ \Carbon\Carbon::parse($cupo->fecha_fin)->format('d/m/Y') }}</div>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center font-medium text-ink">
                    {{ $cupo->cantidad_total }}
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center font-medium {{ (int)$cupo->vendido > 0 ? 'text-primary' : 'text-ink/45' }}">
                    {{ (int) $cupo->vendido }}
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center">
                    @php $disponible = (int) $cupo->disponible; @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $disponible > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $disponible }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-xs text-ink/70">
                    @if ($cupo->fecha_liberacion)
                        @php
                            $diasParaLiberar = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($cupo->fecha_liberacion), false);
                        @endphp
                        <div>{{ \Carbon\Carbon::parse($cupo->fecha_liberacion)->format('d/m/Y') }}</div>
                        @if ($cupo->estado === 'activo')
                            @if ($diasParaLiberar < 0)
                                <span class="text-[10px] font-medium text-rose-600">Vencida</span>
                            @elseif ($diasParaLiberar <= 3)
                                <span class="text-[10px] font-medium text-amber-600">Vence en {{ $diasParaLiberar }} d</span>
                            @endif
                        @endif
                    @else
                        <span class="text-ink/40">—</span>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $estadoBadges[$cupo->estado] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $estadoEtiquetas[$cupo->estado] ?? ucfirst($cupo->estado) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-right">
                    <div class="inline-flex items-center justify-end gap-1">
                        {{-- Dropdown / Cambiar estado rápido --}}
                        <div class="relative inline-block text-left" data-dropdown-wrapper>
                            <button
                                type="button"
                                data-btn-cambiar-estado
                                data-id="{{ $cupo->id }}"
                                data-estado-actual="{{ $cupo->estado }}"
                                data-url="{{ route('cupos.estado', $cupo->id) }}"
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
                            href="{{ route('cupos.edit', $cupo->id) }}"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-ink/10 bg-surface text-ink/60 transition hover:bg-background hover:text-ink focus:outline-none"
                            title="Editar cupo"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>

                        {{-- Eliminar --}}
                        <form
                            action="{{ route('cupos.destroy', $cupo->id) }}"
                            method="POST"
                            class="inline-block"
                            data-form-eliminar
                            data-nombre="Cupo para {{ $cupo->servicio_nombre }} ({{ $cupo->cantidad_total }} espacios)"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200/70 bg-rose-50/50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700 focus:outline-none"
                                title="Eliminar cupo"
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                        </svg>
                        <p class="text-sm">No se encontraron cupos registrados con los criterios seleccionados.</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($cupos->hasPages())
    <div class="border-t border-ink/10 px-5 py-3">
        {{ $cupos->appends($parametrosListado)->links('partials.paginacion') }}
    </div>
@endif
