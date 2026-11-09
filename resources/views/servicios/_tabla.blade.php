@php
    use Illuminate\Support\Str;

    $parametrosListado = $parametrosListado ?? [];
    $tipoEtiquetas = [
        'vuelo' => 'Vuelo',
        'hospedaje' => 'Hospedaje',
        'traslado' => 'Traslado',
        'actividad' => 'Actividad',
        'guia' => 'Guía',
        'seguro' => 'Seguro',
        'otro' => 'Otro',
    ];
    $unidadEtiquetas = [
        'por_persona' => 'Por persona',
        'por_habitacion_noche' => 'Por habitación / noche',
        'por_vehiculo' => 'Por vehículo',
        'por_dia' => 'Por día',
        'por_grupo' => 'Por grupo',
        'por_unidad' => 'Por unidad',
    ];
    $mostrar = fn ($valor) => filled($valor) ? $valor : '—';
    $ruta = function ($servicio) {
        if (filled($servicio->origen_nombre) && filled($servicio->destino_nombre)) {
            return $servicio->origen_nombre.' → '.$servicio->destino_nombre;
        }

        return $servicio->origen_nombre ?: ($servicio->destino_nombre ?: '—');
    };
    $formatoCosto = fn ($valor) => '$'.number_format((float) $valor, 2);
@endphp

<table class="w-full min-w-[52rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
    <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
        <th class="whitespace-nowrap px-5 py-3">Código</th>
        <th class="px-5 py-3">Servicio</th>
        <th class="hidden whitespace-nowrap px-5 py-3 md:table-cell">Tipo</th>
        <th class="hidden whitespace-nowrap px-5 py-3 lg:table-cell">Proveedor</th>
        <th class="hidden whitespace-nowrap px-5 py-3 xl:table-cell">Ruta</th>
        <th class="hidden whitespace-nowrap px-5 py-3 lg:table-cell">Costo neto</th>
        <th class="px-5 py-3">Estado</th>
        <th class="px-5 py-3 text-right">Acciones</th>
    </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
    @forelse ($servicios as $servicio)
        @php $estadoActivo = $servicio->estado === 'activo'; @endphp
        <tr class="transition-colors hover:bg-background/70">
            <td class="whitespace-nowrap px-5 py-3 font-mono text-xs font-medium text-ink/60">{{ $servicio->codigo }}</td>
            <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-secondary/15 text-sm font-semibold text-primary">
                        {{ Str::upper(Str::substr($servicio->nombre, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-medium text-ink">{{ $servicio->nombre }}</p>
                        <p class="truncate text-xs text-ink/45">{{ $servicio->descripcion ?: 'Sin descripción' }}</p>
                    </div>
                </div>
            </td>
            <td class="hidden whitespace-nowrap px-5 py-3 md:table-cell">
                <span class="inline-flex items-center rounded-full bg-secondary/10 px-2.5 py-1 text-xs font-medium text-primary">
                    {{ $tipoEtiquetas[$servicio->tipo] ?? ucfirst((string) $servicio->tipo) }}
                </span>
            </td>
            <td class="hidden whitespace-nowrap px-5 py-3 lg:table-cell">
                <span class="text-ink/70">{{ $mostrar($servicio->proveedor_nombre) }}</span>
            </td>
            <td class="hidden whitespace-nowrap px-5 py-3 xl:table-cell">
                <span class="text-ink/70">{{ $ruta($servicio) }}</span>
            </td>
            <td class="hidden whitespace-nowrap px-5 py-3 lg:table-cell">
                <div class="text-ink/70">{{ $formatoCosto($servicio->costo_neto) }}</div>
                <p class="text-xs text-ink/45">{{ $unidadEtiquetas[$servicio->unidad_cobro] ?? $servicio->unidad_cobro }}</p>
            </td>
            <td class="whitespace-nowrap px-5 py-3">
                <form
                    method="POST"
                    action="{{ route('servicios.estado', $servicio->id) }}"
                    class="inline-flex"
                    data-estado-form
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
                        aria-checked="{{ $estadoActivo ? 'true' : 'false' }}"
                        title="{{ $estadoActivo ? 'Desactivar' : 'Activar' }}"
                        class="estado-switch"
                    >
                        <span class="estado-texto" data-estado="{{ $servicio->estado }}">
                            {{ $estadoActivo ? 'Activo' : 'Inactivo' }}
                        </span>
                        <span class="estado-switch-dot" aria-hidden="true"></span>
                    </button>
                </form>
            </td>
            <td class="whitespace-nowrap px-5 py-3">
                <div class="flex items-center justify-end gap-1">
                    <button
                        type="button"
                        data-modal-open="servicio-detalle-{{ $servicio->id }}"
                        title="Ver detalle"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    <a
                        href="{{ route('servicios.edit', $servicio->id) }}"
                        title="Editar"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-primary/10 hover:text-primary"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                    </a>

                    <form
                        method="POST"
                        action="{{ route('servicios.destroy', $servicio->id) }}"
                        data-confirm="delete"
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
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-rose-50 hover:text-rose-600"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a49.208 49.208 0 00-7.5 0" />
                            </svg>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="px-5 py-14 text-center">
                <p class="text-sm font-semibold text-ink">No se encontraron servicios</p>
                <p class="mt-1 text-xs text-ink/45">Agrega el primer servicio para comenzar.</p>
            </td>
        </tr>
    @endforelse
    </tbody>
</table>

@if ($servicios->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $servicios, 'etiqueta' => 'servicios'])
    </div>
@endif

@foreach ($servicios as $servicio)
    <dialog
        id="servicio-detalle-{{ $servicio->id }}"
        class="m-auto w-[min(92vw,48rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
    >
        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Detalle del servicio</p>
                <h2 class="text-lg font-semibold text-ink">{{ $servicio->nombre }}</h2>
                <p class="mt-1 text-xs text-ink/45">{{ $servicio->codigo }}</p>
            </div>
            <button type="button" data-modal-close class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 hover:bg-background">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Tipo</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $tipoEtiquetas[$servicio->tipo] ?? ucfirst((string) $servicio->tipo) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Proveedor</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($servicio->proveedor_nombre) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ciudad de origen</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($servicio->origen_nombre) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Ciudad de destino</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($servicio->destino_nombre) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Unidad de cobro</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $unidadEtiquetas[$servicio->unidad_cobro] ?? $servicio->unidad_cobro }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Costo neto</dt>
                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $formatoCosto($servicio->costo_neto) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Vigente desde</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($servicio->vigente_desde) }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Vigente hasta</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $mostrar($servicio->vigente_hasta) }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Descripción</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-ink">{{ $mostrar($servicio->descripcion) }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-ink/10 px-6 py-4">
            <button type="button" data-modal-close class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 hover:bg-background">
                Cerrar
            </button>
            <a href="{{ route('servicios.edit', $servicio->id) }}" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">
                Editar
            </a>
        </div>
    </dialog>
@endforeach
