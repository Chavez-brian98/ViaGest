(() => {
    // -------------------------------------------------------------
    // 1. LÓGICA DE TABLA, FILTROS Y AJAX (Listado principal)
    // -------------------------------------------------------------
    const contenedor = document.getElementById('rolesTabla');
    const formFiltros = document.getElementById('rolesFiltros');
    const inputBuscar = document.getElementById('rolesBuscar');

    let abortController = null;
    let debounceTimer = null;

    const actualizarTabla = async (url) => {
        if (!contenedor) return;
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        try {
            contenedor.classList.add('opacity-50', 'pointer-events-none');
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                signal: abortController.signal,
            });

            if (!res.ok) throw new Error('Error al cargar la tabla de roles');

            const html = await res.text();
            contenedor.innerHTML = html;
            window.history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error(error);
            }
        } finally {
            contenedor.classList.remove('opacity-50', 'pointer-events-none');
        }
    };

    const construirUrlFiltros = () => {
        if (!formFiltros) return window.location.href;
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (value && typeof value === 'string' && value.trim() !== '') {
                params.set(key, value.trim());
            }
        }

        const base = formFiltros.getAttribute('action') || window.location.pathname;
        const query = params.toString();
        return query ? `${base}?${query}` : base;
    };

    if (inputBuscar) {
        inputBuscar.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                actualizarTabla(construirUrlFiltros());
            }, 300);
        });

        inputBuscar.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                actualizarTabla(construirUrlFiltros());
            }
        });
    }

    if (formFiltros) {
        formFiltros.querySelectorAll('[data-filtro]').forEach((select) => {
            select.addEventListener('change', () => {
                clearTimeout(debounceTimer);
                actualizarTabla(construirUrlFiltros());
            });
        });

        const btnLimpiar = formFiltros.querySelector('[data-limpiar-filtros]');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', (e) => {
                e.preventDefault();
                formFiltros.reset();
                if (inputBuscar) inputBuscar.value = '';
                actualizarTabla(btnLimpiar.getAttribute('href') || window.location.pathname);
            });
        }
    }

    // Toggle de estado por AJAX y Modales Delegados
    document.addEventListener('submit', async (e) => {
        const formEstado = e.target.closest('[data-estado-form]');
        if (!formEstado) return;

        e.preventDefault();
        const switchBtn = formEstado.querySelector('[data-estado-switch]');
        const textoSpan = formEstado.querySelector('[data-estado-texto]');
        const inputDestino = formEstado.querySelector('input[name="estado_destino"]');
        const fila = formEstado.closest('tr');
        const formEliminar = fila ? fila.querySelector('form[data-eliminar-rol]') : null;

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

    document.addEventListener('click', (e) => {
        // Paginación AJAX
        const linkPaginacion = e.target.closest('#rolesTabla nav a');
        if (linkPaginacion) {
            e.preventDefault();
            const href = linkPaginacion.getAttribute('href');
            if (href) actualizarTabla(href);
            return;
        }

        // Abrir modal de detalle de permisos
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
    // 2. MATRIZ DE PERMISOS (Formulario Create / Edit)
    // -------------------------------------------------------------
    const btnMarcarTodo = document.getElementById('btnMarcarTodo');
    const btnDesmarcarTodo = document.getElementById('btnDesmarcarTodo');

    if (btnMarcarTodo) {
        btnMarcarTodo.addEventListener('click', () => {
            document.querySelectorAll('.checkbox-permiso').forEach((cb) => (cb.checked = true));
        });
    }

    if (btnDesmarcarTodo) {
        btnDesmarcarTodo.addEventListener('click', () => {
            document.querySelectorAll('.checkbox-permiso').forEach((cb) => (cb.checked = false));
        });
    }

    // Marcar por columna (acción)
    document.querySelectorAll('[data-marcar-columna]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const accion = btn.dataset.marcarColumna;
            const checkboxes = document.querySelectorAll(`.checkbox-permiso[data-accion="${accion}"]`);
            const algunDesmarcado = Array.from(checkboxes).some((cb) => !cb.checked);
            checkboxes.forEach((cb) => (cb.checked = algunDesmarcado));
        });
    });

    // Marcar por fila (módulo)
    document.querySelectorAll('[data-marcar-fila]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const modulo = btn.dataset.marcarFila;
            const checkboxes = document.querySelectorAll(`.checkbox-permiso[data-modulo="${modulo}"]`);
            const algunDesmarcado = Array.from(checkboxes).some((cb) => !cb.checked);
            checkboxes.forEach((cb) => (cb.checked = algunDesmarcado));
        });
    });
})();
