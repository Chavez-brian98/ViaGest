(() => {
    const form = document.querySelector('[data-config-form]');

    if (!form) {
        return;
    }

    const logoInput = document.getElementById('configLogo');
    const logoPreview = document.getElementById('configLogoPreview');
    const logoFallback = document.getElementById('configLogoFallback');
    const logoNombre = document.getElementById('configLogoNombre');
    const eliminarLogo = document.getElementById('configEliminarLogo');
    const logoOriginal = form.dataset.logoOriginal || null;

    const mostrarLogoOriginal = () => {
        if (logoOriginal) {
            logoPreview?.setAttribute('src', logoOriginal);
            logoPreview?.classList.remove('hidden');
            logoFallback?.classList.add('hidden');
        } else {
            logoPreview?.classList.add('hidden');
            logoFallback?.classList.remove('hidden');
        }
    };

    logoInput?.addEventListener('change', () => {
        const archivo = logoInput.files?.[0];

        if (!archivo) {
            return;
        }

        if (eliminarLogo) {
            eliminarLogo.checked = false;
        }

        const url = URL.createObjectURL(archivo);

        if (logoPreview) {
            logoPreview.src = url;
            logoPreview.classList.remove('hidden');
            logoPreview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        }

        logoFallback?.classList.add('hidden');

        if (logoNombre) {
            logoNombre.textContent = archivo.name;
        }
    });

    eliminarLogo?.addEventListener('change', () => {
        if (eliminarLogo.checked) {
            if (logoInput) {
                logoInput.value = '';
            }

            logoPreview?.classList.add('hidden');
            logoFallback?.classList.remove('hidden');

            if (logoNombre) {
                logoNombre.textContent = 'El logo actual se eliminará al guardar.';
            }

            return;
        }

        mostrarLogoOriginal();

        if (logoNombre) {
            logoNombre.textContent = 'PNG, JPG o WebP. Máximo 2 MB.';
        }
    });

    const colores = ['primary', 'secondary', 'accent', 'background', 'surface', 'ink'];
    const preview = document.getElementById('configTemaPreview');

    const valorColor = (color) => document.querySelector(`[data-color-text="${color}"]`)?.value || '';

    const actualizarPreview = () => {
        if (!preview) {
            return;
        }

        preview.style.backgroundColor = valorColor('background');
        preview.style.color = valorColor('ink');

        preview.querySelectorAll('[data-preview-primary]').forEach((elemento) => {
            elemento.style.backgroundColor = valorColor('primary');
        });

        preview.querySelectorAll('[data-preview-secondary]').forEach((elemento) => {
            elemento.style.backgroundColor = valorColor('secondary');
        });

        preview.querySelectorAll('[data-preview-accent]').forEach((elemento) => {
            elemento.style.backgroundColor = valorColor('accent');
        });

        preview.querySelectorAll('[data-preview-surface]').forEach((elemento) => {
            elemento.style.backgroundColor = valorColor('surface');
        });
    };

    colores.forEach((color) => {
        const picker = document.querySelector(`[data-color-picker="${color}"]`);
        const text = document.querySelector(`[data-color-text="${color}"]`);

        if (!picker || !text) {
            return;
        }

        picker.addEventListener('input', () => {
            text.value = picker.value;
            actualizarPreview();
        });

        text.addEventListener('input', () => {
            if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
                picker.value = text.value;
                actualizarPreview();
            }
        });
    });

    document.querySelector('[data-reset-colores]')?.addEventListener('click', () => {
        const defaults = {
            primary: '#0b4f6c',
            secondary: '#1ca3aa',
            accent: '#f28f3b',
            background: '#f5f8fa',
            surface: '#ffffff',
            ink: '#1f2933',
        };

        Object.entries(defaults).forEach(([color, value]) => {
            const picker = document.querySelector(`[data-color-picker="${color}"]`);
            const text = document.querySelector(`[data-color-text="${color}"]`);

            if (picker) {
                picker.value = value;
            }

            if (text) {
                text.value = value;
            }
        });

        actualizarPreview();
    });

    actualizarPreview();
})();
