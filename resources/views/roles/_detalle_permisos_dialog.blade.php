@php
    $esAdmin = $rol->esAdministrador();
    $permisosAgrupados = $rol->permisosAgrupados();
    $modulosEtiquetas = \App\Service\Roles\RolService::MODULOS_ETIQUETAS;
    $todasAcciones = \App\Service\Roles\RolService::ACCIONES;
@endphp

<dialog
    id="rol-permisos-{{ $rol->id }}"
    class="m-auto w-[min(92vw,52rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
    aria-labelledby="rol-permisos-titulo-{{ $rol->id }}"
>
    <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
        <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Permisos del rol</p>
            <div class="flex items-center gap-2 mt-0.5">
                <h2 id="rol-permisos-titulo-{{ $rol->id }}" class="truncate text-lg font-semibold text-ink">
                    {{ ucfirst($rol->nombre) }}
                </h2>
                @if ($esAdmin)
                    <span class="inline-flex items-center rounded-md bg-emerald-100 text-emerald-800 px-2 py-0.5 text-xs font-medium">
                        Acceso Total (Superusuario)
                    </span>
                @else
                    <span class="inline-flex items-center rounded-md bg-slate-100 text-slate-700 px-2 py-0.5 text-xs font-medium">
                        {{ $rol->alcance === 'todas_sucursales' ? 'Todas las sucursales' : 'Propia sucursal' }}
                    </span>
                @endif
            </div>
            <p class="text-xs text-ink/50 mt-1">{{ $rol->descripcion ?: 'Sin descripción registrada' }}</p>
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

    <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
        <div class="overflow-x-auto rounded-xl border border-ink/10">
            <table class="w-full text-left text-xs">
                <thead class="bg-background border-b border-ink/10 text-ink/50 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Módulo del sistema</th>
                        @foreach ($todasAcciones as $acc)
                            <th class="px-3 py-2.5 font-semibold text-center">{{ ucfirst($acc) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/5">
                    @foreach ($modulosEtiquetas as $claveModulo => $nombreModulo)
                        @php
                            $accionesRol = $permisosAgrupados[$claveModulo] ?? [];
                        @endphp
                        <tr class="hover:bg-background/40">
                            <td class="px-4 py-2.5 font-medium text-ink/80 whitespace-nowrap">
                                {{ $nombreModulo }}
                            </td>
                            @foreach ($todasAcciones as $acc)
                                @php
                                    $tiene = $esAdmin || in_array($acc, $accionesRol, true);
                                @endphp
                                <td class="px-3 py-2.5 text-center">
                                    @if ($tiene)
                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-ink/15"></span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-ink/10 px-6 py-4 bg-background/30">
        <span class="text-xs text-ink/50">
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
                <a
                    href="{{ route('roles.edit', $rol) }}"
                    class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
                >
                    Editar permisos
                </a>
            @endif
        </div>
    </div>
</dialog>
