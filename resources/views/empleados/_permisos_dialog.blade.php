@php
    $esAdmin = $empleado->esAdministrador();
    $rol = $empleado->rol;
    $permisosRol = $rol ? $rol->permisosAgrupados() : [];
    $overrides = $empleado->overridesPermisos();
    $modulosEtiquetas = \App\Service\Roles\RolService::MODULOS_ETIQUETAS;
    $todasAcciones = \App\Service\Roles\RolService::ACCIONES;

    $totalRol = 0;
    $totalEfectivo = 0;

    foreach ($modulosEtiquetas as $claveModulo => $_) {
        $accionesRol = $permisosRol[$claveModulo] ?? [];

        foreach ($todasAcciones as $accion) {
            $otorgaRol = in_array($accion, $accionesRol, true);
            $override = $overrides[$claveModulo][$accion] ?? null;
            $efectivo = $override === 'permitir' || ($override !== 'denegar' && $otorgaRol);

            $totalRol += $otorgaRol ? 1 : 0;
            $totalEfectivo += $efectivo ? 1 : 0;
        }
    }
@endphp

<dialog
    id="empleado-permisos-{{ $empleado->id }}"
    class="m-auto w-[min(94vw,58rem)] rounded-2xl border border-ink/10 bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-slate-950/50 backdrop:backdrop-blur-sm"
    aria-labelledby="empleado-permisos-titulo-{{ $empleado->id }}"
>
    <form
        method="POST"
        action="{{ route('empleados.permisos.update', $empleado) }}"
        data-form-permisos-empleado
        data-empleado-id="{{ $empleado->id }}"
    >
        @csrf
        @method('PATCH')

        <div class="flex items-start justify-between gap-4 border-b border-ink/10 px-6 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-ink/45">Permisos específicos por empleado</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h2 id="empleado-permisos-titulo-{{ $empleado->id }}" class="truncate text-lg font-semibold text-ink">
                        {{ $empleado->nombreCompleto() }} ({{ $empleado->codigo }})
                    </h2>
                    @if ($esAdmin)
                        <span class="inline-flex items-center rounded-md bg-emerald-100 text-emerald-800 px-2 py-0.5 text-xs font-medium">
                            Superusuario
                        </span>
                    @elseif ($rol)
                        <span class="inline-flex items-center rounded-md bg-secondary/15 text-primary px-2 py-0.5 text-xs font-medium">
                            Rol base: {{ ucfirst($rol->nombre) }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-rose-100 text-rose-800 px-2 py-0.5 text-xs font-medium">
                            Sin rol asignado
                        </span>
                    @endif
                </div>
                <p class="text-xs text-ink/50 mt-1">
                    Revisa el permiso que otorga el rol y el permiso efectivo del empleado. Puedes conceder permisos adicionales (verde) o revocar los que otorga su rol (rojo).
                </p>
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
            @if ($esAdmin)
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs text-emerald-800">
                    Este empleado posee el rol <strong>Administrador</strong>, por lo que dispone de acceso total irrestricto en todos los módulos del sistema.
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 bg-background/60 p-3 rounded-xl border border-ink/10 text-xs">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <span class="inline-flex items-center gap-1.5 text-ink/60">
                            <span class="rounded bg-secondary/15 px-1.5 py-0.5 text-[10px] font-semibold text-primary">Rol ✓</span>
                            Lo otorga el rol
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-medium">
                            <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-800">Emp ✓</span>
                            Permiso efectivo
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-rose-700 font-medium">
                            <span class="rounded bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-800">Emp ✗</span>
                            Denegado al empleado
                        </span>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg border border-ink/15 bg-surface px-2.5 py-1 text-xs font-semibold text-ink/70 hover:text-rose-600 transition"
                        data-reset-overrides="{{ $empleado->id }}"
                    >
                        Restablecer al rol original
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-ink/10">
                    <table class="w-full text-left text-xs" id="tablaPermisosEmpleado_{{ $empleado->id }}">
                        <thead class="bg-background border-b border-ink/10 text-ink/60 uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-4 py-2.5 font-semibold">Módulo</th>
                                @foreach ($todasAcciones as $acc)
                                    <th class="px-2 py-2.5 font-semibold text-center">{{ ucfirst($acc) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink/5">
                            @foreach ($modulosEtiquetas as $claveModulo => $nombreModulo)
                                @php
                                    $tienePorRolModulo = in_array($claveModulo, array_keys($permisosRol), true);
                                    $accionesEnRol = $permisosRol[$claveModulo] ?? [];
                                @endphp
                                <tr class="hover:bg-background/40">
                                    <td class="px-4 py-2 font-medium text-ink/80 whitespace-nowrap">
                                        {{ $nombreModulo }}
                                    </td>
                                    @foreach ($todasAcciones as $acc)
                                        @php
                                            $rolOtorga = in_array($acc, $accionesEnRol, true);
                                            $overrideActual = $overrides[$claveModulo][$acc] ?? null; // 'permitir', 'denegar' o null
                                            $efectivo = $overrideActual === 'permitir' || ($overrideActual !== 'denegar' && $rolOtorga);
                                        @endphp
                                        <td class="px-2 py-1.5 text-center align-top">
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="flex items-center justify-center gap-1">
                                                    <span
                                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-semibold {{ $rolOtorga ? 'bg-secondary/15 text-primary' : 'bg-slate-100 text-slate-500' }}"
                                                        title="Permiso que otorga el rol"
                                                    >
                                                        Rol {{ $rolOtorga ? '✓' : '✗' }}
                                                    </span>
                                                    <span
                                                        data-permiso-efectivo
                                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-semibold {{ $efectivo ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}"
                                                        title="Permiso efectivo del empleado"
                                                    >
                                                        Emp {{ $efectivo ? '✓' : '✗' }}
                                                    </span>
                                                </div>
                                                <select
                                                    name="permisos[{{ $claveModulo }}][{{ $acc }}]"
                                                    data-rol-otorga="{{ $rolOtorga ? '1' : '0' }}"
                                                    class="rounded-lg border px-2 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-secondary/30 select-override-{{ $empleado->id }} @if($overrideActual === 'permitir') border-emerald-300 bg-emerald-50 text-emerald-800 font-semibold @elseif($overrideActual === 'denegar') border-rose-300 bg-rose-50 text-rose-800 font-semibold @else border-ink/15 bg-surface text-ink/70 @endif"
                                                >
                                                    <option value="" @selected(is_null($overrideActual))>
                                                        Heredar del rol
                                                    </option>
                                                    <option value="permitir" @selected($overrideActual === 'permitir') class="text-emerald-700 font-semibold">
                                                        ✓ Conceder
                                                    </option>
                                                    <option value="denegar" @selected($overrideActual === 'denegar') class="text-rose-700 font-semibold">
                                                        ✕ Denegar
                                                    </option>
                                                </select>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink/10 px-6 py-4 bg-background/30">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink/50">
                <span data-status-empleado-{{ $empleado->id }}>
                    Total módulos: {{ count($modulosEtiquetas) }}
                </span>
                @if (! $esAdmin)
                    <span>
                        Rol: <strong data-total-rol-{{ $empleado->id }}>{{ $totalRol }}</strong> ·
                        Empleado: <strong data-total-efectivo-{{ $empleado->id }}>{{ $totalEfectivo }}</strong>
                    </span>
                @endif
            </div>
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
                        Guardar sobreescrituras
                    </button>
                @endif
            </div>
        </div>
    </form>
</dialog>
