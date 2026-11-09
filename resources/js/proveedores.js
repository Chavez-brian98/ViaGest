import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('proveedoresBuscar');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroEstado = document.getElementById('filtroEstado');
    const contenedorTabla = document.getElementById('proveedoresTabla');

    let debounceTimer = null;

    const actualizarListado = async (urlBase, parametros = {}) => {
        const url = new URL(urlBase, window.location.origin);

        Object.keys(parametros).forEach((key) => {
            if (parametros[key] !== '') {
                url.searchParams.set(key, parametros[key]);
            } else {
                url.searchParams.delete(key);
            }
        });

        try {
            contenedorTabla.style.opacity = '0.5';
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) throw new Error('Error al actualizar el listado');

            const html = await response.text();
            contenedorTabla.innerHTML = html;

            // Actualiza la URL del navegador sin recargar la página
            window.history.replaceState({}, '', url.toString());
            inicializarEventosDinamicos();
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'No se pudo actualizar la tabla.',
            });
        } finally {
            contenedorTabla.style.opacity = '1';
        }
    };

    const obtenerParametrosActuales = () => {
        return {
            buscar: inputBuscar ? inputBuscar.value : '',
            tipo: filtroTipo ? filtroTipo.value : '',
            estado: filtroEstado ? filtroEstado.value : '',
        };
    };

    // Búsqueda en tiempo real con debounce
    inputBuscar?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const urlBase = inputBuscar.dataset.url || window.location.pathname;
            actualizarListado(urlBase, obtenerParametrosActuales());
        }, 300);
    });

    // Filtros por select
    [filtroTipo, filtroEstado].forEach((filtro) => {
        filtro?.addEventListener('change', () => {
            const urlBase = inputBuscar?.dataset.url || window.location.pathname;
            actualizarListado(urlBase, obtenerParametrosActuales());
        });
    });

    // Interceptar paginación por AJAX si se desea
    document.addEventListener('click', (e) => {
        const linkPaginacion = e.target.closest('#proveedoresTabla nav a');
        if (linkPaginacion) {
            e.preventDefault();
            const url = linkPaginacion.href;
            const params = new URL(url).searchParams;
            const filtros = {
                buscar: params.get('buscar') || '',
                tipo: params.get('tipo') || '',
                estado: params.get('estado') || '',
                page: params.get('page') || '1',
            };
            actualizarListado(window.location.pathname, filtros);
        }
    });

    // Inicializar modales nativos y acciones de cambio de estado / eliminación
    const inicializarEventosDinamicos = () => {
        // Manejo de modales nativos (<dialog>)
        document.querySelectorAll('[data-modal-open]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal-open');
                const modal = document.getElementById(modalId);
                modal?.showModal();
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('dialog');
                modal?.close();
            });
        });

        // Cambio de estado dinámico (Switch)
        document.querySelectorAll('form[data-estado-form]').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const url = form.action;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        const data = await response.json();
                        throw new Error(data.message || 'No se pudo cambiar el estado.');
                    }

                    // Refrescar tabla manteniendo filtros actuales
                    actualizarListado(window.location.pathname, obtenerParametrosActuales());
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Acción denegada',
                        text: error.message,
                        confirmButtonColor: '#0b4f6c',
                    });
                }
            });
        });

        // Confirmación de eliminación con SweetAlert2
        document.querySelectorAll('form[data-confirm="delete"]').forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta acción eliminará el registro permanentemente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#0b4f6c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    };

    inicializarEventosDinamicos();
});
