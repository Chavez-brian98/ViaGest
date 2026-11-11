import Swal from 'sweetalert2';

(() => {
    const contenedor = document.getElementById('cotizacionesTabla');
    const buscador = document.getElementById('cotizacionesBuscar');
    const formFiltros = document.getElementById('cotizacionesFiltros');
    const filtros = formFiltros ? [...formFiltros.querySelectorAll('[data-filtro]')] : [];
    const limpiar = document.querySelector('[data-limpiar-filtros]');

    const COLOR_PRIMARY = '#0b4f6c';
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

            if (!res.ok) throw new Error('Error al cargar la lista de cotizaciones');

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

    // Delegación de eventos para modales y paginación
    document.addEventListener('click', (e) => {
        // Paginación AJAX
        const linkPaginacion = e.target.closest('#cotizacionesTabla nav a');
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

    // Confirmación al eliminar cotización
    document.addEventListener('submit', async (e) => {
        const formEliminar = e.target.closest('[data-eliminar-cotizacion]');
        if (!formEliminar) return;

        e.preventDefault();
        const codigo = formEliminar.dataset.cotizacionCodigo || 'esta cotización';

        const resultado = await Swal.fire({
            title: '¿Eliminar cotización?',
            text: `Se eliminará permanentemente la cotización ${codigo} y todos sus servicios presupuestados.`,
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

    // -------------------------------------------------------------------------
    // LÓGICA DE DETALLE Y CÁLCULOS EN FORMULARIO CREATE / EDIT
    // -------------------------------------------------------------------------
    const formCotizacion = document.getElementById('formCotizacion');
    if (!formCotizacion) return;

    const tbodyServicios = document.getElementById('tbodyServiciosCotizacion');
    const selectServicio = document.getElementById('selectServicioAgregar');
    const btnAgregar = document.getElementById('btnAgregarServicio');
    const btnImportarPaquete = document.getElementById('btnImportarPaquete');
    const selectPaquete = document.getElementById('paquete_id');
    const inputDescuento = document.getElementById('descuento_monto');

    let contadorFila = tbodyServicios ? tbodyServicios.querySelectorAll('tr.fila-servicio').length : 0;

    const recalcularTotales = () => {
        if (!tbodyServicios) return;

        let subtotal = 0;
        let costoTotal = 0;

        tbodyServicios.querySelectorAll('tr.fila-servicio').forEach((fila) => {
            const cantInput = fila.querySelector('.input-cantidad');
            const costoInput = fila.querySelector('.input-costo');
            const precioInput = fila.querySelector('.input-precio');
            const subtotalSpan = fila.querySelector('.span-subtotal');

            const cantidad = parseFloat(cantInput?.value || 0);
            const costo = parseFloat(costoInput?.value || 0);
            const precio = parseFloat(precioInput?.value || 0);

            const filaSubtotal = cantidad * precio;
            const filaCosto = cantidad * costo;

            subtotal += filaSubtotal;
            costoTotal += filaCosto;

            if (subtotalSpan) {
                subtotalSpan.textContent = `$${filaSubtotal.toFixed(2)}`;
            }
        });

        const descuento = parseFloat(inputDescuento?.value || 0);
        const total = Math.max(0, subtotal - descuento);
        const utilidad = total - costoTotal;

        const subtotalEl = document.getElementById('resumenSubtotal');
        const costoEl = document.getElementById('resumenCostoNeto');
        const totalEl = document.getElementById('resumenTotal');
        const utilidadEl = document.getElementById('resumenUtilidad');

        if (subtotalEl) subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        if (costoEl) costoEl.textContent = `$${costoTotal.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `$${total.toFixed(2)}`;
        if (utilidadEl) {
            utilidadEl.textContent = `$${utilidad.toFixed(2)}`;
            utilidadEl.classList.toggle('text-rose-600', utilidad < 0);
            utilidadEl.classList.toggle('text-emerald-600', utilidad >= 0);
        }

        const filaSinServicios = document.getElementById('filaSinServicios');
        const tieneFilas = tbodyServicios.querySelectorAll('tr.fila-servicio').length > 0;
        if (filaSinServicios) {
            filaSinServicios.style.display = tieneFilas ? 'none' : '';
        }
    };

    const agregarFilaServicio = (servicio) => {
        if (!tbodyServicios) return;

        const filaSinServicios = document.getElementById('filaSinServicios');
        if (filaSinServicios) filaSinServicios.style.display = 'none';

        const idx = contadorFila++;
        const tr = document.createElement('tr');
        tr.className = 'fila-servicio hover:bg-background/40';
        tr.dataset.idx = idx;

        tr.innerHTML = `
            <td class="px-4 py-2.5">
                <input type="hidden" name="servicios[${idx}][servicio_id]" value="${servicio.id}">
                <p class="font-medium text-ink">${servicio.nombre}</p>
                <p class="text-[10px] text-ink/45 font-mono">${servicio.codigo || ''}</p>
            </td>
            <td class="px-3 py-2.5 text-center">
                <input
                    type="number"
                    name="servicios[${idx}][cantidad]"
                    step="0.01"
                    min="0.01"
                    value="${servicio.cantidad || 1}"
                    class="w-20 text-center rounded-lg border border-ink/15 bg-background p-1 text-xs input-cantidad"
                >
            </td>
            <td class="px-3 py-2.5 text-right">
                <input
                    type="number"
                    name="servicios[${idx}][costo_neto_unitario]"
                    step="0.01"
                    min="0"
                    value="${servicio.costo || 0}"
                    class="w-24 text-right rounded-lg border border-ink/15 bg-background p-1 text-xs input-costo"
                >
            </td>
            <td class="px-3 py-2.5 text-right">
                <input
                    type="number"
                    name="servicios[${idx}][precio_unitario]"
                    step="0.01"
                    min="0"
                    value="${servicio.precio || 0}"
                    class="w-24 text-right rounded-lg border border-ink/15 bg-background p-1 text-xs input-precio font-semibold text-primary"
                >
            </td>
            <td class="px-3 py-2.5 text-right font-mono font-semibold text-ink span-subtotal">
                $0.00
            </td>
            <td class="px-3 py-2.5 text-center">
                <button type="button" class="text-ink/40 hover:text-rose-600 btn-eliminar-fila">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </td>
        `;

        tbodyServicios.appendChild(tr);
        recalcularTotales();
    };

    if (btnAgregar && selectServicio) {
        btnAgregar.addEventListener('click', () => {
            const opcion = selectServicio.options[selectServicio.selectedIndex];
            if (!opcion || !opcion.value) return;

            agregarFilaServicio({
                id: opcion.value,
                codigo: opcion.dataset.codigo,
                nombre: opcion.dataset.nombre,
                costo: opcion.dataset.costo,
                precio: opcion.dataset.precio,
                cantidad: 1,
            });

            selectServicio.value = '';
        });
    }

    if (btnImportarPaquete && selectPaquete) {
        btnImportarPaquete.addEventListener('click', async () => {
            const paqueteId = selectPaquete.value;
            if (!paqueteId) {
                Swal.fire({
                    title: 'Selecciona un paquete',
                    text: 'Debes elegir un paquete en el campo superior antes de importar sus servicios.',
                    icon: 'info',
                    confirmButtonColor: COLOR_PRIMARY,
                });
                return;
            }

            try {
                const res = await fetch(`/cotizaciones/servicios-paquete/${paqueteId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) throw new Error('Error al obtener servicios del paquete');

                const servicios = await res.json();
                if (!Array.isArray(servicios) || servicios.length === 0) {
                    Swal.fire('Sin servicios', 'Este paquete no tiene servicios vinculados.', 'warning');
                    return;
                }

                servicios.forEach((srv) => {
                    agregarFilaServicio({
                        id: srv.servicio_id,
                        codigo: srv.codigo,
                        nombre: srv.nombre,
                        costo: srv.costo_neto_unitario,
                        precio: srv.precio_unitario,
                        cantidad: srv.cantidad || 1,
                    });
                });

                Swal.fire({
                    title: '¡Servicios importados!',
                    text: `Se agregaron ${servicios.length} servicios del paquete.`,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                });
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'No se pudieron importar los servicios del paquete.', 'error');
            }
        });
    }

    if (tbodyServicios) {
        tbodyServicios.addEventListener('input', (e) => {
            if (e.target.matches('.input-cantidad, .input-costo, .input-precio')) {
                recalcularTotales();
            }
        });

        tbodyServicios.addEventListener('click', (e) => {
            const btnEliminar = e.target.closest('.btn-eliminar-fila');
            if (btnEliminar) {
                const fila = btnEliminar.closest('tr');
                fila?.remove();
                recalcularTotales();
            }
        });
    }

    if (inputDescuento) {
        inputDescuento.addEventListener('input', recalcularTotales);
    }

    // Inicializar cálculos de servicios pre-cargados
    recalcularTotales();
})();
