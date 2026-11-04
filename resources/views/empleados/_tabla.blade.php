@php
    $parametrosListado = $parametrosListado ?? [];
    $documentoEtiquetas = [
        'dui' => 'DUI',
        'nit' => 'NIT',
        'carnet_residente' => 'Carnet de residente',
        'otro' => 'Otro',
    ];
    $mostrar = fn ($valor) => filled($valor) ? $valor : '—';
@endphp

<table class="w-full min-w-[46rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="whitespace-nowrap px-5 py-3">Código</th>
            <th class="px-5 py-3">Empleado</th>
            <th class="hidden whitespace-nowrap px-5 py-3 md:table-cell">Documento</th>
            <th class="hidden px-5 py-3 lg:table-cell">Contacto / Cargo</th>
            <th class="hidden whitespace-nowrap px-5 py-3 xl:table-cell">Sucursal</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($empleados as $empleado)
            @php $estadoActivo = $empleado->estado === 'activo'; @endphp
            <tr class="transition-colors hover:bg-background/70" data-fila-empleado="{{ $empleado->id }}">
                <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-medium text-ink/60">{{ $empleado->codigo }}</td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-sm font-semibold text-primary">
                            {{ Str::upper(Str::substr($empleado->nombreCompleto(), 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">{{ $empleado->nombreCompleto() }}</p>
                            <p class="text-xs text-ink/45">{{ $empleado->cargo ?? 'Sin cargo asignado' }}</p>
                        </div>
                    </div>
                </td>
                <td class="hidden px-5 py-3 md:table-cell">
                    @if ($empleado->numero_documento)
                        <p class="whitespace-nowrap font-medium text-ink/80">{{ $documentoEtiquetas[$empleado->tipo_documento] ?? 'Documento' }}</p>
                        <p class="whitespace-nowrap text-xs text-ink/45">{{ $empleado->numero_documento }}</p>
                    @else
                        <span class="text-ink/35">—</span>
                    @endif
                </td>
                <td class="hidden px-5 py-3 lg:table-cell">
                    <p class="max-w-[14rem] truncate text-ink/70">{{ $empleado->email ?: 'Sin correo' }}</p>
                    <p class="text-xs text-ink/45">{{ $empleado->telefono ?: 'Sin teléfono' }}</p>
                </td>
                <td class="hidden whitespace-nowrap px-5 py-3 text-ink/70 xl:table-cell">{{ $empleado->sucursal?->nombre ?? '—' }}</td>
                <td class="whitespace-nowrap px-5 py-3">
                    <div class="flex items-center gap-2">
                        <form
                            method="POST"
                            action="{{ route('empleados.estado', $empleado) }}"
                            class="inline-flex"
                            data-estado-form
                            data-estado-actual="{{ $empleado->estado }}"
                            data-empleado-nombre="{{ $empleado->nombreCompleto() }}"
                        >
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado_destino" value="{{ $estadoActivo ? 'inactivo' : 'activo' }}">
                            @foreach ($parametrosListado as $parametro => $valor)
                                <input type="hidden" name="{{ $parametro }}" value="{{ $valor }}">
                            @endforeach
                            <button
                                type="submit"
                                role="switch"
                                data-estado-switch
                                aria-checked="{{ $estadoActivo ? 'true' : 'false' }}"
                                title="{{ $estadoActivo ? 'Desactivar' : 'Activar' }}"
                                aria-label="{{ $estadoActivo ? 'Desactivar' : 'Activar' }} {{ $empleado->nombreCompleto() }}"
                                class="estado-switch"
                            >
                                <span class="estado-texto" data-estado-texto data-estado="{{ $empleado->estado }}">
                                    {{ $estadoActivo ? 'Activo' : 'Inactivo' }}
                                </span>
                                <span class="estado-switch-dot" aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                        <button
                            type="button"
                            data-modal-open="empleado-detalle-{{ $empleado->id }}"
                            title="Ver detalle"
                            aria-label="Ver detalle de {{ $empleado->nombreCompleto() }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573 3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>

                        <a
                            href="{{ route('empleados.edit', $empleado) }}"
                            title="Editar"
                            aria-label="Editar {{ $empleado->nombreCompleto() }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-primary/10 hover:text-primary focus:outline-none focus:ring-ssecondary/30"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </a>

                        <form
                            method="POST"
                            action="{{ route('empleados.destroy', $empleado) }}"
                            data-confirm="delete"
                            data-eliminar-empleado
                            data-empleado-nombre="{{ $empleado->nombreCompleto() }}"
                            class="inline-flex {{ $estadoActivo ? 'hidden' : '' }}"
                        >
                            @csrf
                            @method('DELETE')
                            @foreach ($parametrosListado as $parametro => $valor)
                                <input type="hidden" name="{{ $parametro }}" value="{{ $valor }}">
                            @endforeach
                            <button
                                type="submit"
                                title="Eliminar"
                                aria-label="Eliminar {{ $empleado->nombreCompleto() }}"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-rose-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-rose-400/40"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" none viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49.208 49.208 0 00-7.5 0" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-5 py-14 text-center">
                    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background text-ink/35">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-ink">No se encontraron empleados</p>
                                <p class="mt-1 text-xs text-ink/45">
                                    {{ $buscar !== '' ? 'Prueba con otro término de búsqueda.' : 'Agrega el primer empleado para comenzar.' }}
                                </p>
                            </div>
                            @if ($buscar === '')
                                <a href="{{ route('empleados.create') }}" class="rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40">
                                    Nuevo empleado
                                </a>
                            @endif
                        </div>
                    </tr>
        @endforelse
    </tbody>
</table>

@if ($empleados->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $empleados, 'etiqueta' => 'empleados'])
    </div>
@endif

@foreach ($empleados as $empleado)
    <dialog
        id="empleado-detalle-{{ $empleado->id }}"
        class="m-auto w-[min(92vw,48rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
        aria-labelledby="empleado-detalle-titulo-{{ $empleado->id }}"
    >
        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Detalle del empleado</p>
                <h2 id="empleado-detalle-titulo-{{ $empleado->id }}" class="truncate text-lg font-semibold text-ink">{{ $empleado->nombreCompleto() }}</h2>
                <p class="mt-1 text-xs text-ink/45">
                    {{ $empleado->codigo }} · {{ $empleado->cargo ?? 'Sin cargo asignado' }}
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
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Nombres</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->nombres) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Apellidos</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->apellidos) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Documento</dt>
                    <dd class="mt-1 text-sm text-ink">
                        @if ($empleado->tipo_documento || $empleado->numero_documento)
                            {{ $documentoEtiquetas[$empleado->tipo_documento] ?? 'Documento' }}{{ $empleado->numero_documento ? ' · '.$empleado->numero_documento : '' }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Cargo</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->cargo) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Correo electrónico</dt>
                    <dd class="mt-1 break-all text-sm text-ink">{{ $mostrar($empleado->email) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Teléfono</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->telefono) }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Sucursal</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $empleado->sucursal?->nombre ?? '—' }}</dd>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Información laboral</dt>
                        <dd class="mt-1 text-xs text-ink/60 italic">Detalles contractuales</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Tipo de contrato</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->tipo_contrato) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Fecha de ingreso</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $empleado->fecha_ingreso?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Fecha de salida</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $mostrar($empleado->fecha_salida?->format('d/m/Y')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Salario base</dt>
                        <dd class="mt-1 text-sm text-ink">{{ number_format($empleado->salario_base, 2) }}</dd>
                    </div>
                </div>
            </dl>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-ink/10 px-6 py-4">
            <button type="button" data-modal-close class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40">
                Cerrar
            </button>
            <a href="{{ route('empleados.edit', $empleado) }}" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40">
                Editar
            </a>
        </div>
    </dialog>
@endforeach
