(() => {
    const init = () => {
        const buscarInput = document.getElementById('empleadosBuscar');
        const filtros = document.querySelectorAll('[data-filtro]');
        const limpiarBtn = document.querySelector('[data-limpiar-filtros]');
        const tablaContainer = document.getElementById('empleadosTabla');
        const estadoForms = document.querySelectorAll('[data-estado-form]');
        const eliminarForms = document.querySelectorAll('[data-eliminar-empleado]');
        const modals = document.querySelectorAll('[data-modal-open]');
        const closeModals = document.querySelectorAll('[data-modal-close]');

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

        // Inicialización global de modales
        closeModals.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('dialog');
                if (modal) modal.close();
            });
        });

        initTablaEvents();
    };

    document.addEventListener('DOMContentLoaded', init);
})();
