// public/js/admin/admin.js - Minimal bundle for Fields index
// This contains only what's needed, fully backward compatible with your existing backend

// Utilities class
class AdminUtilities {
    static debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    static extractDataAttributes(element) {
        const data = { element, id: element.dataset.id };
        Object.keys(element.dataset).forEach(key => {
            if (key !== 'id') data[key] = element.dataset[key];
        });
        return data;
    }

    static highlightText(element, searchTerm) {
        if (!searchTerm) {
            element.innerHTML = element.textContent;
            return;
        }
        
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        element.innerHTML = element.textContent.replace(regex, '<span class="search-highlight">$1</span>');
    }
}

// AJAX wrapper for your existing endpoints
class AdminAjax {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async delete(url, options = {}) {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }

    async create(url, formData, options = {}) {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }

    setLoadingState(element, isLoading = true) {
        if (!element) return;

        if (isLoading) {
            element.disabled = true;
            element.dataset.originalText = element.innerHTML;
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        } else {
            element.disabled = false;
            element.innerHTML = element.dataset.originalText || element.innerHTML;
            delete element.dataset.originalText;
        }
    }
}

// Toast notifications
class AdminToast {
    static show(message, type = 'info', duration = 5000) {
        // For now, use browser alert - can be enhanced later
        const typeMap = {
            success: '✅ SUCCESS',
            error: '❌ ERROR', 
            warning: '⚠️ WARNING',
            info: 'ℹ️ INFO'
        };
        
        alert(`${typeMap[type] || 'INFO'}: ${message}`);
    }
}

// Modal management  
class AdminModals {
    static show(modalId, data = {}) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return null;

        // Populate form fields with data
        Object.entries(data).forEach(([key, value]) => {
            const element = modalElement.querySelector(`[name="${key}"], #${key}`);
            if (element) {
                if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                    element.value = value;
                } else {
                    element.textContent = value;
                }
            }
        });

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        return modal;
    }

    static hide(modalId) {
        const modalElement = document.getElementById(modalId);
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();
    }

    static confirm(message = 'Are you sure?') {
        return confirm(message);
    }
}

// Form handling
class AdminForms {
    static async submit(formElement, options = {}) {
        if (typeof formElement === 'string') {
            formElement = document.getElementById(formElement);
        }

        const ajax = new AdminAjax();
        const formData = new FormData(formElement);
        const submitButton = formElement.querySelector('[type="submit"]');

        try {
            if (submitButton) ajax.setLoadingState(submitButton, true);

            const response = await ajax.create(
                formElement.action || window.location.href, 
                formData,
                options
            );

            if (response.success) {
                AdminToast.show(response.message || 'Operation completed successfully', 'success');
                
                if (options.onSuccess) {
                    options.onSuccess(response);
                } else if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    window.location.reload();
                }
            }

            return response;
        } catch (error) {
            AdminToast.show(error.message || 'An error occurred', 'error');
            throw error;
        } finally {
            if (submitButton) ajax.setLoadingState(submitButton, false);
        }
    }
}

// Main filtering class
class AdminFiltering {
    constructor(config) {
        this.config = config;
        this.items = [];
        this.filtered = [];
        this.init();
    }

    init() {
        this.extractItems();
        this.attachEventListeners();
        this.applyFilters();
    }

    extractItems() {
        this.items = Array.from(document.querySelectorAll('.item-row')).map(row => {
            return AdminUtilities.extractDataAttributes(row);
        });
        this.filtered = [...this.items];
    }

    attachEventListeners() {
        this.config.filters?.forEach(filter => {
            const element = document.getElementById(filter.id);
            if (element) {
                const event = filter.type === 'search' ? 'input' : 'change';
                const handler = filter.type === 'search' ? 
                    AdminUtilities.debounce(this.applyFilters.bind(this), 300) : 
                    this.applyFilters.bind(this);
                element.addEventListener(event, handler);
            }
        });
    }

    applyFilters() {
        const filters = this.getFilterValues();
        
        this.filtered = this.items.filter(item => {
            for (const [key, value] of Object.entries(filters)) {
                if (!value) continue;
                
                if (key === 'search') {
                    const searchFields = this.config.searchFields || ['name'];
                    const found = searchFields.some(field => 
                        item[field]?.toLowerCase().includes(value)
                    );
                    if (!found) return false;
                } else {
                    if (item[key] !== value) return false;
                }
            }
            return true;
        });

        this.updateDisplay();
        this.updateStats();
        this.updateSearchHighlights(filters.search || '');
    }

    getFilterValues() {
        const filters = {};
        this.config.filters?.forEach(filter => {
            const element = document.getElementById(filter.id);
            if (element) {
                const key = filter.key || filter.id.replace('Filter', '').toLowerCase();
                filters[key] = filter.type === 'search' ? element.value.toLowerCase() : element.value;
            }
        });
        return filters;
    }

    updateDisplay() {
        this.items.forEach(item => {
            item.element.classList.toggle('filtered-out', !this.filtered.includes(item));
        });

        // Update results count
        const resultsCount = document.getElementById('resultsCount');
        if (resultsCount) {
            resultsCount.textContent = `${this.filtered.length} results`;
        }
        
        // Show/hide no results message
        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.classList.toggle('d-none', this.filtered.length > 0);
        }
    }

    updateStats() {
        if (!this.config.dynamicStats) return;
        
        this.config.stats?.forEach(stat => {
            const element = document.getElementById(stat.id);
            if (element && stat.calculator) {
                const value = stat.calculator(this.filtered);
                element.textContent = typeof value === 'number' ? value.toLocaleString() : value;
            }
        });
    }

    updateSearchHighlights(searchTerm) {
        const selectors = this.config.highlightSelectors || ['.searchable'];
        document.querySelectorAll(selectors.join(', ')).forEach(element => {
            AdminUtilities.highlightText(element, searchTerm);
        });
    }

    clearFilters() {
        this.config.filters?.forEach(filter => {
            const element = document.getElementById(filter.id);
            if (element) element.value = '';
        });
        this.applyFilters();
    }
}

// Main application class
class AdminApp {
    constructor(config = {}) {
        this.config = config;
        this.ajax = new AdminAjax();
        this.filtering = null;
        
        this.init();
    }

    init() {
        if (this.config.filters) {
            this.filtering = new AdminFiltering(this.config);
        }

        // Make utilities globally available
        window.adminAjax = this.ajax;
        window.AdminModals = AdminModals;
        window.AdminForms = AdminForms;
        window.AdminToast = AdminToast;
        
        // Backward compatibility functions
        window.applyFilters = () => this.filtering?.applyFilters();
        window.clearFilters = () => this.filtering?.clearFilters();
        window.showToast = AdminToast.show;
    }
}

// Auto-initialize when DOM loads
document.addEventListener('DOMContentLoaded', function() {
    if (window.adminConfig) {
        window.adminApp = new AdminApp(window.adminConfig);
    }
});

// Add required CSS for filtering
if (!document.getElementById('admin-filter-styles')) {
    const style = document.createElement('style');
    style.id = 'admin-filter-styles';
    style.textContent = `
        .filtered-out { 
            display: none !important; 
        }
        .search-highlight { 
            background: yellow; 
            font-weight: bold; 
        }
    `;
    document.head.appendChild(style);
}