@extends('layouts.admin')

@section('title', 'Configuration Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="fas fa-cogs"></i> Configuration Management
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Configuration</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="difficulties-tab" data-bs-toggle="tab" data-bs-target="#difficulties" type="button" role="tab">
                <i class="fas fa-signal"></i> Difficulties
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button" role="tab">
                <i class="fas fa-shapes"></i> Question Types
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="levels-tab" data-bs-toggle="tab" data-bs-target="#levels" type="button" role="tab">
                <i class="fas fa-layer-group"></i> Levels
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="statuses-tab" data-bs-toggle="tab" data-bs-target="#statuses" type="button" role="tab">
                <i class="fas fa-toggle-on"></i> Statuses
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button" role="tab">
                <i class="fas fa-user-shield"></i> Roles
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" type="button" role="tab">
                <i class="fas fa-ruler"></i> Units
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="configTabContent">
        
        <!-- Difficulties Tab -->
        <div class="tab-pane fade show active" id="difficulties" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Difficulty Levels</h5>
                    <button class="btn btn-primary btn-sm" id="addDifficultyBtn">
                        <i class="fas fa-plus"></i> Add Difficulty
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="difficultiesTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Difficulty</th>
                                    <th>Short Description</th>
                                    <th>Description</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($difficulties as $difficulty)
                                <tr data-id="{{ $difficulty->id }}" class="data-row">
                                    <td>{{ $difficulty->id }}</td>
                                    <td>
                                        <span class="view-mode">{{ $difficulty->difficulty ?? '' }}</span>
                                        <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="difficulty" value="{{ $difficulty->difficulty ?? '' }}" min="0">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $difficulty->short_description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="short_description" value="{{ $difficulty->short_description ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $difficulty->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $difficulty->description ?? '' }}">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Question Types Tab -->
        <div class="tab-pane fade" id="types" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Question Types</h5>
                    <button class="btn btn-primary btn-sm" id="addTypeBtn">
                        <i class="fas fa-plus"></i> Add Type
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="typesTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($types as $type)
                                <tr data-id="{{ $type->id }}" class="data-row">
                                    <td>{{ $type->id }}</td>
                                    <td>
                                        <span class="view-mode">{{ $type->type ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="type" value="{{ $type->type ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $type->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $type->description ?? '' }}">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Levels Tab -->
        <div class="tab-pane fade" id="levels" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Educational Levels</h5>
                    <button class="btn btn-primary btn-sm" id="addLevelBtn">
                        <i class="fas fa-plus"></i> Add Level
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="levelsTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Level</th>
                                    <th>Description</th>
                                    <th>Age</th>
                                    <th>Maxile Range</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($levels as $level)
                                <tr data-id="{{ $level->id }}" class="data-row">
                                    <td>{{ $level->id }}</td>
                                    <td>
                                        <span class="view-mode">{{ $level->level ?? '' }}</span>
                                        <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="level" value="{{ $level->level ?? '' }}" min="0">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $level->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $level->description ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $level->age ?? '' }}</span>
                                        <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="age" value="{{ $level->age ?? '' }}" min="0">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $level->start_maxile_level ?? 0 }} - {{ $level->end_maxile_level ?? 0 }}</span>
                                        <div class="edit-mode d-none d-flex gap-1">
                                            <input type="number" class="form-control form-control-sm inline-edit" 
                                                   data-field="start_maxile_level" value="{{ $level->start_maxile_level ?? 0 }}" style="width: 80px;">
                                            <span>-</span>
                                            <input type="number" class="form-control form-control-sm inline-edit" 
                                                   data-field="end_maxile_level" value="{{ $level->end_maxile_level ?? 0 }}" style="width: 80px;">
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statuses Tab -->
        <div class="tab-pane fade" id="statuses" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Status Types</h5>
                    <button class="btn btn-primary btn-sm" id="addStatusBtn">
                        <i class="fas fa-plus"></i> Add Status
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="statusesTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statuses as $status)
                                <tr data-id="{{ $status->id }}" class="data-row">
                                    <td>{{ $status->id }}</td>
                                    <td>
                                        <span class="view-mode">
                                            <span class="badge bg-secondary">
                                                {{ $status->status ?? '' }}
                                            </span>
                                        </span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="status" value="{{ $status->status ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $status->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $status->description ?? '' }}">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Tab -->
        <div class="tab-pane fade" id="roles" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Roles</h5>
                    <button class="btn btn-primary btn-sm" id="addRoleBtn">
                        <i class="fas fa-plus"></i> Add Role
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="rolesTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                    <th>Users Count</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr data-id="{{ $role->id }}" class="data-row">
                                    <td>{{ $role->id }}</td>
                                    <td>
                                        <span class="view-mode">
                                            <span class="badge bg-primary">{{ $role->role ?? '' }}</span>
                                        </span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="role" value="{{ $role->role ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $role->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $role->description ?? '' }}">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editPermissions({{ $role->id }})">
                                            <i class="fas fa-shield-alt"></i> Manage
                                        </button>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $role->users_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @if(($role->role ?? '') !== 'Super Admin')
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Units Tab -->
        <div class="tab-pane fade" id="units" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Measurement Units</h5>
                    <button class="btn btn-primary btn-sm" id="addUnitBtn">
                        <i class="fas fa-plus"></i> Add Unit
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="unitsTable">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Unit</th>
                                    <th>Description</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($units as $unit)
                                <tr data-id="{{ $unit->id }}" class="data-row">
                                    <td>{{ $unit->id }}</td>
                                    <td>
                                        <span class="view-mode">{{ $unit->unit ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="unit" value="{{ $unit->unit ?? '' }}">
                                    </td>
                                    <td>
                                        <span class="view-mode">{{ $unit->description ?? '' }}</span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" 
                                               data-field="description" value="{{ $unit->description ?? '' }}">
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info edit-row-btn view-mode" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-row-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Remember active tab
    const activeTab = localStorage.getItem('activeConfigTab');
    if (activeTab) {
        const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }
    
    // Save active tab to localStorage
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            localStorage.setItem('activeConfigTab', event.target.getAttribute('data-bs-target'));
        });
    });

    // Generic inline editing functionality
    let originalRowData = {};

    // Edit button click
    $(document).on('click', '.edit-row-btn', function() {
        const row = $(this).closest('tr');
        const rowId = row.data('id');
        
        // Store original values
        originalRowData[rowId] = {};
        row.find('.inline-edit').each(function() {
            const field = $(this).data('field');
            originalRowData[rowId][field] = $(this).val();
        });
        
        // Toggle edit mode
        row.find('.view-mode').addClass('d-none');
        row.find('.edit-mode').removeClass('d-none');
    });

    // Cancel button click
    $(document).on('click', '.cancel-row-btn', function() {
        const row = $(this).closest('tr');
        const rowId = row.data('id');
        
        // Restore original values
        if (originalRowData[rowId]) {
            row.find('.inline-edit').each(function() {
                const field = $(this).data('field');
                $(this).val(originalRowData[rowId][field]);
            });
        }
        
        // Toggle view mode
        row.find('.view-mode').removeClass('d-none');
        row.find('.edit-mode').addClass('d-none');
    });

    // Save button click - Generic handler for all tables
    $(document).on('click', '.save-row-btn', function() {
        const row = $(this).closest('tr');
        const rowId = row.data('id');
        const table = row.closest('table').attr('id');
        const data = {};
        
        // Collect data from inline edit fields
        row.find('.inline-edit').each(function() {
            const field = $(this).data('field');
            data[field] = $(this).val();
        });
        
        // Determine endpoint based on table
        let endpoint = '';
        switch(table) {
            case 'difficultiesTable': endpoint = `/admin/difficulties/${rowId}`; break;
            case 'typesTable': endpoint = `/admin/types/${rowId}`; break;
            case 'levelsTable': endpoint = `/admin/levels/${rowId}`; break;
            case 'statusesTable': endpoint = `/admin/statuses/${rowId}`; break;
            case 'rolesTable': endpoint = `/admin/roles/${rowId}`; break;
            case 'unitsTable': endpoint = `/admin/units/${rowId}`; break;
        }
        
        $.ajax({
            url: endpoint,
            method: 'PUT',
            data: data,
            success: function(response) {
                // Update view mode with new values
                row.find('.inline-edit').each(function() {
                    const field = $(this).data('field');
                    const newValue = $(this).val();
                    const viewSpan = $(this).closest('td').find('.view-mode');
                    
                    if (viewSpan.find('.badge').length > 0) {
                        viewSpan.find('.badge').text(newValue);
                    } else {
                        viewSpan.text(newValue);
                    }
                });
                
                // Toggle view mode
                row.find('.view-mode').removeClass('d-none');
                row.find('.edit-mode').addClass('d-none');
                
                showToast('Updated successfully', 'success');
            },
            error: function(xhr) {
                showToast('Error updating record', 'danger');
            }
        });
    });

    // Delete button click - Generic handler
    $(document).on('click', '.delete-row-btn', function() {
        if (!confirmDelete('Are you sure you want to delete this item?')) return;
        
        const row = $(this).closest('tr');
        const rowId = row.data('id');
        const table = row.closest('table').attr('id');
        
        // Determine endpoint based on table
        let endpoint = '';
        switch(table) {
            case 'difficultiesTable': endpoint = `/admin/difficulties/${rowId}`; break;
            case 'typesTable': endpoint = `/admin/types/${rowId}`; break;
            case 'levelsTable': endpoint = `/admin/levels/${rowId}`; break;
            case 'statusesTable': endpoint = `/admin/statuses/${rowId}`; break;
            case 'rolesTable': endpoint = `/admin/roles/${rowId}`; break;
            case 'unitsTable': endpoint = `/admin/units/${rowId}`; break;
        }
        
        $.ajax({
            url: endpoint,
            method: 'DELETE',
            success: function() {
                row.fadeOut(300, function() {
                    $(this).remove();
                });
                showToast('Deleted successfully', 'success');
            },
            error: function(xhr) {
                showToast('Error deleting record', 'danger');
            }
        });
    });

    // Placeholder for permissions management
    window.editPermissions = function(roleId) {
        alert('Permissions management for role ' + roleId + ' would open here');
        // Implement your permissions modal here
    };
});
</script>
@endpush

@push('styles')
<style>
    .nav-tabs .nav-link {
        color: var(--black-color);
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background: transparent;
        border-bottom-color: var(--primary-color);
    }
    
    .table-responsive {
        min-height: 400px;
    }
    
    .badge {
        padding: 4px 8px;
        font-weight: 500;
    }
    
    .gap-1 {
        gap: 0.25rem;
    }
</style>
@endpush