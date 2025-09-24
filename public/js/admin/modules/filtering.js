
// ============================================================================
// public/js/admin/modules/filtering.js - REUSABLE FILTERING LOGIC
// ============================================================================
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

    // Extract data from ANY table structure
    extractItems() {
        this.items = Array.from(document.querySelectorAll('.item-row')).map(row => {
            return {
                element: row,
                id: row.dataset.id,
                ...AdminUtilities.extractDataAttributes(row)
            };
        });
        this.filtered = [...this.items];
    }

    // Attach listeners based on config - works with ANY filter setup
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

    // Generic filtering that works across all admin pages
    applyFilters() {
        const filters = this.getFilterValues();
        
        this.filtered = this.items.filter(item => {
            return this.config.filterLogic ? 
                this.config.filterLogic(item, filters) : 
                this.defaultFilterLogic(item, filters);
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

    // Default logic that works for most cases
    defaultFilterLogic(item, filters) {
        for (const [key, value] of Object.entries(filters)) {
            if (!value) continue;
            
            if (key.includes('search')) {
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
    }

    updateDisplay() {
        this.items.forEach(item => {
            item.element.classList.toggle('filtered-out', !this.filtered.includes(item));
        });

        AdminUtilities.updateElementText('resultsCount', `${this.filtered.length} results`);
        
        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.classList.toggle('d-none', this.filtered.length > 0);
        }
    }

    updateStats() {
        if (!this.config.dynamicStats) return;
        
        this.config.stats?.forEach(stat => {
            if (stat.calculator) {
                const value = stat.calculator(this.filtered);
                AdminUtilities.updateElementText(stat.id, value);
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

// ============================================================================
// public/js/admin/modules/modals.js - REUSABLE MODAL MANAGEMENT
// ============================================================================
class AdminModals {
    // Show any modal with data population
    static show(modalId, data = {}) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return null;

        // Auto-populate form fields
        Object.entries(data).forEach(([key, value]) => {
            const selectors = [
                `[name="${key}"]`,
                `#${key}`,
                `.${key}-display`
            ];
            
            selectors.forEach(selector => {
                const element = modalElement.querySelector(selector);
                if (element) {
                    if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.tagName === 'SELECT') {
                        element.value = value;
                    } else {
                        element.textContent = value;
                    }
                }
            });
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

    static confirm(message = 'Are you sure?', title = 'Confirm Action') {
        return confirm(message); // Can be enhanced with custom modal
    }
}
