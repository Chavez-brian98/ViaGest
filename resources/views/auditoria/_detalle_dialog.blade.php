@php
    $diff = \App\Service\Auditoria\AuditoriaService::calcularDiff($registro->datos_anteriores, $registro->datos_nuevos);
    $badgeAccion = [
        'crear' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'actualizar' => 'bg-amber-100 text-amber-800 border-amber-200',
        'eliminar' => 'bg-rose-100 text-rose-800 border-rose-200',
        'inicio_sesion' => 'bg-secondary/15 text-primary border-secondary/20',
        'cierre_sesion' => 'bg-ink/10 text-ink/70 border-ink/15',
        'exportar' => 'bg-blue-100 text-blue-800 border-blue-200',
    ];
    $claseBadge = $badgeAccion[$registro->accion] ?? 'bg-ink/10 text-ink/70 border-ink/15';

    $formatearValor = function ($val) {
        if (is_null($val)) {
            return '—';
        }
        if (is_bool($val)) {
            return $val ? 'true' : 'false';
        }
        if (is_array($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        return (string) $val;
    };
@endphp

<dialog
    id="auditoria-detalle-{{ $registro->id }}"
    class="m-auto w-[min(92vw,48rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
    aria-labelledby="auditoria-detalle-titulo-{{ $registro->id }}"
>
    <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Detalle de auditoría #{{ $registro->id }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $claseBadge }}">
                    {{ \App\Service\Auditoria\AuditoriaService::etiquetaAccion($registro->accion) }}
                </span>
                <span class="rounded-lg bg-background px-2 py-0.5 text-xs font-semibold text-ink/80 border border-ink/10">
                    {{ \App\Service\Auditoria\AuditoriaService::etiquetaTabla($registro->tabla) }}
                </span>
                @if ($registro->registro_id)
                    <span class="text-xs font-mono text-ink/60">ID Reg: {{ $registro->registro_id }}</span>
                @endif
            </div>
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

    <div class="max-h-[70vh] overflow-y-auto px-6 py-5 space-y-6">
        {{-- Metadata del evento --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3 rounded-xl border border-ink/10 bg-background/50 p-4 text-xs">
            <div>
                <dt class="font-semibold text-ink/45 uppercase tracking-wider text-[10px]">Empleado responsable</dt>
                <dd class="mt-1 font-medium text-ink">
                    @if ($registro->empleado)
                        {{ $registro->empleado->nombreCompleto() }} ({{ $registro->empleado->codigo }})
                    @else
                        Sistema / Sin usuario
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-semibold text-ink/45 uppercase tracking-wider text-[10px]">Dirección IP</dt>
                <dd class="mt-1 font-mono text-ink">{{ $registro->ip ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-ink/45 uppercase tracking-wider text-[10px]">Fecha y hora</dt>
                <dd class="mt-1 text-ink">{{ $registro->created_at?->format('d/m/Y H:i:s') }}</dd>
            </div>
        </dl>

        {{-- Cambios realizados (Diff) --}}
        <div>
            <h3 class="text-xs font-semibold uppercase tracking-wider text-ink/60 mb-2">
                Cambios detectados ({{ count($diff) }})
            </h3>

            @if (count($diff) > 0)
                <div class="overflow-x-auto rounded-xl border border-ink/10">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-background border-b border-ink/10 text-ink/50 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Campo modificado</th>
                                <th class="px-4 py-2.5 font-semibold">Valor anterior</th>
                                <th class="px-4 py-2.5 font-semibold">Valor nuevo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/5">
                            @foreach ($diff as $campo => $valores)
                                <tr class="hover:bg-background/40">
                                    <td class="px-4 py-2 font-mono font-medium text-ink/80 whitespace-nowrap bg-background/20">
                                        {{ $campo }}
                                    </td>
                                    <td class="px-4 py-2 font-mono text-rose-700 bg-rose-50/40 break-all max-w-[14rem]">
                                        {{ $formatearValor($valores['antes']) }}
                                    </td>
                                    <td class="px-4 py-2 font-mono text-emerald-700 bg-emerald-50/40 break-all max-w-[14rem]">
                                        {{ $formatearValor($valores['despues']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-xl border border-ink/10 bg-background/40 p-4 text-center text-xs text-ink/45">
                    No hay cambios de datos registrados para esta acción (ej. inicio/cierre de sesión o sin diferencias).
                </div>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-ink/10 px-6 py-4">
        <button
            type="button"
            data-modal-close
            class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cerrar
        </button>
    </div>
</dialog>
