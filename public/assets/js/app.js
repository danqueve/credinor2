/**
 * Credinor - Main JS
 */

// Utilidad para llamadas AJAX (Fetch API) con CSRF automático
async function apiCall(endpoint, method = 'GET', data = null) {
    const url = APP_URL + endpoint;
    const options = {
        method: method,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CSRF_TOKEN
        }
    };

    if (data && method !== 'GET') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        // Si es 401/403 intentamos parsear JSON, o recargamos si no hay JSON
        if (response.status === 401) {
            window.location.href = APP_URL + '/login';
            return { ok: false, message: 'Sesión expirada' };
        }
        
        const json = await response.json();
        return json;
    } catch (error) {
        console.error('API Call Error:', error);
        return { ok: false, message: 'Error de red o servidor.' };
    }
}

// Sistema simple de Toasts para notificaciones
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const bgClass = type === 'success' ? 'bg-success' : (type === 'danger' ? 'bg-danger' : 'bg-primary');
    const icon = type === 'success' ? 'bi-check-circle' : (type === 'danger' ? 'bi-exclamation-triangle' : 'bi-info-circle');

    const toastHtml = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icon} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    // Parse HTML y agregar al DOM
    const parser = new DOMParser();
    const doc = parser.parseFromString(toastHtml, 'text/html');
    const toastElement = doc.body.firstChild;
    container.appendChild(toastElement);

    // Inicializar Bootstrap Toast
    const bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
    bsToast.show();

    // Eliminar del DOM tras ocultar
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
