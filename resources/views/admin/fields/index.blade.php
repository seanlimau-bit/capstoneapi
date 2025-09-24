@extends('layouts.admin')

@section('title', 'Fields Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header - Using standard component --}}
    @include('admin.components.page-header', [
        'title' => 'Fields Management',
        'subtitle' => 'Manage all fields in the math learning system',
        'breadcrumbs' => [
            ['title' => 'Dashboard', 'url' => url('/admin')],
            ['title' => 'Fields']
        ],
        'actions' => [
            [
                'text' => 'Create New Field',
                'url' => route('admin.fields.create'),
                'icon' => 'plus',
                'class' => 'primary'
            ],
            [
                'type' => 'dropdown',
                'class' => 'secondary',
                'icon' => 'ellipsis-v',
                'text' => 'Actions',
                'items' => [
                    ['icon' => 'download', 'text' => 'Export Fields', 'onclick' => 'exportFields()'],
                    ['icon' => 'upload', 'text' => 'Import Fields', 'onclick' => 'importFields()'],
                    ['icon' => 'copy', 'text' => 'Bulk Duplicate Selected', 'onclick' => 'bulkDuplicate()', 'id' => 'dropdownBulkDuplicate'],
                    ['icon' => 'trash', 'text' => 'Bulk Delete Selected', 'onclick' => 'bulkDelete()', 'id' => 'dropdownBulkDelete'],
                    ['icon' => 'sync', 'text' => 'Refresh', 'onclick' => 'refreshData()']
                ]
            ]
        ]
    ])

    {{-- Statistics Row - Using standard component --}}
    @include('admin.components.stats-row', [
        'stats' => [
            [
                'value' => 'Loading...',
                'label' => 'Total Fields',
                'color' => 'primary',
                'icon' => 'tags',
                'id' => 'totalFieldsCount'
            ],
            [
                'value' => '0',
                'label' => 'Public',
                'color' => 'success',
                'icon' => 'globe',
                'id' => 'publicCount'
            ],
            [
                'value' => '0',
                'label' => 'Draft',
                'color' => 'warning',
                'icon' => 'edit',
                'id' => 'draftCount'
            ],
            [
                'value' => '0',
                'label' => 'Private',
                'color' => 'info',
                'icon' => 'lock',
                'id' => 'privateCount'
            ]
        ]
    ])

    {{-- Filters - Essential lookup table filters --}}
    @component('admin.components.filters-card', ['items' => []])
    <div class="col-md-3">
        <select class="form-select" id="statusFilter" data-populate="statuses">
            <option value="">All Status</option>
        </select>
    </div>
    <div class="col-md-6">
        <input type="search" class="form-control" id="searchInput" placeholder="Search fields...">
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
            <i class="fas fa-times me-1"></i>Clear Filters
        </button>
    </div>
    @endcomponent

    {{-- Fields Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tags me-2"></i>Fields List
                        </h5>

                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleSelectAll()">
                                <label class="form-check-label" for="selectAll">
                                    <small>Select All</small>
                                </label>
                            </div>

                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="bulkDuplicate()" disabled id="bulkDuplicateBtn">
                                    <i class="fas fa-copy me-1"></i>Duplicate Selected
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="bulkDelete()" disabled id="bulkDeleteBtn">
                                    <i class="fas fa-trash me-1"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="sortTable('field')">
                                        <div class="d-flex align-items-center">
                                            Field Name
                                            <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-field"></i>
                                        </div>
                                    </th>
                                    <th>Description</th>
                                    <th style="cursor: pointer;" onclick="sortTable('status_id')">
                                        <div class="d-flex align-items-center">
                                            Status
                                            <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-status_id"></i>
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="sortTable('tracks_count')">
                                        <div class="d-flex align-items-center">
                                            Tracks
                                            <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-tracks_count"></i>
                                        </div>
                                    </th>
                                    <th style="cursor: pointer;" onclick="sortTable('created_at')">
                                        <div class="d-flex align-items-center">
                                            Created
                                            <i class="fas fa-sort ms-1 text-muted sort-icon" id="sort-created_at"></i>
                                        </div>
                                    </th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="fieldsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">Loading fields...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination Footer --}}
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing <span id="showing-start">0</span> to <span id="showing-end">0</span> 
                            of <span id="total-records">0</span> entries
                        </div>

                        <nav aria-label="Fields pagination">
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                {{-- Pagination will be populated by JavaScript --}}
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let fieldsData = [];
let currentPage = 1;
let totalPages = 1;
let totalFields = 0;
let currentSortField = '';
let currentSortDirection = 'asc';

// Sort functionality
function sortTable(field) {
    if (!field) return;
    
    // Toggle direction if same field, otherwise start with asc
    if (currentSortField === field) {
        currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortDirection = 'asc';
    }
    
    currentSortField = field;
    
    // Update sort icons
    document.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'fas fa-sort ms-1 text-muted sort-icon';
    });
    
    const currentIcon = document.getElementById(`sort-${field}`);
    if (currentIcon) {
        currentIcon.className = `fas fa-sort-${currentSortDirection === 'asc' ? 'up' : 'down'} ms-1 text-primary sort-icon`;
    }
    
    // Reload fields with sort
    loadFields(1);
}

