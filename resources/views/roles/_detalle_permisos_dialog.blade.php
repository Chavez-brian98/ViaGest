@php
    $esAdmin = $rol->esAdministrador();
    $permisosAgrupados = $rol->permisosAgrupados();
    $modulosEtiquetas = \App\Service\Roles\RolService::MODULOS_ETIQUETAS;
    $todasAcciones = \App\Service\Roles\RolService::ACCIONES;
@endphp

<dialog
    id="rol-permisos-{{ $rol->id }}"
    class="m-auto w-[min(94vw,56rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
    aria-labelledby="rol-permisos-titulo-{{ $rol->id }}"
>
    <form
        method="POST"
        action="{{ route('roles.permisos.update', $rol) }}"
        data-form-permisos-rol
        data-rol-id="{{ $rol->id }}"
    >
        @csrf
        @method('PATCH')

        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Gestión de permisos</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h2 id="rol-permisos-titulo-{{ $rol->id }}" class="truncate text-lg font-semibold text-ink">
                        Rol: {{ ucfirst($rol->nombre) }}
                    </h2>
                    @if ($esAdmin)
                        <span class="inline-flex items-center rounded-md bg-emerald-100 text-emerald-800 px-2 py-0.5 text-xs font-medium">
                            Superusuario (Acceso Total)
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-slate-100 text-slate-700 px-2 py-0.5 text-xs font-medium">
                            {{ $rol->alcance === 'todas_sucursales' ? 'Todas las sucursales' : 'Propia sucursal' }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-ink/50 mt-1">{{ $rol->descripcion ?: 'Edita los permisos directamente desde esta matriz.' }}</p>
            </div>
            <button
                type="button"
                data-modal-close
                aria-label="Cerrar modal"
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-ink/45 transition hover:bg-background hover:text-ink focus:outline-none focus:ring-2 focus:ring-secondary/40"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="max-h-[68vh] overflow-y-auto px-6 py-4 space-y-4">
            @if (! $esAdmin)
                <div class="flex flex-wrap items-center justify-between gap-2 bg-background/60 p-2.5 rounded-xl border border-ink/10 text-xs">
                    <span class="text-ink/60 font-medium">Acciones rápidas de selección:</span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-ink/15 bg-surface px-2.5 py-1 font-semibold text-ink/70 hover:text-primary transition"
                            data-marcar-todo-modal="{{ $rol->id }}"
                        >
                            Marcar todos
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-ink/15 bg-surface px-2.5 py-1 font-semibold text-ink/70 hover:text-rose-600 transition"
                            data-desmarcar-todo-modal="{{ $rol->id }}"
                        >
                            Desmarcar todos
                        </button>
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto rounded-xl border border-ink/10">
                <table class="w-full text-left text-xs" id="tablaModalRol_{{ $rol->id }}">
                    <thead class="bg-background border-b border-ink/10 text-ink/60 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Módulo del sistema</th>
                            @foreach ($todasAcciones as $acc)
                                <th class="px-3 py-2.5 font-semibold text-center">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span>{{ ucfirst($acc) }}</span>
                                        @if (! $esAdmin)
                                            <button
                                                type="button"
                                                class="text-[9px] text-primary/70 hover:underline hover:text-primary"
                                                data-columna-modal="{{ $acc }}"
                                                data-modal-id="{{ $rol->id }}"
                                            >
                                                Columna
                                            </button>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            @if (! $esAdmin)
                                <th class="px-3 py-2.5 text-center font-semibold">Fila</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/5">
                        @foreach ($modulosEtiquetas as $claveModulo => $nombreModulo)
                            @php
                                $accionesRol = $permisosAgrupados[$claveModulo] ?? [];
                            @endphp
                            <tr class="hover:bg-background/40" data-fila-modulo-modal="{{ $claveModulo }}">
                                <td class="px-4 py-2.5 font-medium text-ink/80 whitespace-nowrap">
                                    {{ $nombreModulo }}
                                </td>
                                @foreach ($todasAcciones as $acc)
                                    @php
                                        $tiene = $esAdmin || in_array($acc, $accionesRol, true);
                                    @endphp
                                    <td class="px-3 py-2.5 text-center">
                                        @if ($esAdmin)
                                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @else
                                            <input
                                                type="checkbox"
                                                name="permisos[{{ $claveModulo }}][]"
                                                value="{{ $acc }}"
                                                @checked($tiene)
                                                class="h-4 w-4 rounded border-ink/20 text-primary focus:ring-secondary/30 checkbox-modal-rol-{{ $rol->id }}"
                                                data-modulo="{{ $claveModulo }}"
                                                data-accion="{{ $acc }}"
                                            >
                                        @endif
                                    </td>
                                @endforeach
                                @if (! $esAdmin)
                                    <td class="px-3 py-2.5 text-center">
                                        <button
                                            type="button"
                                            class="text-[10px] text-ink/45 hover:text-primary hover:underline"
                                            data-fila-modal="{{ $claveModulo }}"
                                            data-modal-id="{{ $rol->id }}"
                                        >
                                            Alternar
                                        </button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-ink/10 px-6 py-4 bg-background/30">
            <span class="text-xs text-ink/50" data-status-guardado-{{ $rol->id }}>
                Total módulos: {{ count($modulosEtiquetas) }}
            </span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    data-modal-close
                    class="rounded-xl border border-ink/15 px-4 py-2 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
                >
                    Cerrar
                </button>
                @if (! $esAdmin)
                    <button
                        type="submit"
                        class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                    >
                        Guardar cambios
                    </button>
                @endif
            </div>
        </div>
    </form>
</dialog>
