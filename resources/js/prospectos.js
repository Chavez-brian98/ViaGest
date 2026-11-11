import Swal from 'sweetalert2';

(() => {
    const contenedor = document.getElementById('prospectosTabla');
    const buscador = document.getElementById('prospectosBuscar');
    const formFiltros = document.getElementById('prospectosFiltros');
    const filtros = formFiltros ? [...formFiltros.querySelectorAll('[data-filtro]')] : [];
    const limpiar = document.querySelector('[data-limpiar-filtros]');

    const COLOR_PRIMARY = '#0b4f6c';
    const COLOR_EMERALD = '#059669';
    const COLOR_ROSE = '#e11d48';

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

            if (!res.ok) throw new Error('Error al cargar la lista de prospectos');

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

    if (buscador) {
        buscador.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                actualizarTabla(construirUrlFiltros());
            }, 300);
        });

        buscador.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                actualizarTabla(construirUrlFiltros());
            }
        });
    }

    if (formFiltros) {
        filtros.forEach((select) => {
            select.addEventListener('change', () => {
                clearTimeout(debounceTimer);
                actualizarTabla(construirUrlFiltros());
            });
        });
    }

    if (limpiar) {
        limpiar.addEventListener('click', (e) => {
            e.preventDefault();
            if (formFiltros) formFiltros.reset();
            if (buscador) buscador.value = '';
            actualizarTabla(limpiar.getAttribute('href') || window.location.pathname);
        });
    }

    // Modal de confirmación para Convertir Prospecto a Cliente con SweetAlert2
    document.addEventListener('click', async (e) => {
        const btnConvertir = e.target.closest('[data-convertir-prospecto]');
        if (btnConvertir) {
            const prospectoId = btnConvertir.dataset.convertirProspecto;
            const prospectoNombre = btnConvertir.dataset.prospectoNombre;
            const prospectoCodigo = btnConvertir.dataset.prospectoCodigo;

            const resultado = await Swal.fire({
                title: '¿Convertir en cliente?',
                html: `
                    <div class="text-left text-sm space-y-3">
                        <p>Se creará un registro en el directorio de clientes para <strong>${prospectoNombre}</strong> (${prospectoCodigo}) con sus datos de contacto.</p>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">Tipo de documento (opcional):</label>
                            <select id="swal_tipo_documento" class="w-full text-xs rounded-lg border border-slate-300 p-2">
                                <option value="">Sin documento</option>
                                <option value="dui">DUI</option>
                                <option value="nit">NIT</option>
                                <option value="carnet_residente">Carnet de residente</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">Número de documento (opcional):</label>
                            <input id="swal_numero_documento" type="text" placeholder="Ej. 00000000-0" class="w-full text-xs rounded-lg border border-slate-300 p-2">
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, convertir en cliente',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: COLOR_EMERALD,
                cancelButtonColor: '#64748b',
                focusConfirm: false,
                preConfirm: () => {
                    return {
                        tipo_documento: document.getElementById('swal_tipo_documento')?.value || null,
                        numero_documento: document.getElementById('swal_numero_documento')?.value || null,
                    };
                },
            });

            if (resultado.isConfirmed) {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;

                try {
                    Swal.showLoading();
                    const res = await fetch(`/prospectos/${prospectoId}/convertir`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify(resultado.value),
                    });

                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(data.error || data.message || 'No se pudo convertir el prospecto.');
                    }

                    await Swal.fire({
                        title: '¡Convertido!',
                        text: data.mensaje || 'Prospecto convertido exitosamente.',
                        icon: 'success',
                        confirmButtonColor: COLOR_PRIMARY,
                    });

                    actualizarTabla(construirUrlFiltros());
                } catch (err) {
                    Swal.fire({
                        title: 'Error',
                        text: err.message,
                        icon: 'error',
                        confirmButtonColor: COLOR_ROSE,
                    });
                }
            }
            return;
        }

        // Paginación AJAX
        const linkPaginacion = e.target.closest('#prospectosTabla nav a');
        if (linkPaginacion) {
            e.preventDefault();
            const href = linkPaginacion.getAttribute('href');
            if (href) actualizarTabla(href);
            return;
        }

        // Apertura de modales
        const btnOpen = e.target.closest('[data-modal-open]');
        if (btnOpen) {
            const modalId = btnOpen.getAttribute('data-modal-open');
            const dialog = document.getElementById(modalId);
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
            }
            return;
        }

        // Cierre de modales
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

    // Confirmación al eliminar prospecto
    document.addEventListener('submit', async (e) => {
        const formEliminar = e.target.closest('[data-eliminar-prospecto]');
        if (!formEliminar) return;

        e.preventDefault();
        const nombre = formEliminar.dataset.prospectoNombre || 'este prospecto';

        const resultado = await Swal.fire({
            title: '¿Eliminar prospecto?',
            text: `Se eliminará permanentemente el registro de ${nombre}. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: COLOR_ROSE,
            cancelButtonColor: '#64748b',
        });

        if (resultado.isConfirmed) {
            formEliminar.submit();
        }
    });

    // Control dinámico en formulario Create/Edit para motivo de pérdida
    const selectEstado = document.getElementById('estado');
    const contenedorMotivo = document.getElementById('contenedorMotivoPerdida');

    if (selectEstado && contenedorMotivo) {
        selectEstado.addEventListener('change', () => {
            if (selectEstado.value === 'perdido') {
                contenedorMotivo.classList.remove('hidden');
            } else {
                contenedorMotivo.classList.add('hidden');
            }
        });
    }
})();
