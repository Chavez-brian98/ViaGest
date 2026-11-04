(() => {
    const contenedor = document.getElementById('auditoriaTabla');
    const formFiltros = document.getElementById('auditoriaFiltros');
    const inputBuscar = document.getElementById('auditoriaBuscar');

    if (!contenedor) return;

    let abortController = null;
    let debounceTimer = null;

    const actualizarTabla = async (url) => {
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

            if (!res.ok) throw new Error('Error al cargar la tabla de auditoría');

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

    // Paginación y Modales Delegados en contenedor y document
    document.addEventListener('click', (e) => {
        // Paginación AJAX
        const linkPaginacion = e.target.closest('#auditoriaTabla nav a');
        if (linkPaginacion) {
            e.preventDefault();
            const href = linkPaginacion.getAttribute('href');
            if (href) actualizarTabla(href);
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

        // Cerrar modal con botón
        const btnClose = e.target.closest('[data-modal-close]');
        if (btnClose) {
            const dialog = btnClose.closest('dialog');
            if (dialog) dialog.close();
            return;
        }

        // Cerrar al hacer clic en el backdrop
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
})();