document.addEventListener('DOMContentLoaded', function() {
    loadFields();
    setupFieldsFilters();
    
    // Populate global dropdowns after a small delay to ensure DOM is ready
    setTimeout(function() {
        if (typeof populateGlobalDropdowns === 'function') {
            populateGlobalDropdowns();
        }
    }, 100);
});

function loadFields(page = 1) {
    const tbody = document.getElementById('fieldsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading fields...</div></td></tr>';
    
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    
    // Add filter parameters
    const filters = getActiveFilters();
    Object.entries(filters).forEach(([key, value]) => {
        if (value) url.searchParams.set(key, value);
    });
    
    // Add sort parameters if sorting is active
    if (currentSortField) {
        url.searchParams.set('sort', currentSortField);
        url.searchParams.set('direction', currentSortDirection);
    }
    
    fetch(url.toString(), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        fieldsData = data.fields;
        currentPage = page;
        totalPages = data.num_pages || 1;
        totalFields = data.totals ? data.totals.total : data.fields.length;
        
        renderFieldsTable(fieldsData);
        updatePagination();
        updateStatistics(data.totals);
    })
    .catch(error => {
        console.error('Error loading fields:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading fields. Please refresh the page.</td></tr>';
    });
}

function getActiveFilters() {
    return {
        status_id: document.getElementById('statusFilter')?.value || '',
        search: document.getElementById('searchInput')?.value || ''
    };
}

function setupFieldsFilters() {
    // Add filter event listeners
    ['statusFilter', 'searchInput'].forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', () => loadFields(1));
            if (filterId === 'searchInput') {
                let searchTimeout;
                element.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => loadFields(1), 500);
                });
            }
        }
    });
}

function renderFieldsTable(fields) {
    const tbody = document.getElementById('fieldsTableBody');
    
    if (!fields || fields.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-search me-2"></i>No fields found</td></tr>';
        return;
    }
    
    tbody.innerHTML = fields.map(field => `
        <tr class="field-row" data-field-id="${field.id}">
        <td>
        <div class="form-check">
        <input type="checkbox" value="${field.id}" class="form-check-input field-checkbox">
        </div>
        </td>
        <td>
        <div class="field-details">
        <div class="fw-semibold mb-1">${field.field}</div>
        <small class="text-muted">ID: ${field.id}</small>
        </div>
        </td>
        <td>
        <div class="field-description">
        ${field.description ? truncateText(field.description, 80) : '<span class="text-muted">No description</span>'}
        </div>
        </td>
        <td>
        ${renderFieldStatus(field.status)}
        </td>
        <td>
        <span class="badge bg-info">${field.tracks_count || 0}</span>
        </td>
        <td>
        ${formatDate(field.created_at)}
        </td>
        <td>
        <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-info" onclick="viewField(${field.id})" title="View">
        <i class="fas fa-eye"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="copyField(${field.id})" title="Duplicate">
        <i class="fas fa-copy"></i>
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="deleteField(${field.id})" title="Delete">
        <i class="fas fa-trash"></i>
        </button>
        </div>
        </td>
        </tr>
        `).join('');
}

function renderFieldStatus(status) {
    if (!status) return '<span class="badge bg-secondary">Unknown</span>';
    
    const statusConfig = {
        'Public': { class: 'success', icon: 'globe' },
        'Draft': { class: 'warning', icon: 'edit' },
        'Only Me': { class: 'info', icon: 'lock' },
        'Restricted': { class: 'secondary', icon: 'ban' }
    };
    
    const config = statusConfig[status.status] || { class: 'secondary', icon: 'question' };
    
    return `<span class="badge bg-${config.class}"><i class="fas fa-${config.icon} me-1"></i>${status.status}</span>`;
}

