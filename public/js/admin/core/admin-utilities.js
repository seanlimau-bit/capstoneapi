// File Structure for Reusable JavaScript
/*
public/js/
├── admin/
│   ├── core/
│   │   ├── admin-base.js          // Core classes (AdminInterface, AdminAjax, etc)
│   │   ├── admin-components.js    // Reusable component behaviors
│   │   └── admin-utilities.js     // Utility functions
│   ├── modules/
│   │   ├── filtering.js           // Filtering logic
│   │   ├── modals.js             // Modal management
│   │   ├── forms.js              // Form handling
│   │   └── tables.js             // Table interactions
│   └── admin.js                  // Main entry point that loads everything
*/

// ============================================================================
// public/js/admin/core/admin-utilities.js - HIGHLY REUSABLE
// ============================================================================
class AdminUtilities {
    // Reusable across ALL admin pages
    static debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    static formatNumber(num) {
        return typeof num === 'number' ? num.toLocaleString() : num;
    }

    static truncateText(text, maxLength = 100) {
        return text && text.length > maxLength ? text.slice(0, maxLength) + '...' : text;
    }

    static highlightText(element, searchTerm) {
        if (!searchTerm) {
            element.innerHTML = element.textContent;
            return;
        }
        
        const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        element.innerHTML = element.textContent.replace(regex, '<span class="search-highlight">$1</span>');
    }

    static extractDataAttributes(element) {
        const data = {};
        Object.keys(element.dataset).forEach(key => {
            data[key] = element.dataset[key];
        });
        return data;
    }

    static updateElementText(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = this.formatNumber(value);
        }
    }
}

// ============================================================================
// Usage in your blade templates - SAME ACROSS ALL PAGES
// ============================================================================
/*
@push('scripts')
<script src="{{ asset('js/admin/admin.js') }}"></script>
<script>
// Just configure, don't rewrite logic
window.adminConfig = {
    filters: [...],
    stats: [...],
    searchFields: [...],
    // Page-specific overrides if needed
};

// Page-specific functions use the standard patterns
function deleteItem(id) {
    if (!AdminModals.confirm('Delete this item?')) return;
    
    adminAjax.delete(`/admin/items/${id}`)
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-id="${id}"]`).remove();
                window.adminApp.filtering.extractItems(); // Refresh
                applyFilters();
                showToast('Deleted successfully', 'success');
            }
        });
}
</script>
@endpush
*/