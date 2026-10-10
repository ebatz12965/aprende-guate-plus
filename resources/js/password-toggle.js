/**
 * Botón «ver contraseña» para todos los campos de contraseña de la plataforma.
 * Se agrega solo (también a los campos que aparecen después, por ejemplo en un formulario que se abre),
 * así ninguna pantalla tiene que acordarse de incluirlo. Un campo con data-password-toggle="off" se respeta.
 */
const EYE = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_OFF = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.6 6.6C3.9 8.4 2 12 2 12s3.5 7 10 7c1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';

let counter = 0;

function enhance(input) {
    if (input.dataset.passwordToggle === 'off' || input.dataset.passwordToggleReady) return;
    input.dataset.passwordToggleReady = '1';
    if (!input.id) input.id = `password-field-${++counter}`;

    const style = window.getComputedStyle(input);
    const wrapper = document.createElement('div');
    wrapper.className = 'ag-password-field';
    // El margen pasa al contenedor para que el botón quede centrado sobre el campo.
    Object.assign(wrapper.style, { position: 'relative', marginTop: style.marginTop, marginBottom: style.marginBottom, flex: style.flex });
    if (style.display === 'block' || input.classList.contains('w-full')) wrapper.style.display = 'block';
    input.style.marginTop = '0';
    input.style.marginBottom = '0';
    input.style.paddingRight = '2.75rem';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ag-password-toggle';
    button.setAttribute('aria-controls', input.id);
    Object.assign(button.style, {
        position: 'absolute', top: '50%', right: '0.375rem', transform: 'translateY(-50%)', display: 'grid', placeItems: 'center',
        width: '2.25rem', height: '2.25rem', border: '0', borderRadius: '0.5rem', background: 'transparent', color: '#64748B', cursor: 'pointer',
    });

    const render = () => {
        const visible = input.type === 'text';
        button.innerHTML = visible ? EYE_OFF : EYE;
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    };
    button.addEventListener('click', () => {
        // Se conserva el cursor donde estaba al cambiar entre ver y ocultar.
        const { selectionStart, selectionEnd } = input;
        input.type = input.type === 'password' ? 'text' : 'password';
        render();
        input.focus();
        try { input.setSelectionRange(selectionStart, selectionEnd); } catch (e) {}
    });
    button.addEventListener('mouseenter', () => { button.style.color = '#1565C0'; });
    button.addEventListener('mouseleave', () => { button.style.color = '#64748B'; });
    // Al enviar el formulario la contraseña vuelve a ocultarse (el navegador no la guarda como texto).
    input.form?.addEventListener('submit', () => { input.type = 'password'; render(); });

    render();
    wrapper.appendChild(button);
}

function scan(root = document) {
    root.querySelectorAll?.('input[type="password"]').forEach(enhance);
}

export function startPasswordToggles() {
    scan();
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
            if (node.nodeType !== 1) return;
            if (node.matches?.('input[type="password"]')) enhance(node);
            else scan(node);
        }));
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startPasswordToggles, { once: true });
else startPasswordToggles();
