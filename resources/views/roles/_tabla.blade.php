@php
    $parametrosListado = $parametrosListado ?? [];
@endphp

<table class="w-full min-w-[46rem] text-sm">
    <thead class="sticky top-0 z-10 bg-surface">
        <tr class="border-b border-ink/10 text-left text-[11px] font-semibold uppercase tracking-wider text-ink/45">
            <th class="px-5 py-3">Rol</th>
            <th class="hidden px-5 py-3 md:table-cell">Descripción</th>
            <th class="whitespace-nowrap px-5 py-3">Alcance</th>
            <th class="whitespace-nowrap px-5 py-3 text-center">Empleados</th>
            <th class="px-5 py-3">Estado</th>
            <th class="px-5 py-3 text-right">Acciones</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-ink/5">
        @forelse ($roles as $rol)
            @php
                $esAdmin = $rol->esAdministrador();
                $estadoActivo = $rol->estado === 'activo';
            @endphp
            <tr class="transition-colors hover:bg-background/70" data-fila-rol="{{ $rol->id }}">
                <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $esAdmin ? 'bg-primary text-white shadow-sm' : 'bg-secondary/15 text-primary' }} text-sm font-semibold">
                            {{ Str::upper(Str::substr($rol->nombre, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink flex items-center gap-1.5">
                                {{ ucfirst($rol->nombre) }}
                                @if ($esAdmin)
                                    <span class="inline-flex items-center rounded-md bg-primary/10 px-1.5 py-0.5 text-[10px] font-medium text-primary">
                                        Sistema
                                    </span>
                                @endif
                            </p>
                            <p class="text-xs text-ink/45 md:hidden truncate">{{ $rol->descripcion ?: 'Sin descripción' }}</p>
                        </div>
                    </div>
                </td>
                <td class="hidden px-5 py-3 md:table-cell text-ink/70 max-w-xs truncate">
                    {{ $rol->descripcion ?: '—' }}
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $rol->alcance === 'todas_sucursales' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-slate-50 text-slate-700 border-slate-200' }}">
                        {{ $rol->alcance === 'todas_sucursales' ? 'Todas las sucursales' : 'Propia sucursal' }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3 text-center">
                    <span class="inline-flex items-center justify-center rounded-full bg-background px-2.5 py-1 text-xs font-semibold text-ink/75 border border-ink/10">
                        {{ $rol->empleados_count ?? $rol->empleados()->count() }}
                    </span>
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    @if ($esAdmin)
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Activo permanente
                        </span>
                    @else
                        <div class="flex items-center gap-2">
                            <form
                                method="POST"
                                action="{{ route('roles.estado', $rol) }}"
                                class="inline-flex"
                                data-estado-form
                                data-estado-actual="{{ $rol->estado }}"
                                data-rol-nombre="{{ $rol->nombre }}"
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
                                    aria-label="{{ $estadoActivo ? 'Desactivar' : 'Activar' }} {{ $rol->nombre }}"
                                    class="estado-switch"
                                >
                                    <span class="estado-texto" data-estado-texto data-estado="{{ $rol->estado }}">
                                        {{ $estadoActivo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <span class="estado-switch-dot" aria-hidden="true"></span>
                                </button>
                            </form>
                        </div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-5 py-3">
                    <div class="flex items-center justify-end gap-1">
                        {{-- Botón para ver y editar permisos en modal --}}
                        <button
                            type="button"
                            data-modal-open="rol-permisos-{{ $rol->id }}"
                            title="{{ $esAdmin ? 'Ver permisos' : 'Gestionar permisos' }} de {{ $rol->nombre }}"
                            aria-label="{{ $esAdmin ? 'Ver permisos' : 'Gestionar permisos' }} de {{ $rol->nombre }}"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-ink/45 transition hover:bg-secondary/10 hover:text-primary focus:outline-none focus:ring-2 focus:ring-secondary/40"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </button>

                        @if (! $esAdmin)
                            <form
                                method="POST"
                                action="{{ route('roles.destroy', $rol) }}"
                                data-confirm="delete"
                                data-eliminar-rol
                                data-rol-nombre="{{ $rol->nombre }}"
                                class="inline-flex {{ $estadoActivo ? 'hidden' : '' }}"
                            >
                                @csrf
                                @method('DELETE')
                                @foreach ($parametrosListado as $parametro => $valor)
                                    <input type="hidden" name="{{ $parametro }}" value="{{ $valor }}">
                                @endforeach
                                <button
                                    type="submit"
                                    title="Eliminar rol"
                                    aria-label="Eliminar {{ $rol->nombre }}"
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
                <td colspan="6" class="px-5 py-14 text-center">
                    <div class="mx-auto flex max-w-sm flex-col items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-background text-ink/35">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-ink">No se encontraron roles</p>
                            <p class="mt-1 text-xs text-ink/45">
                                {{ $buscar !== '' ? 'Prueba con otro término de búsqueda.' : 'Crea un nuevo rol para definir accesos.' }}
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($roles->total() > 0)
    <div class="border-t border-ink/10 px-5 py-3">
        @include('partials.paginacion', ['paginator' => $roles, 'etiqueta' => 'roles'])
    </div>
@endif

{{-- Modales de visualización de permisos por módulo --}}
@foreach ($roles as $rol)
    @include('roles._detalle_permisos_dialog', ['rol' => $rol])
@endforeach