function updatePagination() {
    const paginationContainer = document.getElementById('pagination');
    if (!paginationContainer) return;
    
    let paginationHtml = '';
    
    if (currentPage > 1) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadFields(${currentPage - 1}); return false;">
        <i class="fas fa-chevron-left"></i> Previous
        </a>
        </li>
        `;
    } else {
        paginationHtml += `
        <li class="page-item disabled">
        <span class="page-link">
        <i class="fas fa-chevron-left"></i> Previous
        </span>
        </li>
        `;
    }
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadFields(1); return false;">1</a>
        </li>
        `;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
        <a class="page-link" href="#" onclick="loadFields(${i}); return false;">${i}</a>
        </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadFields(${totalPages}); return false;">${totalPages}</a>
        </li>
        `;
    }
    
    if (currentPage < totalPages) {
        paginationHtml += `
        <li class="page-item">
        <a class="page-link" href="#" onclick="loadFields(${currentPage + 1}); return false;">
        Next <i class="fas fa-chevron-right"></i>
        </a>
        </li>
        `;
    } else {
        paginationHtml += `
        <li class="page-item disabled">
        <span class="page-link">
        Next <i class="fas fa-chevron-right"></i>
        </span>
        </li>
        `;
    }
    
    paginationContainer.innerHTML = paginationHtml;
    
    const start = (currentPage - 1) * 50 + 1;
    const end = Math.min(currentPage * 50, start + fieldsData.length - 1);
    
    document.getElementById('showing-start').textContent = start;
    document.getElementById('showing-end').textContent = end;
    document.getElementById('total-records').textContent = totalFields;
}

function updateStatistics(totals) {
    if (totals) {
        document.getElementById('totalFieldsCount').textContent = totals.total || 0;
        document.getElementById('publicCount').textContent = totals.public || 0;
        document.getElementById('draftCount').textContent = totals.draft || 0;
        document.getElementById('privateCount').textContent = totals.private || 0;
    } else {
        document.getElementById('totalFieldsCount').textContent = totalFields;
        // Calculate from current data if no totals provided
        const public = fieldsData.filter(f => f.status?.status === 'Public').length;
        const draft = fieldsData.filter(f => f.status?.status === 'Draft').length;
        const private = fieldsData.filter(f => f.status?.status === 'Only Me').length;
        
        document.getElementById('publicCount').textContent = public;
        document.getElementById('draftCount').textContent = draft;
        document.getElementById('privateCount').textContent = private;
    }
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('searchInput').value = '';
    loadFields(1);
}

function truncateText(text, length) {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    return new Date(dateString).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.field-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActionButtons();
}

function updateBulkActionButtons() {
    const selectedCheckboxes = document.querySelectorAll('.field-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkDuplicateBtn = document.getElementById('bulkDuplicateBtn');
    
    const hasSelected = selectedCheckboxes.length > 0;
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.disabled = !hasSelected;
    }
    
    if (bulkDuplicateBtn) {
        bulkDuplicateBtn.disabled = !hasSelected;
    }
}

function bulkDuplicate() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showToast('Please select fields to duplicate', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to duplicate ${selectedIds.length} selected fields?`)) {
        const bulkDuplicateBtn = document.getElementById('bulkDuplicateBtn');
        const originalText = bulkDuplicateBtn.innerHTML;
        bulkDuplicateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Duplicating...';
        bulkDuplicateBtn.disabled = true;
        
        fetch('/admin/fields/bulk-duplicate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                field_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`${selectedIds.length} fields duplicated successfully!`, 'success');
                document.getElementById('selectAll').checked = false;
                document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = false);
                loadFields(currentPage);
            } else {
                showToast(data.message || 'Error duplicating fields', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error duplicating fields', 'error');
        })
        .finally(() => {
            bulkDuplicateBtn.innerHTML = originalText;
            updateBulkActionButtons();
        });
    }
}

function bulkDelete() {
    const selectedIds = getSelectedIds();
    
    if (selectedIds.length === 0) {
        showToast('Please select fields to delete', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected fields? This action cannot be undone.`)) {
        fetch('/admin/fields/bulk-delete', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                field_ids: selectedIds
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Selected fields deleted successfully!', 'success');
                document.getElementById('selectAll').checked = false;
                document.querySelectorAll('.field-checkbox').forEach(cb => cb.checked = false);
                loadFields(currentPage);
            } else {
                showToast(data.message || 'Error deleting fields', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showToast('Error deleting fields', 'error');
        });
    }
}

function getSelectedIds() {
    const selectedCheckboxes = document.querySelectorAll('.field-checkbox:checked');
    return Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
}

function viewField(fieldId) {
    window.location.href = `/admin/fields/${fieldId}`;
}

function editField(fieldId) {
    window.location.href = `/admin/fields/${fieldId}/edit`;
}

function copyField(fieldId) {
    if (confirm('Are you sure you want to duplicate this field?')) {
        fetch(`/admin/fields/${fieldId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Field duplicated successfully!', 'success');
                loadFields(currentPage); // ← Already refreshes
            } else {
                showToast(data.message || 'Error duplicating field', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error duplicating field', 'error');
        });
    }
}

function deleteField(fieldId) {
    if (confirm('Are you sure you want to delete this field? This action cannot be undone.')) {
        fetch(`/admin/fields/${fieldId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                showToast('Field deleted successfully!', 'success');
                loadFields(currentPage); // Refresh the table
            } else {
                // Handle error response
                return response.json().then(data => {
                    showToast(data.message || 'Error deleting field', 'error');
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting field', 'error');
        });
    }
}
function exportFields() {
    window.location.href = '/admin/fields/export';
}

function importFields() {
    showToast('Import functionality coming soon', 'info');
}

function refreshData() {
    loadFields(currentPage);
}

function showToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(`${type.toUpperCase()}: ${message}`);
    }
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('field-checkbox')) {
        updateBulkActionButtons();
    }
});
</script>
@endpush