import Swal from 'sweetalert2';

const contenedor = document.getElementById('clientesTabla');
const buscador = document.getElementById('clientesBuscar');
const filtros = [...document.querySelectorAll('[data-filtro]')];
const limpiar = document.querySelector('[data-limpiar-filtros]');

const COLOR_PRIMARY = '#0b4f6c';
const COLOR_PELIGRO = '#e11d48';
const COLOR_ADVERTENCIA = '#f59e0b';

const escapar = texto => {
    const elemento = document.createElement('div');
    elemento.textContent = texto;
    return elemento.innerHTML;
};

let controlador = null;
let temporizador = null;
let solicitud = 0;

const actualizarUrl = (url, reemplazar = true) => {
    if (reemplazar) {
        window.history.replaceState({}, '', url);
        return;
    }

    window.history.pushState({}, '', url);
};

const sincronizarControles = url => {
    const parametros = new URL(url, window.location.origin).searchParams;

    if (buscador) {
        buscador.value = parametros.get('buscar') ?? '';
    }

    filtros.forEach(filtro => {
        filtro.value = parametros.get(filtro.name) ?? '';
    });
};

const cargar = async (url, { reemplazarUrl = true, enfocarBuscador = false } = {}) => {
    if (!contenedor) {
        return;
    }

    controlador?.abort();
    controlador = new AbortController();

    const actual = ++solicitud;

    contenedor.setAttribute('aria-busy', 'true');
    contenedor.classList.add('opacity-60', 'transition-opacity');

    try {
        const respuesta = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            signal: controlador.signal,
            credentials: 'same-origin',
        });

        if (respuesta.redirected) {
            window.location.href = respuesta.url;
            return;
        }

        if (!respuesta.ok) {
            throw new Error(`Respuesta ${respuesta.status}`);
        }

        contenedor.innerHTML = await respuesta.text();
        actualizarUrl(url, reemplazarUrl);
        sincronizarControles(url);
        contenedor.scrollTop = 0;

        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            contenedor.animate(
                [
                    { opacity: 0.35, transform: 'translateY(5px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ],
                { duration: 190, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
            );
        }

        if (enfocarBuscador) {
            buscador?.focus();
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            ventanaError();
        }
    } finally {
        if (actual === solicitud) {
            contenedor.removeAttribute('aria-busy');
            contenedor.classList.remove('opacity-60', 'transition-opacity');
        }
    }
};

const ventanaError = (titulo = 'No se pudo actualizar el listado', texto = 'Revisa tu conexión e inténtalo de nuevo.') => {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: texto,
        confirmButtonText: 'Entendido',
        confirmButtonColor: COLOR_PRIMARY,
    });
};

const aplicarEstado = (formulario, estado) => {
    const activo = estado === 'activo';
    const fila = formulario.closest('[data-fila-cliente]');
    const boton = formulario.querySelector('[data-estado-switch]');
    const texto = fila?.querySelector('[data-estado-texto]');
    const input = formulario.querySelector('input[name="estado_destino"]');
    const eliminar = fila?.querySelector('[data-eliminar-cliente]');
    const nombre = formulario.dataset.clienteNombre ?? '';

    formulario.dataset.estadoActual = estado;

    if (input) {
        input.value = activo ? 'inactivo' : 'activo';
    }

    boton?.setAttribute('aria-checked', activo ? 'true' : 'false');
    boton?.setAttribute('title', activo ? 'Desactivar' : 'Activar');
    boton?.setAttribute('aria-label', `${activo ? 'Desactivar' : 'Activar'} ${nombre}`.trim());

    if (texto) {
        texto.dataset.estado = estado;
        texto.textContent = activo ? 'Activo' : 'Inactivo';
    }

    eliminar?.classList.toggle('hidden', activo);
};

const urlDeListado = () => {
    const url = new URL(window.location.href);

    const parametros = {
        buscar: buscador?.value.trim() ?? '',
        ...Object.fromEntries(filtros.map(filtro => [filtro.name, filtro.value])),
    };

    Object.entries(parametros).forEach(([nombre, valor]) => {
        if (valor === '') {
            url.searchParams.delete(nombre);
        } else {
            url.searchParams.set(nombre, valor);
        }
    });

    url.searchParams.delete('page');

    return url.toString();
};

const buscar = () => {
    window.clearTimeout(temporizador);
    cargar(urlDeListado(), { reemplazarUrl: true });
};

buscador?.addEventListener('input', () => {
    window.clearTimeout(temporizador);
    temporizador = window.setTimeout(buscar, 300);
});

filtros.forEach(filtro => {
    filtro.addEventListener('change', buscar);
});

