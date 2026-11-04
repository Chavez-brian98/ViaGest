@php
    $parametrosListado = $parametrosListado ?? [];

    $badgeAccion = [
        'crear' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'actualizar' => 'bg-amber-100 text-amber-800 border-amber-200',
        'eliminar' => 'bg-rose-100 text-rose-800 border-rose-200',
        'inicio_sesion' => 'bg-secondary/15 text-primary border-secondary/20',
        'cierre_sesion' => 'bg-ink/10 text-ink/70 border-ink/15',
        'exportar' => 'bg-blue-100 text-blue-800 border-blue-200',
    ];
@endphp

<table class="w-full min-w-[52rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="whitespace-nowrap px-5 py-3">Fecha y hora</th>
            <th class="px-5 py-3">Empleado</th>
            <th class="whitespace-nowrap px-5 py-3">Acción</th>
            <th class="whitespace-nowrap px-5 py-3">Módulo</th>
            <th class="hidden whitespace-nowrap px-5 py-3 md:table-cell">Registro ID</th>
            <th class="hidden whitespace-nowrap px-5 py-3 lg:table-cell">IP</th>
            <th class="px-5 py-3 text-right">Detalle</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($registros as $registro)
            @php
                $claseBadge = $badgeAccion[$registro->accion] ?? 'bg-ink/10 text-ink/70 border-ink/15';
                $diff = \App\Service\Auditoria\AuditoriaService::calcularDiff($registro->datos_anteriores, $registro->datos_nuevos);
                $tieneDetalles = count($diff) > 0 || !empty($registro->ip);
            @endphp
            <tr class="transition-colors hover:bg-background/70">
                <td class="whitespace-nowrap px-5 py-3 text-xs font-medium text-ink/70">
                    <span class="block text-ink font-semibold">{{ $registro->created_at?->format('d/m/Y') }}</span>
                    <span class="text-ink/45">{{ $registro->created_at?->format('H:i:s') }}</span>
                </td>
                <td class="px-5 py-3">
                    @if ($registro->empleado)
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-xs font-semibold text-primary">
                                {{ $registro->empleado->iniciales() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ink">{{ $registro->empleado->nombreCompleto() }}</p>
                                <p class="text-xs text-ink/45 font-mono">{{ $registro->empleado->codigo }}</p>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-ink/45 italic text-xs">
                            <span class="inline-block h-2 w-2 rounded-full bg-ink/30"></span>
                            Sistema / Anónimo
                        </div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $claseBadge }}">
                        {{ \App\Service\Auditoria\AuditoriaService::etiquetaAccion($registro->accion) }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-xs font-medium text-ink/80">
                    <span class="rounded-lg bg-background px-2 py-1 border border-ink/10">
                        {{ \App\Service\Auditoria\AuditoriaService::etiquetaTabla($registro->tabla) }}
                    </span>
                </td>
                <td class="hidden whitespace-nowrap px-5 py-3 font-mono text-xs text-ink/60 md:table-cell">
                    {{ $registro->registro_id ?? '—' }}
                </td>
                <td class="hidden whitespace-nowrap px-5 py-3 font-mono text-xs text-ink/50 lg:table-cell">
                    {{ $registro->ip ?? '—' }}
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-right">
                    <button
                        type="button"
                        data-modal-open="auditoria-detalle-{{ $registro->id }}"
                        title="Ver detalle del cambio"
                        aria-label="Ver detalle del registro {{ $registro->id }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-14 text-center">
                    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background text-ink/35">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">No se encontraron eventos de auditoría</p>
                            <p class="mt-1 text-xs text-ink/45">
                                {{ $buscar !== '' ? 'Prueba con otro término de búsqueda o limpia los filtros.' : 'No hay registros en la bitácora actualmente.' }}
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($registros->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $registros, 'etiqueta' => 'registros'])
    </div>
@endif

@foreach ($registros as $registro)
    @include('auditoria._detalle_dialog', ['registro' => $registro])
@endforeach
