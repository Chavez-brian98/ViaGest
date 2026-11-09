import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // ----------------------------------------------------
    // 1. Manejo del listado (Búsqueda, Filtros y Paginación)
    // ----------------------------------------------------
    const inputBuscar = document.getElementById('paquetesBuscar');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroEstado = document.getElementById('filtroEstado');
    const filtroDestino = document.getElementById('filtroDestino');
    const contenedorTabla = document.getElementById('paquetesTabla');

    let debounceTimer = null;

    const actualizarListado = async (urlBase, parametros = {}) => {
        if (!contenedorTabla) return;

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
            ciudad_destino_id: filtroDestino ? filtroDestino.value : '',
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

    [filtroTipo, filtroEstado, filtroDestino].forEach((select) => {
        if (select) {
            select.addEventListener('change', () => {
                const url = inputBuscar ? inputBuscar.dataset.url : window.location.pathname;
                actualizarListado(url, obtenerParametrosActuales());
            });
        }
    });

    const inicializarEventosDinamicos = () => {
        if (!contenedorTabla) return;

        const enlacesPaginacion = contenedorTabla.querySelectorAll('nav[role="navigation"] a');
        enlacesPaginacion.forEach((enlace) => {
            enlace.addEventListener('click', (e) => {
                e.preventDefault();
                actualizarListado(enlace.href, {});
            });
        });

        const formulariosEliminar = contenedorTabla.querySelectorAll('[data-form-eliminar]');
        formulariosEliminar.forEach((form) => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const nombre = form.dataset.nombre || 'este paquete';

                Swal.fire({
                    title: '¿Eliminar paquete?',
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

        const botonesCambiarEstado = contenedorTabla.querySelectorAll('[data-btn-cambiar-estado]');
        botonesCambiarEstado.forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                const estadoActual = btn.dataset.estadoActual;

                Swal.fire({
                    title: 'Cambiar estado del paquete',
                    input: 'select',
                    inputOptions: {
                        borrador: 'Borrador',
                        activo: 'Activo',
                        inactivo: 'Inactivo',
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
                            text: 'El estado del paquete fue actualizado correctamente.',
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

    // ----------------------------------------------------
    // 2. Formulario de Paquete: Autocomplete por Categoría y Costeo
    // ----------------------------------------------------
    const formPaquete = document.getElementById('formPaquete');
    if (!formPaquete) return;

    const urlBuscarServicios = formPaquete.dataset.urlBuscarServicios;
    const btnAgregarServicio = document.getElementById('btnAgregarServicio');
    const contenedorFilas = document.getElementById('contenedorFilasServicios');
    const tplFila = document.getElementById('tplFilaServicio');
    const selectMargenTipo = document.getElementById('margen_tipo');
    const inputMargenValor = document.getElementById('margen_valor');

    const elCostoNeto = document.getElementById('resumenCostoNeto');
    const elPrecioVenta = document.getElementById('resumenPrecioVenta');
    const elUtilidad = document.getElementById('resumenUtilidad');

    let contadorFilas = contenedorFilas ? contenedorFilas.querySelectorAll('.fila-servicio').length : 0;

    const etiquetasCategorias = {
        vuelo: '✈️ Vuelos',
        hospedaje: '🏨 Hospedajes',
        traslado: '🚐 Traslados',
        actividad: '🎟️ Actividades',
        guia: '🧭 Guías',
        seguro: '🛡️ Seguros',
        otro: '📦 Otros',
    };

    const obtenerIdsServiciosSeleccionados = () => {
        const ids = [];
        const inputs = contenedorFilas.querySelectorAll('.input-servicio-id');
        inputs.forEach((input) => {
            const val = input.value.trim();
            if (val !== '') {
                ids.push(parseInt(val, 10));
            }
        });
        return ids;
    };

    const recalcularTotales = () => {
        let costoTotal = 0;
        const filas = contenedorFilas.querySelectorAll('.fila-servicio');

        filas.forEach((fila) => {
            const cellCosto = fila.querySelector('.cell-costo');
            const inputCantidad = fila.querySelector('.input-cantidad');
            const cellSubtotal = fila.querySelector('.cell-subtotal');

            const costoUnitario = cellCosto && cellCosto.dataset.costo ? parseFloat(cellCosto.dataset.costo) : 0;
            const cantidad = inputCantidad && inputCantidad.value ? parseFloat(inputCantidad.value) : 0;

            const subtotal = costoUnitario * cantidad;
            costoTotal += subtotal;

            if (cellCosto) cellCosto.textContent = `$${costoUnitario.toFixed(2)}`;
            if (cellSubtotal) cellSubtotal.textContent = `$${subtotal.toFixed(2)}`;
        });

        const tipoMargen = selectMargenTipo ? selectMargenTipo.value : 'porcentaje';
        const valorMargen = inputMargenValor && inputMargenValor.value ? parseFloat(inputMargenValor.value) : 0;

        let precioVenta = costoTotal;
        let utilidad = 0;

        if (tipoMargen === 'porcentaje') {
            utilidad = costoTotal * (valorMargen / 100);
            precioVenta = costoTotal + utilidad;
        } else {
            utilidad = valorMargen;
            precioVenta = costoTotal + utilidad;
        }

        if (elCostoNeto) elCostoNeto.textContent = `$${costoTotal.toFixed(2)}`;
        if (elPrecioVenta) elPrecioVenta.textContent = `$${precioVenta.toFixed(2)}`;
        if (elUtilidad) elUtilidad.textContent = `+ Utilidad: $${utilidad.toFixed(2)}`;
    };

    const inicializarAutocompleteFila = (fila) => {
        const wrapper = fila.querySelector('.autocomplete-wrapper');
        if (!wrapper) return;

        const inputHidden = wrapper.querySelector('.input-servicio-id');
        const inputTexto = wrapper.querySelector('.input-buscar-servicio');
        const btnLimpiar = wrapper.querySelector('.btn-limpiar-seleccion');
        const dropdown = wrapper.querySelector('.dropdown-resultados');
        const cellCosto = fila.querySelector('.cell-costo');
        const inputCantidad = fila.querySelector('.input-cantidad');
        const btnQuitar = fila.querySelector('.btn-quitar-fila');

        let debounceBuscar = null;

        const cerrarDropdown = () => {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        };

        const buscarServiciosRemotos = async (termino) => {
            const excluidos = obtenerIdsServiciosSeleccionados();
            const url = new URL(urlBuscarServicios, window.location.origin);
            url.searchParams.set('q', termino);
            excluidos.forEach((id) => url.searchParams.append('excluir[]', id));

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) return;

                const servicios = await response.json();
                renderizarDropdown(servicios);
            } catch (err) {
                console.error(err);
            }
        };

        const renderizarDropdown = (servicios) => {
            if (!servicios || servicios.length === 0) {
                dropdown.innerHTML = `
                    <div class="px-4 py-3 text-center text-xs text-ink/40">
                        No se encontraron servicios disponibles con ese término.
                    </div>
                `;
                dropdown.classList.remove('hidden');
                return;
            }

            // Agrupar por categoría / tipo
            const agrupados = {};
            servicios.forEach((s) => {
                const cat = s.tipo || 'otro';
                if (!agrupados[cat]) agrupados[cat] = [];
                agrupados[cat].push(s);
            });

            let html = '';
            Object.keys(agrupados).forEach((categoria) => {
                const tituloCategoria = etiquetasCategorias[categoria] || categoria.toUpperCase();
                html += `
                    <div class="border-b border-ink/5 px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-ink/40 bg-background/50">
                        ${tituloCategoria}
                    </div>
                `;

                agrupados[categoria].forEach((s) => {
                    const costo = parseFloat(s.costo_neto || 0).toFixed(2);
                    html += `
                        <div
                            class="item-resultado-servicio flex cursor-pointer items-center justify-between px-3 py-2 text-xs transition hover:bg-secondary/10"
                            data-id="${s.id}"
                            data-codigo="${s.codigo}"
                            data-nombre="${s.nombre}"
                            data-tipo="${s.tipo}"
                            data-costo="${costo}"
                        >
                            <div class="min-w-0 pr-2">
                                <span class="font-mono font-semibold text-ink/70">[${s.codigo}]</span>
                                <span class="font-medium text-ink ml-1">${s.nombre}</span>
                                <span class="text-[11px] text-ink/40 block truncate">${s.proveedor_nombre || 'Sin proveedor'}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="font-mono font-semibold text-ink/80">$${costo}</span>
                            </div>
                        </div>
                    `;
                });
            });

            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');

            dropdown.querySelectorAll('.item-resultado-servicio').forEach((item) => {
                item.addEventListener('click', () => {
                    const id = item.dataset.id;
                    const codigo = item.dataset.codigo;
                    const nombre = item.dataset.nombre;
                    const tipo = item.dataset.tipo;
                    const costo = parseFloat(item.dataset.costo);

                    inputHidden.value = id;
                    inputTexto.value = `[${codigo}] ${nombre} (${tipo})`;
                    btnLimpiar.classList.remove('hidden');
                    cellCosto.dataset.costo = costo;
                    cellCosto.textContent = `$${costo.toFixed(2)}`;

                    cerrarDropdown();
                    recalcularTotales();
                });
            });
        };

        inputTexto.addEventListener('focus', () => {
            if (!inputHidden.value) {
                buscarServiciosRemotos(inputTexto.value.trim());
            }
        });

        inputTexto.addEventListener('input', () => {
            if (inputHidden.value) {
                inputHidden.value = '';
                cellCosto.dataset.costo = '0';
                cellCosto.textContent = '$0.00';
                btnLimpiar.classList.add('hidden');
                recalcularTotales();
            }

            clearTimeout(debounceBuscar);
            debounceBuscar = setTimeout(() => {
                buscarServiciosRemotos(inputTexto.value.trim());
            }, 300);
        });

        btnLimpiar.addEventListener('click', () => {
            inputHidden.value = '';
            inputTexto.value = '';
            cellCosto.dataset.costo = '0';
            cellCosto.textContent = '$0.00';
            btnLimpiar.classList.add('hidden');
            recalcularTotales();
            inputTexto.focus();
            buscarServiciosRemotos('');
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                cerrarDropdown();
            }
        });

        if (inputCantidad) {
            inputCantidad.addEventListener('input', recalcularTotales);
        }

        if (btnQuitar) {
            btnQuitar.addEventListener('click', () => {
                fila.remove();
                recalcularTotales();
            });
        }
    };

    if (contenedorFilas) {
        contenedorFilas.querySelectorAll('.fila-servicio').forEach(inicializarAutocompleteFila);
    }

    if (btnAgregarServicio && tplFila && contenedorFilas) {
        btnAgregarServicio.addEventListener('click', () => {
            const html = tplFila.innerHTML.replaceAll('__INDEX__', contadorFilas++);
            const tr = document.createElement('tr');
            tr.className = 'fila-servicio transition-colors hover:bg-background/50';
            tr.dataset.indice = contadorFilas;
            tr.innerHTML = html.replace(/<tr[^>]*>|<\/tr>/g, '');

            contenedorFilas.appendChild(tr);
            inicializarAutocompleteFila(tr);
            recalcularTotales();

            const inputNuevo = tr.querySelector('.input-buscar-servicio');
            if (inputNuevo) {
                inputNuevo.focus();
            }
        });
    }

    if (selectMargenTipo) selectMargenTipo.addEventListener('change', recalcularTotales);
    if (inputMargenValor) inputMargenValor.addEventListener('input', recalcularTotales);

    recalcularTotales();
});
