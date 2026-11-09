import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    const inputBuscar = document.getElementById('cuposBuscar');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroServicio = document.getElementById('filtroServicio');
    const contenedorTabla = document.getElementById('cuposTabla');

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

            window.history.replaceState({}, '', url.toString());
            inicializarEventosDinamicos();
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar la tabla.',
            });
        } finally {
            contenedorTabla.style.opacity = '1';
        }
    };

    const obtenerParametrosActuales = () => {
        return {
            buscar: inputBuscar ? inputBuscar.value.trim() : '',
            tipo: filtroTipo ? filtroTipo.value : '',
            estado: filtroEstado ? filtroEstado.value : '',
            servicio_id: filtroServicio ? filtroServicio.value : '',
        };
    };

    if (inputBuscar) {
        inputBuscar.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                actualizarListado(inputBuscar.dataset.url || window.location.pathname, obtenerParametrosActuales());
            }, 350);
        });
    }

    [filtroTipo, filtroEstado, filtroServicio].forEach((select) => {
        if (select) {
            select.addEventListener('change', () => {
                const url = inputBuscar ? inputBuscar.dataset.url : window.location.pathname;
                actualizarListado(url, obtenerParametrosActuales());
            });
        }
    });

    const inicializarEventosDinamicos = () => {
        // Paginación ajax
        const enlacesPaginacion = contenedorTabla.querySelectorAll('nav[role="navigation"] a');
        enlacesPaginacion.forEach((enlace) => {
            enlace.addEventListener('click', (e) => {
                e.preventDefault();
                actualizarListado(enlace.href, {});
            });
        });

        // Confirmación para eliminar
        const formulariosEliminar = contenedorTabla.querySelectorAll('[data-form-eliminar]');
        formulariosEliminar.forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const nombre = form.dataset.nombre || 'este cupo';

                Swal.fire({
                    title: '¿Eliminar cupo?',
                    text: `¿Estás seguro de eliminar "${nombre}"? Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Cambiar estado rápido con SweetAlert2
        const botonesCambiarEstado = contenedorTabla.querySelectorAll('[data-btn-cambiar-estado]');
        botonesCambiarEstado.forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                const estadoActual = btn.dataset.estadoActual;

                Swal.fire({
                    title: 'Cambiar estado del cupo',
                    input: 'select',
                    inputOptions: {
                        activo: 'Activo',
                        liberado: 'Liberado',
                        cerrado: 'Cerrado',
                    },
                    inputValue: estadoActual,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0b4f6c',
                    showLoaderOnConfirm: true,
                    preConfirm: async (nuevoEstado) => {
                        if (nuevoEstado === estadoActual) {
                            return true;
                        }

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                        try {
                            const response = await fetch(url, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify({ estado_destino: nuevoEstado }),
                            });

                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Error al actualizar el estado');
                            }

                            return data;
                        } catch (error) {
                            Swal.showValidationMessage(error.message);
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading(),
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Actualizado',
                            text: 'El estado del cupo fue actualizado correctamente.',
                            timer: 1500,
                            showConfirmButton: false,
                        });
                        actualizarListado(window.location.href, obtenerParametrosActuales());
                    }
                });
            });
        });
    };

    inicializarEventosDinamicos();
});
