// ============================================================================
// public/js/admin/core/admin-base.js - REUSABLE BASE CLASSES
// ============================================================================
class AdminAjax {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.baseHeaders = {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };
    }

    // Reusable across ALL admin pages
    async request(method, url, data = null, options = {}) {
        const config = {
            method,
            headers: { ...this.baseHeaders, ...options.headers },
        };

        if (data) {
            config.body = data instanceof FormData ? data : JSON.stringify(data);
            if (data instanceof FormData) {
                delete config.headers['Content-Type'];
            }
        }

        try {
            const response = await fetch(url, config);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (error) {
            this.handleError(error, options);
            throw error;
        }
    }

    // Standard CRUD - reusable everywhere
    async create(url, data, options = {}) { return this.request('POST', url, data, options); }
    async read(url, options = {}) { return this.request('GET', url, null, options); }
    async update(url, data, options = {}) { return this.request('PUT', url, data, options); }
    async delete(url, options = {}) { return this.request('DELETE', url, null, options); }

    handleError(error, options = {}) {
        const message = options.errorMessage || error.message || 'An error occurred';
        if (options.showToast !== false) {
            AdminToast.show(message, 'error');
        }
    }

    setLoadingState(element, isLoading = true) {
        if (typeof element === 'string') element = document.querySelector(element);
        if (!element) return;

        if (isLoading) {
            element.disabled = true;
            element.dataset.originalText = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
        } else {
            element.disabled = false;
            element.innerHTML = element.dataset.originalText || 'Submit';
            delete element.dataset.originalText;
        }
    }
}

class AdminToast {
    // Reusable toast system for ALL admin pages
    static show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}
