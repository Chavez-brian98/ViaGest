@php
    $esAdmin = $rol->exists && $rol->esAdministrador();
    $inputBase = 'w-full rounded-xl border bg-background px-3.5 py-2.5 text-sm text-ink transition placeholder:text-ink/40 focus:border-secondary focus:bg-surface focus:outline-none focus:ring-4 focus:ring-secondary/20';
    $labelBase = 'mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink/60';
@endphp

<div class="space-y-6">
    <div class="rounded-2xl border border-ink/10 bg-surface p-5 sm:p-6 shadow-sm">
        <h2 class="text-base font-semibold text-ink mb-4">Información del rol</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="nombre" class="{{ $labelBase }}">Nombre del rol <span class="text-rose-500">*</span></label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre', $rol->nombre) }}"
                    required
                    maxlength="50"
                    placeholder="Ej. Coordinador de Operaciones"
                    @disabled($esAdmin)
                    class="{{ $inputBase }} @error('nombre') border-rose-300 ring-rose-200 @else border-ink/15 @enderror"
                >
                @if ($esAdmin)
                    <p class="mt-1 text-xs text-ink/40">El nombre del rol de administrador no puede ser modificado.</p>
                @endif
                @error('nombre')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="alcance" class="{{ $labelBase }}">Alcance operativo <span class="text-rose-500">*</span></label>
                <select
                    id="alcance"
                    name="alcance"
                    required
                    class="{{ $inputBase }} @error('alcance') border-rose-300 ring-rose-200 @else border-ink/15 @enderror"
                >
                    <option value="propia_sucursal" @selected(old('alcance', $rol->alcance) === 'propia_sucursal')>
                        Propia sucursal (restringido a su asignación)
                    </option>
                    <option value="todas_sucursales" @selected(old('alcance', $rol->alcance) === 'todas_sucursales')>
                        Todas las sucursales (acceso global multi-sucursal)
                    </option>
                </select>
                @error('alcance')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="descripcion" class="{{ $labelBase }}">Descripción del rol</label>
                <input
                    type="text"
                    id="descripcion"
                    name="descripcion"
                    value="{{ old('descripcion', $rol->descripcion) }}"
                    maxlength="255"
                    placeholder="Breve resumen de las responsabilidades de este perfil..."
                    class="{{ $inputBase }} @error('descripcion') border-rose-300 ring-rose-200 @else border-ink/15 @enderror"
                >
                @error('descripcion')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            @if (! $esAdmin)
                <div>
                    <label for="estado" class="{{ $labelBase }}">Estado inicial</label>
                    <select
                        id="estado"
                        name="estado"
                        class="{{ $inputBase }} border-ink/15"
                    >
                        <option value="activo" @selected(old('estado', $rol->estado ?? 'activo') === 'activo')>Activo</option>
                        <option value="inactivo" @selected(old('estado', $rol->estado) === 'inactivo')>Inactivo</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    {{-- Matriz interactiva de permisos --}}
    <div class="rounded-2xl border border-ink/10 bg-surface p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4 pb-4 border-b border-ink/10">
            <div>
                <h2 class="text-base font-semibold text-ink">Matriz de permisos por módulo</h2>
                <p class="text-xs text-ink/50">Habilita o deshabilita los permisos específicos sobre cada recurso.</p>
            </div>

            @if (! $esAdmin)
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        id="btnMarcarTodo"
                        class="rounded-xl border border-ink/15 bg-background px-3 py-1.5 text-xs font-semibold text-ink/70 transition hover:bg-surface hover:text-primary"
                    >
                        Seleccionar todos
                    </button>
                    <button
                        type="button"
                        id="btnDesmarcarTodo"
                        class="rounded-xl border border-ink/15 bg-background px-3 py-1.5 text-xs font-semibold text-ink/70 transition hover:bg-surface hover:text-rose-600"
                    >
                        Limpiar todos
                    </button>
                </div>
            @endif
        </div>

        @if ($esAdmin)
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs text-emerald-800">
                El rol <strong>Administrador</strong> tiene otorgados automáticamente todos los permisos sobre todos los módulos del sistema por ser el perfil raíz.
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-ink/10">
                <table class="w-full text-left text-xs" id="tablaPermisosMatriz">
                    <thead class="bg-background border-b border-ink/10 text-ink/60 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Módulo</th>
                            @foreach ($acciones as $acc)
                                <th class="px-3 py-3 text-center font-semibold">
                                    <div class="flex flex-col items-center gap-1">
                                        <span>{{ ucfirst($acc) }}</span>
                                        <button
                                            type="button"
                                            class="text-[9px] font-normal text-primary/70 hover:underline hover:text-primary"
                                            data-marcar-columna="{{ $acc }}"
                                        >
                                            Columna
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-3 py-3 text-center font-semibold">Fila</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/5">
                        @foreach ($modulosEtiquetas as $claveModulo => $nombreModulo)
                            @php
                                $accionesGuardadas = old("permisos.{$claveModulo}", $permisosSeleccionados[$claveModulo] ?? []);
                            @endphp
                            <tr class="hover:bg-background/40" data-fila-modulo="{{ $claveModulo }}">
                                <td class="px-4 py-2.5 font-medium text-ink/80 whitespace-nowrap">
                                    {{ $nombreModulo }}
                                </td>
                                @foreach ($acciones as $acc)
                                    @php
                                        $checked = in_array($acc, $accionesGuardadas, true);
                                    @endphp
                                    <td class="px-3 py-2.5 text-center">
                                        <input
                                            type="checkbox"
                                            name="permisos[{{ $claveModulo }}][]"
                                            value="{{ $acc }}"
                                            @checked($checked)
                                            class="h-4 w-4 rounded border-ink/20 text-primary focus:ring-secondary/30 checkbox-permiso"
                                            data-modulo="{{ $claveModulo }}"
                                            data-accion="{{ $acc }}"
                                        >
                                    </td>
                                @endforeach
                                <td class="px-3 py-2.5 text-center">
                                    <button
                                        type="button"
                                        class="text-[10px] text-ink/45 hover:text-primary hover:underline"
                                        data-marcar-fila="{{ $claveModulo }}"
                                    >
                                        Alternar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a
            href="{{ route('roles.index') }}"
            class="rounded-xl border border-ink/15 px-4 py-2.5 text-sm font-semibold text-ink/70 transition hover:bg-background focus:outline-none focus:ring-2 focus:ring-secondary/40"
        >
            Cancelar
        </a>
        <button
            type="submit"
            class="rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-accent/40"
        >
            {{ $rol->exists ? 'Guardar cambios' : 'Crear rol' }}
        </button>
    </div>
</div>
