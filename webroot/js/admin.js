'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const summary = document.querySelector('[data-validation-summary]');
    if (summary) summary.focus();

    document.querySelectorAll('form[data-cascade-parent]').forEach((form) => {
        if (!form.dataset.cascadeParent) return;
        const parent = form.elements.namedItem(form.dataset.cascadeParent);
        const child = form.elements.namedItem(form.dataset.cascadeChild);
        const status = form.querySelector('[data-cascade-status]');
        const retry = form.querySelector('[data-cascade-retry]');
        const save = form.querySelector('[data-save]');
        if (!parent || !child || !status || !retry || !save) return;

        let pending = null;
        let sequence = 0;
        const reset = (message) => {
            child.replaceChildren(new Option(message, ''));
            child.value = '';
        };

        const load = async () => {
            const current = ++sequence;
            if (pending) pending.abort();
            pending = new AbortController();
            retry.hidden = true;
            status.classList.remove('is-error');
            if (!parent.value) {
                reset('Selecciona primero la opción anterior');
                child.disabled = true;
                child.removeAttribute('aria-busy');
                save.disabled = false;
                status.textContent = '';
                return;
            }
            child.disabled = true;
            child.setAttribute('aria-busy', 'true');
            save.disabled = true;
            reset('Cargando opciones…');
            status.textContent = 'Consultando opciones disponibles…';
            const url = new URL(form.dataset.cascadeEndpoint, window.location.origin);
            if (form.dataset.cascadeMode === 'query') {
                url.searchParams.set(form.dataset.cascadeQuery, parent.value);
            } else {
                url.pathname = url.pathname.replace(/\/0$/, `/${encodeURIComponent(parent.value)}`);
            }
            try {
                const response = await fetch(url, {
                    method: 'GET', credentials: 'same-origin',
                    headers: {'Accept': 'application/json'}, signal: pending.signal,
                });
                if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
                    throw new Error('Tu sesión ha caducado. Inicia sesión de nuevo.');
                }
                const body = await response.json();
                if (!response.ok) throw new Error(body.error || 'No se pudieron cargar las opciones.');
                if (!Array.isArray(body.data)) throw new Error('La respuesta recibida no tiene el formato esperado.');
                if (current !== sequence) return;
                reset(body.data.length ? 'Selecciona una opción' : 'No hay opciones disponibles');
                body.data.forEach((item) => child.add(new Option(String(item.label), String(item.id))));
                child.disabled = body.data.length === 0;
                status.textContent = body.data.length ? `${body.data.length} opciones disponibles.` : 'No hay registros para esta selección. Crea uno en el módulo correspondiente.';
            } catch (error) {
                if (error.name === 'AbortError' || current !== sequence) return;
                reset('No se pudieron cargar las opciones');
                child.disabled = true;
                status.textContent = error.message || 'Error de conexión. Vuelve a intentarlo.';
                status.classList.add('is-error');
                retry.hidden = false;
            } finally {
                if (current === sequence) {
                    child.removeAttribute('aria-busy');
                    save.disabled = false;
                }
            }
        };

        // La selección inicial se sirve desde PHP, también al editar o devolver errores.
        if (!parent.value || child.options.length <= 1) child.disabled = true;
        parent.addEventListener('change', load);
        retry.addEventListener('click', load);
    });
});
