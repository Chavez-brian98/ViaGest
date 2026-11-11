@php
    $parametrosListado = $parametrosListado ?? [];
    $mostrar = fn ($valor) => filled($valor) ? $valor : '—';
    $estadosClases = [
        'nuevo' => 'bg-sky-50 text-sky-700 border-sky-200',
        'contactado' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'cotizado' => 'bg-amber-50 text-amber-700 border-amber-200',
        'convertido' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'perdido' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $estadosNombres = \App\Service\Prospectos\ProspectoService::ESTADOS;
    $origenesNombres = \App\Service\Prospectos\ProspectoService::ORIGENES;
    $motivosNombres = \App\Service\Prospectos\ProspectoService::MOTIVOS_PERDIDA;
@endphp

<table class="w-full min-w-[50rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="whitespace-nowrap px-5 py-3">Código</th>
            <th class="px-5 py-3">Prospecto</th>
            <th class="hidden px-5 py-3 md:table-cell">Contacto</th>
            <th class="hidden px-5 py-3 lg:table-cell">Interés / Viaje</th>
            <th class="hidden whitespace-nowrap px-5 py-3 xl:table-cell">Vendedor / Sucursal</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($prospectos as $prospecto)
            @php
                $esPerdido = $prospecto->estado === 'perdido';
                $esConvertido = $prospecto->estado === 'convertido';
                $nombreCompleto = trim($prospecto->nombres . ' ' . ($prospecto->apellidos ?? ''));
                $iniciales = Str::upper(Str::substr($prospecto->nombres, 0, 1));
            @endphp
            <tr class="transition-colors hover:bg-background/70" data-fila-prospecto="{{ $prospecto->id }}">
                <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-medium text-ink/60">
                    {{ $prospecto->codigo }}
                </td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-sm font-semibold text-primary">
                            {{ $iniciales }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">{{ $nombreCompleto }}</p>
                            <p class="text-xs text-ink/45">
                                Origen: {{ $origenesNombres[$prospecto->origen] ?? ucfirst($prospecto->origen) }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="hidden px-5 py-3 md:table-cell">
                    <p class="max-w-[14rem] truncate text-ink/70">{{ $prospecto->email ?: 'Sin correo' }}</p>
                    <p class="text-xs text-ink/45">{{ $prospecto->telefono ?: 'Sin teléfono' }}</p>
                </td>
                <td class="hidden px-5 py-3 lg:table-cell">
                    <div class="space-y-0.5">
                        <p class="text-xs font-medium text-ink">
                            Destino: <span class="font-semibold text-primary">{{ $prospecto->pais_destino ?? 'No especificado' }}</span>
                            @if ($prospecto->numero_viajeros)
                                <span class="text-ink/45 font-normal">({{ $prospecto->numero_viajeros }} pax)</span>
                            @endif
                        </p>
                        @if ($prospecto->proximo_seguimiento)
                            <p class="text-[11px] text-amber-700 font-medium flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3 w-3 inline">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Seguimiento: {{ \Carbon\Carbon::parse($prospecto->proximo_seguimiento)->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>
                </td>
                <td class="hidden whitespace-nowrap px-5 py-3 text-ink/70 xl:table-cell">
                    <p class="text-xs font-medium text-ink">{{ $prospecto->vendedor_nombres }} {{ $prospecto->vendedor_apellidos }}</p>
                    <p class="text-[11px] text-ink/45">{{ $prospecto->sucursal_nombre }}</p>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-medium {{ $estadosClases[$prospecto->estado] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                        {{ $estadosNombres[$prospecto->estado] ?? ucfirst($prospecto->estado) }}
                    </span>
                    @if ($esPerdido && $prospecto->motivo_perdida)
                        <p class="text-[10px] text-rose-600 mt-0.5">{{ $motivosNombres[$prospecto->motivo_perdida] ?? $prospecto->motivo_perdida }}</p>
                    @endif
                    @if ($esConvertido && $prospecto->cliente_codigo)
                        <p class="text-[10px] text-emerald-700 mt-0.5 font-mono">Cliente: {{ $prospecto->cliente_codigo }}</p>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Botón convertir a cliente si aún no está convertido --}}
                        @if (! $esConvertido)
                            <button
                                type="button"
                                data-convertir-prospecto="{{ $prospecto->id }}"
                                data-prospecto-nombre="{{ $nombreCompleto }}"
                                data-prospecto-codigo="{{ $prospecto->codigo }}"
                                title="Convertir a cliente"
                                aria-label="Convertir {{ $nombreCompleto }} en cliente"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400/40"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.765z" />
                                </svg>
                            </button>
                        @endif

                        {{-- Ver detalle modal --}}
                        <button
                            type="button"
                            data-modal-open="prospecto-detalle-{{ $prospecto->id }}"
                            title="Ver detalle"
                            aria-label="Ver detalle de {{ $nombreCompleto }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573 3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        {{-- Editar --}}
                        <a
                            href="{{ route('prospectos.edit', $prospecto->id) }}"
                            title="Editar"
                            aria-label="Editar {{ $nombreCompleto }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/30"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>

                        {{-- Eliminar (habilitado solo si está perdido) --}}
                        @if ($esPerdido)
                            <form
                                method="POST"
                                action="{{ route('prospectos.destroy', $prospecto->id) }}"
                                data-eliminar-prospecto
                                data-prospecto-nombre="{{ $nombreCompleto }}"
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
                                    aria-label="Eliminar {{ $nombreCompleto }}"
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
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">No se encontraron prospectos</p>
                            <p class="mt-1 text-xs text-ink/45">
                                {{ $buscar !== '' ? 'Prueba con otro término de búsqueda o cambia los filtros.' : 'Registra el primer prospecto para comenzar el seguimiento comercial.' }}
                            </p>
                        </div>
                        @if ($buscar === '')
                            <a href="{{ route('prospectos.create') }}" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40">
                                Nuevo prospecto
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($prospectos->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $prospectos, 'etiqueta' => 'prospectos'])
    </div>
@endif

{{-- Modales de Detalle --}}
@foreach ($prospectos as $prospecto)
    @php
        $nombreCompleto = trim($prospecto->nombres . ' ' . ($prospecto->apellidos ?? ''));
    @endphp
    <dialog
        id="prospecto-detalle-{{ $prospecto->id }}"
        class="m-auto w-[min(92vw,46rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
        aria-labelledby="prospecto-detalle-titulo-{{ $prospecto->id }}"
    >
        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ficha de prospecto</p>
                <h2 id="prospecto-detalle-titulo-{{ $prospecto->id }}" class="truncate text-lg font-semibold text-ink">
                    {{ $nombreCompleto }}
                </h2>
                <p class="mt-0.5 text-xs text-ink/50">
                    {{ $prospecto->codigo }} · Estado: <strong class="text-primary">{{ $estadosNombres[$prospecto->estado] ?? ucfirst($prospecto->estado) }}</strong>
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

        <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Nombres y Apellidos</dt>
                    <dd class="mt-1 text-sm font-medium text-ink">{{ $nombreCompleto }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Origen del prospecto</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $origenesNombres[$prospecto->origen] ?? ucfirst($prospecto->origen) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Correo electrónico</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($prospecto->email) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Teléfono</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($prospecto->telefono) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Destino de interés</dt>
                    <dd class="mt-1 text-sm font-medium text-primary">{{ $mostrar($prospecto->pais_destino) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Número de viajeros</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $prospecto->numero_viajeros ? $prospecto->numero_viajeros . ' persona(s)' : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Fecha de viaje estimada</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $prospecto->fecha_viaje_estimada ? \Carbon\Carbon::parse($prospecto->fecha_viaje_estimada)->format('d/m/Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Próximo seguimiento</dt>
                    <dd class="mt-1 text-sm font-semibold text-amber-700">{{ $prospecto->proximo_seguimiento ? \Carbon\Carbon::parse($prospecto->proximo_seguimiento)->format('d/m/Y') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Vendedor asignado</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $prospecto->vendedor_nombres }} {{ $prospecto->vendedor_apellidos }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Sucursal</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $prospecto->sucursal_nombre }}</dd>
                </div>

                @if ($prospecto->estado === 'perdido')
                    <div class="sm:col-span-2 rounded-xl bg-rose-50 p-3 border border-rose-200">
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-rose-800">Motivo de pérdida</dt>
                        <dd class="mt-1 text-sm font-medium text-rose-700">
                            {{ $motivosNombres[$prospecto->motivo_perdida] ?? $prospecto->motivo_perdida ?? 'No especificado' }}
                        </dd>
                    </div>
                @endif

                @if ($prospecto->estado === 'convertido')
                    <div class="sm:col-span-2 rounded-xl bg-emerald-50 p-3 border border-emerald-200">
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">Cliente asociado</dt>
                        <dd class="mt-1 text-sm font-medium text-emerald-700 flex items-center gap-2">
                            <span>Código: <strong>{{ $prospecto->cliente_codigo }}</strong></span>
                            @if ($prospecto->cliente_id)
                                <a href="{{ route('clientes.index', ['buscar' => $prospecto->cliente_codigo]) }}" class="underline hover:text-emerald-900 text-xs font-semibold">
                                    Ver en directorio de clientes →
                                </a>
                            @endif
                        </dd>
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Notas y requerimientos</dt>
                    <dd class="mt-1 text-sm text-ink/80 whitespace-pre-line bg-background/50 p-3 rounded-xl border border-ink/10">
                        {{ $prospecto->notas ?: 'Sin notas registradas.' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-ink/10 px-6 py-4 bg-background/30">
            <button
                type="button"
                data-modal-close
                class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
            >
                Cerrar
            </button>
            <a
                href="{{ route('prospectos.edit', $prospecto->id) }}"
                class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
            >
                Editar
            </a>
        </div>
    </dialog>
@endforeach