document.getElementById('clientesFiltros')?.addEventListener('submit', evento => {
    evento.preventDefault();
    buscar();
});

limpiar?.addEventListener('click', evento => {
    evento.preventDefault();

    if (buscador) {
        buscador.value = '';
    }

    filtros.forEach(filtro => {
        filtro.value = '';
    });

    cargar(limpiar.href, { reemplazarUrl: true });
});

contenedor?.addEventListener('click', evento => {
    if (evento.button !== 0 || evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) {
        return;
    }

    const enlace = evento.target.closest('nav a[href]');

    if (!enlace || enlace.origin !== window.location.origin) {
        return;
    }

    evento.preventDefault();
    cargar(enlace.href, { reemplazarUrl: false, enfocarBuscador: true });
});

window.addEventListener('popstate', () => {
    cargar(window.location.href, { reemplazarUrl: true, enfocarBuscador: true });
});

document.addEventListener('click', evento => {
    const abrir = evento.target.closest('[data-modal-open]');

    if (abrir) {
        document.getElementById(abrir.dataset.modalOpen)?.showModal();
        return;
    }

    const cerrar = evento.target.closest('[data-modal-close]');

    if (cerrar) {
        cerrar.closest('dialog')?.close();
        return;
    }

    const fondo = evento.target.closest('dialog');

    if (fondo && evento.target === fondo) {
        fondo.close();
    }
});

document.addEventListener('submit', async evento => {
    const formulario = evento.target.closest('form[data-estado-form]');

    if (!formulario || formulario.dataset.enviando === '1') {
        return;
    }

    evento.preventDefault();

    const boton = formulario.querySelector('[data-estado-switch]');
    const estadoAnterior = formulario.dataset.estadoActual ?? 'activo';
    const estadoNuevo = formulario.querySelector('input[name="estado_destino"]')?.value;

    if (!estadoNuevo) {
        return;
    }

    const datos = new FormData(formulario);
    formulario.dataset.enviando = '1';

    if (boton) {
        boton.disabled = true;
    }

    aplicarEstado(formulario, estadoNuevo);

    try {
        const respuesta = await fetch(formulario.action, {
            method: 'POST',
            body: datos,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (respuesta.redirected) {
            window.location.href = respuesta.url;
            return;
        }

        if (!respuesta.ok) {
            throw new Error(`Respuesta ${respuesta.status}`);
        }

        const resultado = await respuesta.json();
        const estadoFinal = ['activo', 'inactivo'].includes(resultado.estado) ? resultado.estado : estadoNuevo;
        aplicarEstado(formulario, estadoFinal);

        const filtroEstado = new URL(window.location.href).searchParams.get('estado');

        if (filtroEstado && filtroEstado !== estadoFinal) {
            await new Promise(resolve => window.setTimeout(resolve, 320));
            cargar(window.location.href, { reemplazarUrl: true });
        }
    } catch (error) {
        aplicarEstado(formulario, estadoAnterior);
        ventanaError(
            'No se pudo cambiar el estado',
            'El cliente conserva su estado anterior. Revisa tu conexión e inténtalo de nuevo.',
        );
    } finally {
        delete formulario.dataset.enviando;

        if (boton) {
            boton.disabled = false;
        }
    }
});

document.addEventListener('submit', evento => {
    const formulario = evento.target.closest('form[data-confirm]');

    if (!formulario || formulario.dataset.confirmado === '1') {
        return;
    }

    evento.preventDefault();

    const eliminar = formulario.dataset.confirm === 'delete';
    const nombre = formulario.dataset.clienteNombre ?? 'este cliente';

    Swal.fire({
        icon: 'warning',
        title: eliminar ? '¿Eliminar cliente?' : '¿Deshabilitar cliente?',
        html: eliminar
            ? `<p class="text-sm text-slate-600">Se eliminará permanentemente a <strong>${escapar(nombre)}</strong>. Esta acción no se puede deshacer.</p>`
            : `<p class="text-sm text-slate-600"><strong>${escapar(nombre)}</strong> quedará inactivo. Podrás eliminarlo después si no tiene registros asociados.</p>`,
        confirmButtonText: eliminar ? 'Sí, eliminar' : 'Sí, deshabilitar',
        cancelButtonText: 'Cancelar',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: eliminar ? COLOR_PELIGRO : COLOR_ADVERTENCIA,
        cancelButtonColor: '#64748b',
    }).then(resultado => {
        if (!resultado.isConfirmed) {
            return;
        }

        formulario.dataset.confirmado = '1';
        formulario.submit();
    });
});
