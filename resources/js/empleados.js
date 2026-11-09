(() => {
    const init = () => {
        const buscarInput = document.getElementById('empleadosBuscar');
        const filtros = document.querySelectorAll('[data-filtro]');
        const limpiarBtn = document.querySelector('[data-limpiar-filtros]');
        const tablaContainer = document.getElementById('empleadosTabla');
        const estadoForms = document.querySelectorAll('[data-estado-form]');
        const eliminarForms = document.querySelectorAll('[data-eliminar-empleado]');

        if (!buscarInput || !tablaContainer) return;

        let debounceTimer;

        const fetchTabla = async (params = {}) => {
            const url = new URL(buscarInput.dataset.url, window.location.origin);
            const query = new URLSearchParams(params);

            if (buscarInput.value) {
                query.set('buscar', buscarInput.value);
            }

            filtros.forEach(filtro => {
                if (filtro.value) {
                    query.set(filtro.name, filtro.value);
                }
            });

            try {
                const response = await fetch(`${url.pathname}?${query.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) throw new Error('Error al cargar la tabla');
                const html = await response.text();
                tablaContainer.innerHTML = html;

                // Re-inicializar eventos de la tabla cargada dinámicamente
                initTablaEvents();
            } catch (error) {
                console.error(error);
            }
        };

        const initTablaEvents = () => {
            // Toggles de estado
            document.querySelectorAll('[data-estado-form]').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    try {
                        const response = await fetch(form.action, {
                            method: 'PATCH',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await response.json();

                        const fila = document.querySelector(`[data-fila-empleado="${form.dataset.actual}"]`); // Esto es un ejemplo, el controller devuelve el estado
                        // Simplificado: recargar tabla para reflejar cambios
                        fetchTabla();
                    } catch (error) {
                        console.error(error);
                    }
                });
            });

            // Confirmaciones de eliminación
            document.querySelectorAll('[data-eliminar-empleado]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!confirm(`¿Está seguro de eliminar al empleado ${form.dataset.empleadoNombre}?`)) {
                        e.preventDefault();
                    }
                });
            });

            // Modales de detalle
            document.querySelectorAll('[data-modal-open]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const modalId = btn.dataset.modalOpen;
                    const modal = document.getElementById(modalId);
                    if (modal) modal.showModal();
                });
            });
        };

        buscarInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchTabla(), 300);
        });

        filtros.forEach(filtro => {
            filtro.addEventListener('change', () => fetchTabla());
        });

        limpiarBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = limpiarBtn.href;
        });

        // Cierre delegado de modales (funciona también tras recargar la tabla)
        document.addEventListener('click', (e) => {
            const cerrar = e.target.closest('[data-modal-close]');
            if (cerrar) {
                cerrar.closest('dialog')?.close();
            }
        });

        // Manejar envío de permisos de empleado por AJAX
        document.addEventListener('submit', async (e) => {
            const formPermisos = e.target.closest('[data-form-permisos-empleado]');
            if (!formPermisos) return;

            e.preventDefault();
            const submitBtn = formPermisos.querySelector('button[type="submit"]');
            const empleadoId = formPermisos.dataset.empleadoId;
            const statusSpan = formPermisos.querySelector(`[data-status-empleado-${empleadoId}]`);
            const statusInicial = statusSpan?.textContent ?? '';

            try {
                submitBtn?.setAttribute('disabled', 'true');
                if (submitBtn) submitBtn.textContent = 'Guardando...';

                const formData = new FormData(formPermisos);
                const res = await fetch(formPermisos.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formPermisos.querySelector('input[name="_token"]')?.value || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                const data = await res.json();
                if (!res.ok) {
                    alert(data.error || 'Error al guardar los permisos del empleado.');
                    return;
                }

                if (statusSpan) {
                    statusSpan.textContent = '¡Sobreescrituras guardadas con éxito!';
                    statusSpan.classList.add('text-emerald-600', 'font-semibold');
                    setTimeout(() => {
                        statusSpan.textContent = statusInicial;
                        statusSpan.classList.remove('text-emerald-600', 'font-semibold');
                    }, 3000);
                }
            } catch (err) {
                console.error(err);
                alert('Ocurrió un error inesperado al guardar los permisos.');
            } finally {
                submitBtn?.removeAttribute('disabled');
                if (submitBtn) submitBtn.textContent = 'Guardar sobreescrituras';
            }
        });

        // Restablecer overrides de empleado a nivel visual
        document.addEventListener('click', (e) => {
            const btnReset = e.target.closest('[data-reset-overrides]');
            if (btnReset) {
                const empId = btnReset.dataset.resetOverrides;
                document.querySelectorAll(`.select-override-${empId}`).forEach(sel => {
                    sel.value = '';
                    sel.className = 'rounded-lg border px-2 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-secondary/30 select-override-' + empId + ' border-ink/15 bg-surface text-ink/70';
                });
            }
        });

        // Cambiar estilo de color del select al cambiar opción
        document.addEventListener('change', (e) => {
            if (e.target.matches('[class*="select-override-"]')) {
                const sel = e.target;
                const empId = sel.className.match(/select-override-(\d+)/)?.[1] || '';
                if (sel.value === 'permitir') {
                    sel.className = `rounded-lg border px-2 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-secondary/30 select-override-${empId} border-emerald-300 bg-emerald-50 text-emerald-800 font-semibold`;
                } else if (sel.value === 'denegar') {
                    sel.className = `rounded-lg border px-2 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-secondary/30 select-override-${empId} border-rose-300 bg-rose-50 text-rose-800 font-semibold`;
                } else {
                    sel.className = `rounded-lg border px-2 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-secondary/30 select-override-${empId} border-ink/15 bg-surface text-ink/70`;
                }
            }
        });

        initTablaEvents();
    };

    document.addEventListener('DOMContentLoaded', init);
})();
