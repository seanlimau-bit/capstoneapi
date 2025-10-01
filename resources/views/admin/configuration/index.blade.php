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
            <button class="nav-link active" id="difficulties-tab" data-bs-toggle="tab" data-bs-target="#difficulties"
            type="button" role="tab">
            <i class="fas fa-signal"></i> Difficulties
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="types-tab" data-bs-toggle="tab" data-bs-target="#types" type="button"
        role="tab">
        <i class="fas fa-shapes"></i> Question Types
    </button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link" id="levels-tab" data-bs-toggle="tab" data-bs-target="#levels" type="button"
    role="tab">
    <i class="fas fa-layer-group"></i> Levels
</button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link" id="statuses-tab" data-bs-toggle="tab" data-bs-target="#statuses" type="button"
    role="tab">
    <i class="fas fa-toggle-on"></i> Statuses
</button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles" type="button"
    role="tab">
    <i class="fas fa-user-shield"></i> Roles
</button>
</li>
<li class="nav-item" role="presentation">
    <button class="nav-link" id="units-tab" data-bs-toggle="tab" data-bs-target="#units" type="button"
    role="tab">
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
                <button class="btn btn-primary btn-sm add-btn" data-table="difficulties" data-bs-toggle="modal"
                data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Difficulty
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="difficultiesTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-col="0" width="60">ID</th>
                            <th class="sortable" data-col="1">Difficulty</th>
                            <th class="sortable" data-col="2">Short Description</>
                                <th class="sortable" data-col="3">Description</th>
                                <th class="sortable" data-col="4">Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($difficulties as $difficulty)
                            <tr data-id="{{ $difficulty->id }}" data-status-id="{{ $difficulty->status_id ?? '' }}"
                                class="data-row">
                                <td class="click-edit">
                                    <span class="view-mode">{{ $difficulty->id }}</span>
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $difficulty->difficulty ?? '' }}</span>
                                    <input type="number"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="difficulty" value="{{ $difficulty->difficulty ?? '' }}" min="0">
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $difficulty->short_description ?? '' }}</span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="short_description"
                                    value="{{ $difficulty->short_description ?? '' }}">
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $difficulty->description ?? '' }}</span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="description" value="{{ $difficulty->description ?? '' }}">
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">
                                        <span class="badge bg-secondary">{{ $difficulty->status->status ?? '—' }}</span>
                                    </span>
                                    <select class="form-control form-control-sm edit-mode d-none inline-edit" data-field="status_id">
                                        @foreach($statusOptions as $option)
                                        <option value="{{ $option['id'] }}" {{ $difficulty->status_id == $option['id'] ? 'selected' : '' }}>
                                            {{ $option['text'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                                    title="Save">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                                title="Cancel">
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
            <button class="btn btn-primary btn-sm add-btn" data-table="types" data-bs-toggle="modal"
            data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add Type
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="typesTable">
                <thead>
                    <tr>
                        <th class="sortable" data-col="0" width="60">ID</th>
                        <th class="sortable" data-col="1">Type</th>
                        <th class="sortable" data-col="2">Description</th>
                        <th class="sortable" data-col="3">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $type)
                    <tr data-id="{{ $type->id }}" data-status-id="{{ $type->status_id ?? '' }}"
                        class="data-row">
                        <td class="click-edit"><span class="view-mode">{{ $type->id }}</span></td>
                        <td class="click-edit">
                            <span class="view-mode">{{ $type->type ?? '' }}</span>
                            <input type="text"
                            class="form-control form-control-sm edit-mode d-none inline-edit"
                            data-field="type" value="{{ $type->type ?? '' }}">
                        </td>
                        <td class="click-edit">
                            <span class="view-mode">{{ $type->description ?? '' }}</span>
                            <input type="text"
                            class="form-control form-control-sm edit-mode d-none inline-edit"
                            data-field="description" value="{{ $type->description ?? '' }}">
                        </td>
                        <td class="click-edit">
                            <span class="view-mode">
                                <span class="badge bg-secondary">{{ $type->status->status ?? '—' }}</span>
                            </span>
                            <select class="form-control form-control-sm edit-mode d-none inline-edit" data-field="status_id">
                                @foreach($statusOptions as $option)
                                <option value="{{ $option['id'] }}" {{ $type->status_id == $option['id'] ? 'selected' : '' }}>
                                    {{ $option['text'] }}
                                </option>
                                @endforeach
                            </select>
                        </td>                        
                        <td>
                            <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                            title="Save"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                            title="Cancel"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                                class="fas fa-trash"></i></button>
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
            <button class="btn btn-primary btn-sm add-btn" data-table="levels" data-bs-toggle="modal"
            data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add Level
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="levelsTable">
                <thead>
                    <tr>
                        <th class="sortable" data-col="0" width="60">ID</th>
                        <th class="sortable" data-col="1">Level</th>
                        <th class="sortable" data-col="2">Description</th>
                        <th class="sortable" data-col="3">Age</th>
                        <th class="sortable" data-col="4">Maxile Range</th>
                        <th class="sortable" data-col="5">Status</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($levels as $level)
                    <tr data-id="{{ $level->id }}" data-status-id="{{ $level->status_id ?? '' }}"
                        class="data-row">
                        <td class="click-edit"><span class="view-mode">{{ $level->id }}</span></td>
                        <td class="click-edit">
                            <span class="view-mode">{{ $level->level ?? '' }}</span>
                            <input type="number"
                            class="form-control form-control-sm edit-mode d-none inline-edit"
                            data-field="level" value="{{ $level->level ?? '' }}" min="0">
                        </td>
                        <td class="click-edit">
                            <span class="view-mode">{{ $level->description ?? '' }}</span>
                            <input type="text"
                            class="form-control form-control-sm edit-mode d-none inline-edit"
                            data-field="description" value="{{ $level->description ?? '' }}">
                        </td>
                        <td class="click-edit">
                            <span class="view-mode">{{ $level->age ?? '' }}</span>
                            <input type="number"
                            class="form-control form-control-sm edit-mode d-none inline-edit"
                            data-field="age" value="{{ $level->age ?? '' }}" min="0">
                        </td>
                        <td class="click-edit">
                            <span class="view-mode maxile-span">
                                {{ $level->start_maxile_level ?? 0 }} - {{ $level->end_maxile_level ?? 0 }}
                            </span>
                            <div class="edit-mode d-none d-flex gap-1">
                                <input type="number" class="form-control form-control-sm inline-edit"
                                data-field="start_maxile_level"
                                value="{{ $level->start_maxile_level ?? 0 }}" style="width: 90px;">
                                <span>-</span>
                                <input type="number" class="form-control form-control-sm inline-edit"
                                data-field="end_maxile_level"
                                value="{{ $level->end_maxile_level ?? 0 }}" style="width: 90px;">
                            </div>
                        </td>
                        <td class="click-edit">
                            <span class="view-mode">
                                <span class="badge bg-secondary">{{ $level->status->status ?? '—' }}</span>
                            </span>
                            <select class="form-control form-control-sm edit-mode d-none inline-edit" data-field="status_id">
                                @foreach($statusOptions as $option)
                                <option value="{{ $option['id'] }}" {{ $level->status_id == $option['id'] ? 'selected' : '' }}>
                                    {{ $option['text'] }}
                                </option>
                                @endforeach
                            </select>
                        </td>                        
                        <td>
                            <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                            title="Save"><i class="fas fa-check"></i></button>
                            <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                            title="Cancel"><i class="fas fa-times"></i></button>
                            <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                                class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Statuses Tab (no Status column here) -->
<div class="tab-pane fade" id="statuses" role="tabpanel">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Status Types</h5>
            <button class="btn btn-primary btn-sm add-btn" data-table="statuses" data-bs-toggle="modal"
            data-bs-target="#addModal">
            <i class="fas fa-plus"></i> Add Status
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="statusesTable">
                <thead>
                    <tr>
                        <th class="sortable" data-col="0" width="60">ID</th>
                        <th class="sortable" data-col="1">Status</th>
                        <th class="sortable" data-col="2">Description</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($statuses as $status)
                    <tr data-id="{{ $status->id }}" class="data-row">
                        <td class="click-edit"><span class="view-mode">{{ $status->id }}</span></td>
                        <td class="click-edit">
                            <span class="view-mode"><span
                                class="badge bg-secondary">{{ $status->status ?? '' }}</span></span>
                                <input type="text"
                                class="form-control form-control-sm edit-mode d-none inline-edit"
                                data-field="status" value="{{ $status->status ?? '' }}">
                            </td>
                            <td class="click-edit">
                                <span class="view-mode">{{ $status->description ?? '' }}</span>
                                <input type="text"
                                class="form-control form-control-sm edit-mode d-none inline-edit"
                                data-field="description" value="{{ $status->description ?? '' }}">
                            </td>
                            <td>
                                <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                                title="Save"><i class="fas fa-check"></i></button>
                                <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                                title="Cancel"><i class="fas fa-times"></i></button>
                                <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                                    class="fas fa-trash"></i></button>
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
                <button class="btn btn-primary btn-sm add-btn" data-table="roles" data-bs-toggle="modal"
                data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Role
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="rolesTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-col="0" width="60">ID</th>
                            <th class="sortable" data-col="1">Role Name</th>
                            <th class="sortable" data-col="2">Description</th>
                            <th class="sortable" data-col="4">Users Count</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                        <tr data-id="{{ $role->id }}" data-status-id="{{ $role->status_id ?? '' }}"
                            class="data-row">
                            <td class="click-edit"><span class="view-mode">{{ $role->id }}</span></td>
                            <td class="click-edit">
                                <span class="view-mode"><span
                                    class="badge bg-primary">{{ $role->role ?? '' }}</span></span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="role" value="{{ $role->role ?? '' }}">
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $role->description ?? '' }}</span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="description" value="{{ $role->description ?? '' }}">
                                </td>
                                <td><span class="badge bg-info">{{ $role->users_count ?? 0 }}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                                    title="Save"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                                    title="Cancel"><i class="fas fa-times"></i></button>
                                    @if(($role->role ?? '') !== 'Super Admin')
                                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                                        class="fas fa-trash"></i></button>
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
                    <button class="btn btn-primary btn-sm add-btn" data-table="units" data-bs-toggle="modal"
                    data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add Unit
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="unitsTable">
                        <thead>
                            <tr>
                                <th class="sortable" data-col="0" width="60">ID</th>
                                <th class="sortable" data-col="1">Unit</th>
                                <th class="sortable" data-col="2">Description</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($units as $unit)
                            <tr data-id="{{ $unit->id }}" data-status-id="{{ $unit->status_id ?? '' }}"
                                class="data-row">
                                <td class="click-edit"><span class="view-mode">{{ $unit->id }}</span></td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $unit->unit ?? '' }}</span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="unit" value="{{ $unit->unit ?? '' }}">
                                </td>
                                <td class="click-edit">
                                    <span class="view-mode">{{ $unit->description ?? '' }}</span>
                                    <input type="text"
                                    class="form-control form-control-sm edit-mode d-none inline-edit"
                                    data-field="description" value="{{ $unit->description ?? '' }}">
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none"
                                    title="Save"><i class="fas fa-check"></i></button>
                                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none"
                                    title="Cancel"><i class="fas fa-times"></i></button>
                                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                                        class="fas fa-trash"></i></button>
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

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addForm">
                <div class="modal-body">
                    <div id="modalFields"><!-- Dynamic fields --></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="permissionsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePermissions">Save Permissions</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const MAP = JSON.parse(localStorage.getItem('STATUS_MAP') || '{ }');
        window.STATUS_MAP = MAP;
        window.renderStatusBadge = id => `<span class="badge bg-secondary">${MAP[String(id)] ?? '—'}</span>`;
        window.renderStatusOptions = selId => Object.entries(MAP)
        .map(([id, name]) => `<option value="${id}" ${String(selId) === id ? 'selected' : ''}>${name}</option>`)
        .join('');
    })();

    $(document).ready(function () {
            // CSRF + AJAX headers
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            // Toast
            function showToast(message, type = 'success') {
                const toastEl = document.getElementById('liveToast');
                const toast = new bootstrap.Toast(toastEl);
                const toastBody = document.getElementById('toastMessage');
                toastBody.textContent = message;
                toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'text-white');
                if (type === 'success') toastEl.classList.add('bg-success', 'text-white');
                else if (type === 'danger') toastEl.classList.add('bg-danger', 'text-white');
                else if (type === 'warning') toastEl.classList.add('bg-warning');
                else if (type === 'info') toastEl.classList.add('bg-info', 'text-white');
                toast.show();
            }
            function confirmDelete(message = 'Are you sure you want to delete this item?') { return confirm(message); }

            // Remember active tab
            const activeTab = localStorage.getItem('activeConfigTab');
            if (activeTab) {
                const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
                if (tabTrigger) new bootstrap.Tab(tabTrigger).show();
            }
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', e => localStorage.setItem('activeConfigTab',
                    e.target.getAttribute('data-bs-target')));
            });

            // Table config (add status_id to lookups except statuses)
            const tableConfig = {
                difficulties: {
                    title: 'Add Difficulty',
                    endpoint: '/admin/difficulties',
                    fields: [
                    { name: 'difficulty', label: 'Difficulty Level', type: 'number', required: true },
                    { name: 'short_description', label: 'Short Description', type: 'text', required: true },
                    { name: 'description', label: 'Full Description', type: 'textarea', required: false },
                    ]
                },
                types: {
                    title: 'Add Question Type',
                    endpoint: '/admin/types',
                    fields: [
                    { name: 'type', label: 'Type Name', type: 'text', required: true },
                    { name: 'description', label: 'Description', type: 'textarea', required: false },
                    { name: 'status_id', label: 'Visibility', type: 'status', required: true }
                    ]
                },
                levels: {
                    title: 'Add Educational Level',
                    endpoint: '/admin/levels',
                    fields: [
                    { name: 'level', label: 'Level', type: 'number', required: true },
                    { name: 'description', label: 'Description', type: 'text', required: true },
                    { name: 'age', label: 'Age', type: 'number', required: false },
                    { name: 'start_maxile_level', label: 'Start Maxile Level', type: 'number', required: false },
                    { name: 'end_maxile_level', label: 'End Maxile Level', type: 'number', required: false },
                    { name: 'status_id', label: 'Visibility', type: 'status', required: true }
                    ]
                },
                statuses: {
                    title: 'Add Status',
                    endpoint: '/admin/statuses',
                    fields: [
                    { name: 'status', label: 'Status Name', type: 'text', required: true },
                    { name: 'description', label: 'Description', type: 'textarea', required: false }
                    ]
                },
                roles: {
                    title: 'Add Role',
                    endpoint: '/admin/roles',
                    fields: [
                    { name: 'role', label: 'Role Name', type: 'text', required: true },
                    { name: 'description', label: 'Description', type: 'textarea', required: false },
                    { name: 'status_id', label: 'Visibility', type: 'status', required: true }
                    ]
                },
                units: {
                    title: 'Add Unit',
                    endpoint: '/admin/units',
                    fields: [
                    { name: 'unit', label: 'Unit Name', type: 'text', required: true },
                    { name: 'description', label: 'Description', type: 'textarea', required: false },
                    ]
                }
            };

            let currentTable = '';
            let originalRowData = {};

            // Build modal fields
            $('.add-btn').on('click', function () {
                currentTable = $(this).data('table');
                const config = tableConfig[currentTable];
                $('#modalTitle').text(config.title);

                let fieldsHtml = '';
                config.fields.forEach(field => {
                    fieldsHtml += `<div class="mb-3">
                    <label for="${field.name}" class="form-label">${field.label} ${field.required ? '<span class="text-danger">*</span>'
                    : ''}</label>`;
                    if (field.type === 'textarea') {
                        fieldsHtml += `<textarea class="form-control" id="${field.name}" name="${field.name}" ${field.required ? 'required'
                        : ''} rows="3"></textarea>`;
                    } else if (field.type === 'status') {
                        fieldsHtml += `<select class="form-select" id="${field.name}" name="${field.name}" ${field.required ? 'required'
                        : ''}>
                        ${renderStatusOptions()}
                        </select>`;
                    } else {
                        fieldsHtml += `<input type="${field.type}" class="form-control" id="${field.name}" name="${field.name}"
                        ${field.required ? 'required' : ''}>`;
                    }
                    fieldsHtml += `
                    </div>`;
                });

                $('#modalFields').html(fieldsHtml);
                $('#addForm')[0].reset();
            });

            // Submit Add
            $('#addForm').on('submit', function (e) {
                e.preventDefault();
                const config = tableConfig[currentTable];
                const formData = $(this).serialize();
                $.ajax({
                    url: config.endpoint,
                    method: 'POST',
                    data: formData,
                    success: function (response) {
                        const payload = (response && (response.data || response.unit || response.item || response.model))
                        ? (response.data || response.unit || response.item || response.model)
                        : response;
                        if (!payload || typeof payload !== 'object') { showToast('Unexpected server response', 'danger'); return; }

                        const addModalEl = document.getElementById('addModal');
                        (bootstrap.Modal.getInstance(addModalEl) || new bootstrap.Modal(addModalEl)).hide();

                        addNewRowToTable(currentTable, payload);
                        showToast('Item added successfully', 'success');
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            let msg = 'Validation errors:\n';
                            Object.keys(errors).forEach(k => { msg += errors[k].join('\n') + '\n'; });
                            alert(msg);
                        } else {
                            showToast('Error adding item', 'danger');
                        }
                    }
                });
            });


            // Add a new row into table and hydrate its status select
            function addNewRowToTable(tableType, data) {
                const tableId = tableType + 'Table';
                let newRow = '';

                if (tableType === 'difficulties') {
                    newRow = `
                    <tr data-id="${data.id}" data-status-id="${data.status_id || ''}" class="data-row">
                    <td class="click-edit"><span class="view-mode">${data.id}</span></td>
                    <td class="click-edit">
                    <span class="view-mode">${data.difficulty || ''}</span>
                    <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="difficulty"
                    value="${data.difficulty || ''}" min="0">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.short_description || ''}</span>
                    <textarea class="form-control form-control-sm edit-mode d-none inline-edit"
                    data-field="short_description">${data.short_description || ''}</
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.description || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="description"
                    value="${data.description || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode status-badge"
                    data-status-id="${data.status_id || ''}">${renderStatusBadge(data.status_id)}</span>
                    <select class="form-select form-select-sm edit-mode d-none inline-edit" data-field="status_id"></select>
                    </td>
                    <td>
                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save"><i
                    class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel"><i
                    class="fas fa-times"></i></button>
                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                    </tr>`;
                } else if (tableType === 'types') {
                    newRow = `
                    <tr data-id="${data.id}" data-status-id="${data.status_id || ''}" class="data-row">
                    <td class="click-edit"><span class="view-mode">${data.id}</span></td>
                    <td class="click-edit">
                    <span class="view-mode">${data.type || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="type"
                    value="${data.type || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.description || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="description"
                    value="${data.description || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode status-badge"
                    data-status-id="${data.status_id || ''}">${renderStatusBadge(data.status_id)}</span>
                    <select class="form-select form-select-sm edit-mode d-none inline-edit" data-field="status_id"></select>
                    </td>
                    <td>
                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save"><i
                    class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel"><i
                    class="fas fa-times"></i></button>
                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                    </tr> `;
                } else if (tableType === 'levels') {
                    newRow = `
                    <tr data-id="${data.id}" data-status-id="${data.status_id || ''}" class="data-row">
                    <td class="click-edit"><span class="view-mode">${data.id}</span></td>
                    <td class="click-edit">
                    <span class="view-mode">${data.level || ''}</span>
                    <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="level"
                    value="${data.level || ''}" min="0">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.description || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit"
                    data-field="description" value="${data.description || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.age || ''}</span>
                    <input type="number" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="age"
                    value="${data.age || ''}" min="0">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode maxile-span">${data.start_maxile_level || 0} - ${data.end_maxile_level || 0}</span>
                    <div class="edit-mode d-none d-flex gap-1">
                    <input type="number" class="form-control form-control-sm inline-edit" data-field="start_maxile_level"
                    value="${data.start_maxile_level || 0}" style="width: 90px;">
                    <span>-</span>
                    <input type="number" class="form-control form-control-sm inline-edit" data-field="end_maxile_level"
                    value="${data.end_maxile_level || 0}" style="width: 90px;">
                    </div>
                    </td>
                    <td class="click-edit">
                    <span class="view-mode status-badge"
                    data-status-id="${data.status_id || ''}">${renderStatusBadge(data.status_id)}</span>
                    <select class="form-select form-select-sm edit-mode d-none inline-edit" data-field="status_id"></select>
                    </td>
                    <td>
                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save"><i
                    class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel"><i
                    class="fas fa-times"></i></button>
                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i class="fas fa-trash"></i></button>
                    </td>
                    </tr>`;
                } else if (tableType === 'roles') {
                    newRow = `
                    <tr data-id="${data.id}" data-status-id="${data.status_id || ''}" class="data-row">
                    <td class="click-edit"><span class="view-mode">${data.id}</span></td>
                    <td class="click-edit">
                    <span class="view-mode"><span class="badge bg-primary">${data.role || ''}</span></span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="role"
                    value="${data.role || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.description || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit"
                    data-field="description" value="${data.description || ''}">
                    </td>
                    <td>
                    <button class="btn btn-sm btn-outline-secondary permissions-btn" data-role-id="${data.id}">
                    <i class="fas fa-shield-alt"></i> Manage
                    </button>
                    </td>
                    <td><span class="badge bg-info">0</span></td>
                    <td class="click-edit">
                    <span class="view-mode status-badge"
                    data-status-id="${data.status_id || ''}">${renderStatusBadge(data.status_id)}</span>
                    <select class="form-select form-select-sm edit-mode d-none inline-edit" data-field="status_id"></select>
                    </td>
                    <td>
                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save"><i
                    class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel"><i
                    class="fas fa-times"></i></button>
                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                    class="fas fa-trash"></i></button>
                    </td>
                    </tr>`;
                } else if (tableType === 'units') {
                    newRow = `
                    <tr data-id="${data.id}" data-status-id="${data.status_id || ''}" class="data-row">
                    <td class="click-edit"><span class="view-mode">${data.id}</span></td>
                    <td class="click-edit">
                    <span class="view-mode">${data.unit || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit" data-field="unit"
                    value="${data.unit || ''}">
                    </td>
                    <td class="click-edit">
                    <span class="view-mode">${data.description || ''}</span>
                    <input type="text" class="form-control form-control-sm edit-mode d-none inline-edit"
                    data-field="description" value="${data.description || ''}">
                    </td>
                    <td>
                    <button class="btn btn-sm btn-success save-row-btn edit-mode d-none" title="Save"><i
                    class="fas fa-check"></i></button>
                    <button class="btn btn-sm btn-warning cancel-row-btn edit-mode d-none" title="Cancel"><i
                    class="fas fa-times"></i></button>
                    <button class="btn btn-sm btn-danger delete-row-btn" title="Delete"><i
                    class="fas fa-trash"></i></button>
                    </td>
                    </tr>`;
                }

                const $tbody = $('#' + tableId + ' tbody');
                $tbody.prepend(newRow);
            }
            function enterEditMode($row) {
                const rowId = $row.data('id');

    // Show edit mode FIRST before saving values
    $row.addClass('editing');
    $row.find('.view-mode').addClass('d-none');
    $row.find('.edit-mode').removeClass('d-none');
    
    // NOW save original values (after elements are visible)
    if (!originalRowData[rowId]) {
        originalRowData[rowId] = {};
        $row.find('.inline-edit').each(function () {
            const field = $(this).data('field');
            if ($(this).is('select')) {
                originalRowData[rowId][field] = $(this).val();
            } else if ($(this).is('textarea')) {
                originalRowData[rowId][field] = $(this).text() || $(this).val();
            } else {
                originalRowData[rowId][field] = $(this).val();
            }
        });
    }

    // Focus first non-select input
    const $first = $row.find('.inline-edit').not('select').first();
    if ($first.length) $first.trigger('focus');
}
            // === Inline Edit UX without an "Edit" button ===
            // Click any visible value to enter edit mode for that row
            $(document).on('click', '.click-edit .view-mode', function () {
                const $row = $(this).closest('tr');
                enterEditMode($row);
            });
            // Focus any input -> enter edit mode
            $(document).on('focus', '.inline-edit', function () {
                const $row = $(this).closest('tr');
                enterEditMode($row);
            });

                    // ENTER to Save (except inside textarea) / ESC to Cancel
                    $(document).on('keydown', '.inline-edit', function (e) {
                        if (e.key === 'Enter' && this.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            $(this).closest('tr').find('.save-row-btn').trigger('click');
                        } else if (e.key === 'Escape') {
                            e.preventDefault();
                            $(this).closest('tr').find('.cancel-row-btn').trigger('click');
                        }
                    });

            // Cancel
            $(document).on('click', '.cancel-row-btn', function () {
                const $row = $(this).closest('tr');
                const rowId = $row.data('id');
                const orig = originalRowData[rowId] || {};
                $row.find('.inline-edit').each(function () {
                    const field = $(this).data('field');
                    if (field in orig) $(this).val(orig[field]);
                });
                // update maxile view back
                const startVal = $row.find('[data-field="start_maxile_level"]').val();
                const endVal = $row.find('[data-field="end_maxile_level"]').val();
                if ($row.find('.maxile-span').length) $row.find('.maxile-span').text(`${startVal} - ${endVal}`);

                $row.removeClass('editing');
                $row.find('.view-mode').removeClass('d-none');
                $row.find('.edit-mode').addClass('d-none');
                $row.find('.inline-edit').each(function () {
                    const field = $(this).data('field');
                    if ($(this).is('textarea')) {
                        originalRowData[rowId][field] = $(this).text() || $(this).val();
                    } else {
                        originalRowData[rowId][field] = $(this).val();
                    }
                });
            });

            // Save
            $(document).on('click', '.save-row-btn', function () {
                const $row = $(this).closest('tr');
                const rowId = $row.data('id');
                const table = $row.closest('table').attr('id');
                const data = {};
                $row.find('.inline-edit').each(function () {
                    const field = $(this).data('field');
                    data[field] = $(this).val();
                });

                let endpoint = '';
                switch (table) {
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
                    success: function (response) {
            // Update view spans
            $row.find('.inline-edit').each(function () {
                const field = $(this).data('field');
                const newValue = $(this).val();
                const $td = $(this).closest('td');
                const $view = $td.find('.view-mode');

                if (field === 'status_id') {
                    // Update status badge with the text, not the ID
                    $row.attr('data-status-id', newValue);
                    const selectedText = $(this).find('option:selected').text(); // ✅ Get text from selected option
                    $view.find('.badge').text(selectedText); // ✅ Update badge text
                } else if (field === 'start_maxile_level' || field === 'end_maxile_level') {
                    const startVal = $row.find('[data-field="start_maxile_level"]').val();
                    const endVal = $row.find('[data-field="end_maxile_level"]').val();
                    $row.find('.maxile-span').text(`${startVal} - ${endVal}`);
                } else if ($view.find('.badge').length > 0) {
                    $view.find('.badge').text(newValue);
                } else {
                    $view.text(newValue);
                }
            });

            $row.removeClass('editing');
            $row.find('.view-mode').removeClass('d-none');
            $row.find('.edit-mode').addClass('d-none');

            showToast('Updated successfully', 'success');
        },
        error: function () { showToast('Error updating record', 'danger'); }
    });
            });

            // Delete
            $(document).on('click', '.delete-row-btn', function () {
                if (!confirmDelete()) return;
                const $row = $(this).closest('tr');
                const rowId = $row.data('id');
                const table = $row.closest('table').attr('id');

                let endpoint = '';
                switch (table) {
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
                    success: function () {
                        $row.fadeOut(250, function () { $(this).remove(); });
                        showToast('Deleted successfully', 'success');
                    },
                    error: function () { showToast('Error deleting record', 'danger'); }
                });
            });

            // Permissions modal (unchanged)
            let currentRoleId = null;
            $(document).on('click', '.permissions-btn', function () {
                currentRoleId = $(this).data('role-id');
                $.ajax({
                    url: `/admin/roles/${currentRoleId}/permissions`,
                    method: 'GET',
                    success: function (response) {
                        let html = '<div class="row">';
                        const cats = {
                            users: 'User Management', content: 'Content Management', settings: 'Settings', reports:
                            'Reports'
                        };
                        Object.keys(cats).forEach(cat => {
                            html += `<div class="col-md-6 mb-3">
                            <h6>${cats[cat]}</h6>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="perm_${cat}_view" value="${cat}.view"
                            ${response.permissions?.includes(`${cat}.view`) ? 'checked' : ''}>
                            <label class="form-check-label" for="perm_${cat}_view">View</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="perm_${cat}_create" value="${cat}.create"
                            ${response.permissions?.includes(`${cat}.create`) ? 'checked' : ''}>
                            <label class="form-check-label" for="perm_${cat}_create">Create</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="perm_${cat}_edit" value="${cat}.edit"
                            ${response.permissions?.includes(`${cat}.edit`) ? 'checked' : ''}>
                            <label class="form-check-label" for="perm_${cat}_edit">Edit</label>
                            </div>
                            <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="perm_${cat}_delete" value="${cat}.delete"
                            ${response.permissions?.includes(`${cat}.delete`) ? 'checked' : ''}>
                            <label class="form-check-label" for="perm_${cat}_delete">Delete</label>
                            </div>
                            </div>`;
                        });
                        html += '</div>';
                        $('#permissionsContent').html(html);
                        $('#permissionsModal').modal('show');
                    },
                    error: function () { showToast('Error loading permissions', 'danger'); }
                });
            });

            $('#savePermissions').on('click', function () {
                const permissions = [];
                $('#permissionsContent input:checked').each(function () { permissions.push($(this).val()); });
                $.ajax({
                    url: `/admin/roles/${currentRoleId}/permissions`,
                    method: 'POST',
                    data: { permissions },
                    success: function () {
                        $('#permissionsModal').modal('hide'); showToast('Permissions updated successfully',
                            'success');
                    },
                    error: function () { showToast('Error updating permissions', 'danger'); }
                });
            });

            // ===== Click-to-sort headers (client-side) =====
            function sortTable($table, colIndex, asc) {
                const $tbody = $table.find('tbody');
                const rows = $tbody.find('tr').get();
                rows.sort((a, b) => {
                    const A = ($(a).children().eq(colIndex).text() || '').trim();
                    const B = ($(b).children().eq(colIndex).text() || '').trim();
                    const nA = parseFloat(A.replace(/[^0-9.\-]/g, '')); const nB = parseFloat(B.replace(/[^0-9.\-]/g, ''));
                    const bothNumeric = !isNaN(nA) && !isNaN(nB) && A !== '' && B !== '';
                    if (bothNumeric) return asc ? (nA - nB) : (nB - nA);
                    return asc ? A.localeCompare(B) : B.localeCompare(A);
                });
                $.each(rows, (_, row) => $tbody.append(row));
            }
            const sortState = {}; // tableId -> {col, asc}
            $(document).on('click', 'th.sortable', function () {
                const $th = $(this), $table = $th.closest('table');
                const tableId = $table.attr('id');
                const col = parseInt($th.data('col'), 10);
                const state = sortState[tableId] || { col: -1, asc: true };
                const asc = state.col === col ? !state.asc : true;
                sortState[tableId] = { col, asc };
                sortTable($table, col, asc);
                // mark header
                $table.find('th.sortable').removeClass('sort-asc sort-desc');
                $th.addClass(asc ? 'sort-asc' : 'sort-desc');
            });
        });

    </script>
    @endpush

    @push('styles')
    <style>
        .nav-tabs .nav-link {
            color: var(--bs-dark);
            border: none;
            border-bottom: 2px solid transparent;
            transition: all .3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--bs-primary);
            border-bottom-color: var(--bs-primary);
        }

        .nav-tabs .nav-link.active {
            color: var(--bs-primary);
            background: transparent;
            border-bottom-color: var(--bs-primary);
        }

        .table-responsive {
            min-height: 400px;
        }

        .badge {
            padding: 4px 8px;
            font-weight: 500;
        }

        .gap-1 {
            gap: .25rem;
        }

        .data-row {
            animation: fadeIn .3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .3);
        }

        .btn {
            transition: all .3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, .2);
        }

        .toast {
            min-width: 300px;
        }

        /* clickable header sort indicators */
        th.sortable {
            cursor: pointer;
            position: relative;
        }

        th.sortable.sort-asc::after {
            content: "▲";
            font-size: .65rem;
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: .6;
        }

        th.sortable.sort-desc::after {
            content: "▼";
            font-size: .65rem;
            position: absolute;
            right: .5rem;
            top: 50%;
            transform: translateY(-50%);
            opacity: .6;
        }

        /* make cells feel editable */
        td.click-edit .view-mode {
            display: inline-block;
            min-height: 1.6rem;
            padding: .25rem .2rem;
            border-radius: .25rem;
        }

        td.click-edit .view-mode:hover {
            background: rgba(0, 0, 0, .03);
        }

        /* force-hide view layer when JS adds d-none */
        .view-mode.d-none {
            display: none !important;
        }

        /* ensure the editor sits above any leftover view layer */
        td.click-edit .inline-edit {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        td.click-edit .view-mode {
            position: relative;
            z-index: 1;
        }

        /* textarea comfortable height */
        td.click-edit textarea.inline-edit {
            min-height: 2.25rem;
        }
    </style>
    @endpush