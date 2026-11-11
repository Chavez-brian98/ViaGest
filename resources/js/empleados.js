(() => {
    const init = () => {
        const buscarInput = document.getElementById('empleadosBuscar');
        const filtros = document.querySelectorAll('[data-filtro]');
        const limpiarBtn = document.querySelector('[data-limpiar-filtros]');
        const tablaContainer = document.getElementById('empleadosTabla');

        if (!buscarInput || !tablaContainer) return;

        let debounceTimer;

        const fetchTabla = async (params = {}) => {
            const url = new URL(buscarInput.dataset.url, window.location.origin);
            const query = new URLSearchParams(params);

            if (buscarInput.value) {
                query.set('buscar', buscarInput.value);
            }

            filtros.forEach((filtro) => {
                if (filtro.value) {
                    query.set(filtro.name, filtro.value);
                }
            });

            try {
                tablaContainer.classList.add('opacity-50', 'pointer-events-none');
                const response = await fetch(`${url.pathname}?${query.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('Error al cargar la tabla');
                const html = await response.text();
                tablaContainer.innerHTML = html;
                window.history.replaceState({}, '', `${url.pathname}?${query.toString()}`);
            } catch (error) {
                console.error(error);
            } finally {
                tablaContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        };

        buscarInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchTabla(), 300);
        });

        filtros.forEach((filtro) => {
            filtro.addEventListener('change', () => fetchTabla());
        });

        limpiarBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            window.location.href = limpiarBtn.href;
        });

        // Toggle de estado por AJAX
        document.addEventListener('submit', async (e) => {
            const formEstado = e.target.closest('[data-estado-form]');
            if (!formEstado) return;

            e.preventDefault();
            const switchBtn = formEstado.querySelector('[data-estado-switch]');
            const textoSpan = formEstado.querySelector('[data-estado-texto]');
            const inputDestino = formEstado.querySelector('input[name="estado_destino"]');
            const fila = formEstado.closest('tr');
            const formEliminar = fila ? fila.querySelector('form[data-eliminar-empleado]') : null;

            const estadoAnterior = formEstado.dataset.estadoActual;
            const nuevoEstado = inputDestino ? inputDestino.value : (estadoAnterior === 'activo' ? 'inactivo' : 'activo');

            try {
                switchBtn?.setAttribute('disabled', 'true');
                const res = await fetch(formEstado.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formEstado.querySelector('input[name="_token"]')?.value || '',
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(new FormData(formEstado)).toString(),
                });

                const data = await res.json();
                if (!res.ok) {
                    alert(data.error || 'Ocurrió un error al actualizar el estado.');
                    return;
                }

                formEstado.dataset.estadoActual = data.estado;
                if (inputDestino) {
                    inputDestino.value = data.estado === 'activo' ? 'inactivo' : 'activo';
                }
                if (switchBtn) {
                    switchBtn.setAttribute('aria-checked', data.estado === 'activo' ? 'true' : 'false');
                    switchBtn.title = data.estado === 'activo' ? 'Desactivar' : 'Activar';
                }
                if (textoSpan) {
                    textoSpan.dataset.estado = data.estado;
                    textoSpan.textContent = data.estado === 'activo' ? 'Activo' : 'Inactivo';
                }
                if (formEliminar) {
                    if (data.estado === 'activo') {
                        formEliminar.classList.add('hidden');
                    } else {
                        formEliminar.classList.remove('hidden');
                    }
                }
            } catch (err) {
                console.error(err);
            } finally {
                switchBtn?.removeAttribute('disabled');
            }
        });

        // Confirmación de eliminación
        document.addEventListener('submit', (e) => {
            const formEliminar = e.target.closest('[data-eliminar-empleado]');
            if (!formEliminar) return;

            if (!confirm(`¿Está seguro de eliminar al empleado ${formEliminar.dataset.empleadoNombre}?`)) {
                e.preventDefault();
            }
        });

        // Apertura, cierre y backdrop de diálogos (delegado)
        document.addEventListener('click', (e) => {
            // Paginación AJAX
            const linkPaginacion = e.target.closest('#empleadosTabla nav a');
            if (linkPaginacion) {
                e.preventDefault();
                const href = linkPaginacion.getAttribute('href');
                if (href) {
                    const url = new URL(href, window.location.origin);
                    const params = Object.fromEntries(url.searchParams.entries());
                    fetchTabla(params);
                }
                return;
            }

            // Abrir modal
            const btnOpen = e.target.closest('[data-modal-open]');
            if (btnOpen) {
                const modalId = btnOpen.getAttribute('data-modal-open');
                const dialog = document.getElementById(modalId);
                if (dialog && typeof dialog.showModal === 'function') {
                    dialog.showModal();
                }
                return;
            }

            // Cerrar modal
            const btnClose = e.target.closest('[data-modal-close]');
            if (btnClose) {
                const dialog = btnClose.closest('dialog');
                if (dialog) dialog.close();
                return;
            }

            // Cerrar al pulsar el backdrop
            if (e.target.tagName === 'DIALOG' && e.target.hasAttribute('open')) {
                const rect = e.target.getBoundingClientRect();
                const isInDialog =
                    rect.top <= e.clientY &&
                    e.clientY <= rect.top + rect.height &&
                    rect.left <= e.clientX &&
                    e.clientX <= rect.left + rect.width;

                if (!isInDialog) {
                    e.target.close();
                }
            }
        });

        // -------------------------------------------------------------
        // MATRIZ DE PERMISOS DENTRO DEL MODAL (empleados._permisos_dialog)
        // -------------------------------------------------------------
        document.addEventListener('click', (e) => {
            // Marcar todos en modal específico
            const btnMarcarTodoModal = e.target.closest('[data-marcar-todo-modal]');
            if (btnMarcarTodoModal) {
                const empId = btnMarcarTodoModal.dataset.marcarTodoModal;
                document.querySelectorAll(`.checkbox-modal-empleado-${empId}`).forEach((cb) => (cb.checked = true));
                return;
            }

            // Desmarcar todos en modal específico
            const btnDesmarcarTodoModal = e.target.closest('[data-desmarcar-todo-modal]');
            if (btnDesmarcarTodoModal) {
                const empId = btnDesmarcarTodoModal.dataset.desmarcarTodoModal;
                document.querySelectorAll(`.checkbox-modal-empleado-${empId}`).forEach((cb) => (cb.checked = false));
                return;
            }

            // Marcar columna en modal específico
            const btnColModal = e.target.closest('[data-columna-modal]');
            if (btnColModal) {
                const accion = btnColModal.dataset.columnaModal;
                const empId = btnColModal.dataset.modalId;
                const checkboxes = document.querySelectorAll(`.checkbox-modal-empleado-${empId}[data-accion="${accion}"]`);
                const algunDesmarcado = Array.from(checkboxes).some((cb) => !cb.checked);
                checkboxes.forEach((cb) => (cb.checked = algunDesmarcado));
                return;
            }

            // Alternar fila en modal específico
            const btnFilaModal = e.target.closest('[data-fila-modal]');
            if (btnFilaModal) {
                const modulo = btnFilaModal.dataset.filaModal;
                const empId = btnFilaModal.dataset.modalId;
                const checkboxes = document.querySelectorAll(`.checkbox-modal-empleado-${empId}[data-modulo="${modulo}"]`);
                const algunDesmarcado = Array.from(checkboxes).some((cb) => !cb.checked);
                checkboxes.forEach((cb) => (cb.checked = algunDesmarcado));
                return;
            }
        });

        // Guardar permisos de empleado vía AJAX desde el modal
        document.addEventListener('submit', async (e) => {
            const formPermisos = e.target.closest('[data-form-permisos-empleado]');
            if (!formPermisos) return;

            e.preventDefault();
            const submitBtn = formPermisos.querySelector('button[type="submit"]');
            const empId = formPermisos.dataset.empleadoId;
            const statusSpan = formPermisos.querySelector(`[data-status-guardado-${empId}]`);
            const statusInicial = statusSpan ? statusSpan.textContent : '';

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
                    statusSpan.textContent = '¡Permisos guardados con éxito!';
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
                if (submitBtn) submitBtn.textContent = 'Guardar cambios';
            }
        });
    };

    document.addEventListener('DOMContentLoaded', init);
})();
