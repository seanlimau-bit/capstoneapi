@extends('layouts.admin')

@section('content')
@php
    // Compute all values once at template start
    $fieldId = (int)$field->id;
    $fieldData = [
        'name' => $field->field ?? 'Untitled Field',
        'icon' => $field->icon ?? 'fa-cube',
        'description' => $field->description ?? 'No description provided',
        'complexity' => $field->complexity,
        'tracks_count' => $field->tracks_count ?? 0,
        'created_at' => $field->created_at?->format('M j, Y g:i A') ?? 'N/A',
        'updated_at' => $field->updated_at?->format('M j, Y g:i A') ?? 'N/A'
    ];
    
    $status = optional($field->status);
    $statusData = [
        'text' => ucfirst($status->status ?? 'unknown'),
        'badge' => $status->status === 'active' ? 'success' : 'warning',
        'value' => $status->status === 'active' ? '1' : '2'
    ];
    
    $complexityBadge = match($fieldData['complexity']) {
        'basic' => 'success',
        'intermediate' => 'warning', 
        'advanced' => 'danger',
        default => null
    };

    // Get tracks for this field
    $tracks = $field->tracks ?? collect();
@endphp

@include('admin.components.page-header', [
    'title' => $fieldData['name'],
    'subtitle' => 'Field Details & Track Management',
    'icon' => $fieldData['icon'],
    'breadcrumb' => [
        ['label' => 'Settings', 'url' => '/admin'],
        ['label' => 'Fields', 'url' => route('admin.fields.index')],
        ['label' => $fieldData['name'], 'active' => true]
    ],
    'actions' => [
        ['label' => 'Add Track', 'action' => 'openCreateTrackModal()', 'type' => 'primary'],
        ['label' => 'Export Data', 'action' => 'exportField()', 'type' => 'success'],
        ['label' => 'Delete Field', 'action' => "deleteField({$fieldId})", 'type' => 'danger']
    ]
])

<div class="row">
    <div class="col-md-8">
        <!-- Field Information -->
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Field Information</h5>
                <small class="text-muted">Click to edit inline</small>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Field Name</label>
                            <div class="editable-field" data-field="field" data-type="text" data-value="{{ e($fieldData['name']) }}">
                                {{ $fieldData['name'] }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Icon Class</label>
                            <div class="editable-field" data-field="icon" data-type="text" data-value="{{ e($fieldData['icon']) }}">
                                <i class="{{ $fieldData['icon'] }}"></i> {{ $fieldData['icon'] }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Complexity Level</label>
                            <div class="editable-field" 
                                 data-field="complexity" 
                                 data-type="select" 
                                 data-options='{"basic":"Basic","intermediate":"Intermediate","advanced":"Advanced"}'
                                 data-value="{{ e($fieldData['complexity'] ?? '') }}">
                                @if($fieldData['complexity'] && $complexityBadge)
                                    <span class="badge bg-{{ $complexityBadge }}">{{ ucfirst($fieldData['complexity']) }}</span>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="editable-field" 
                                 data-field="status_id" 
                                 data-type="select" 
                                 data-options='{"1":"Active","2":"Draft"}'
                                 data-value="{{ $statusData['value'] }}">
                                <span class="badge bg-{{ $statusData['badge'] }}">{{ $statusData['text'] }}</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Created</label>
                            <div>{{ $fieldData['created_at'] }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Last Updated</label>
                            <div>{{ $fieldData['updated_at'] }}</div>
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold">Description</label>
                    <div class="editable-field" data-field="description" data-type="textarea" data-value="{{ e($fieldData['description']) }}">
                        {{ $fieldData['description'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Tracks Management -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Field Tracks ({{ $fieldData['tracks_count'] }})</h5>
                <button class="btn btn-sm btn-primary" onclick="openCreateTrackModal()">
                    <i class="fas fa-plus me-1"></i>Add Track
                </button>
            </div>
            <div class="card-body">
                @if($tracks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm" id="tracksTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Track Name</th>
                                    <th>Description</th>
                                    <th>Skills Count</th>
                                    <th>Status</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tracks as $track)
                                <tr id="track-{{ $track->id }}">
                                    <td>
                                        <div class="fw-semibold">{{ $track->track ?? 'Untitled Track' }}</div>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px;">
                                            {{ $track->description ? (strlen($track->description) > 80 ? substr($track->description, 0, 80) . '...' : $track->description) : 'No description' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $track->skills_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ optional($track->status)->status === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst(optional($track->status)->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editTrack({{ $track->id }})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteTrack({{ $track->id }})" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-route fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Tracks Found</h5>
                        <p class="text-muted mb-3">This field doesn't have any tracks assigned yet.</p>
                        <button class="btn btn-primary" onclick="openCreateTrackModal()">
                            <i class="fas fa-plus me-2"></i>Create First Track
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Field Statistics -->
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Field Statistics</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">Total Tracks</span>
                    <span class="badge bg-primary">{{ $fieldData['tracks_count'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small">Active Tracks</span>
                    <span class="badge bg-success">{{ $tracks->filter(fn($t) => optional($t->status)->status === 'active')->count() }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">Draft Tracks</span>
                    <span class="badge bg-warning">{{ $tracks->filter(fn($t) => optional($t->status)->status !== 'active')->count() }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="card-title mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="openCreateTrackModal()">
                        <i class="fas fa-plus me-2"></i>Create Track
                    </button>
                    <button class="btn btn-outline-success" onclick="exportField()">
                        <i class="fas fa-download me-2"></i>Export Field Data
                    </button>
                    <button class="btn btn-outline-warning" onclick="duplicateField()">
                        <i class="fas fa-copy me-2"></i>Duplicate Field
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Track Modal -->
<div class="modal fade" id="trackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trackModalTitle">Create New Track</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="trackForm">
                <div class="modal-body">
                    <input type="hidden" id="trackId" name="track_id">
                    <div class="mb-3">
                        <label class="form-label">Track Name *</label>
                        <input type="text" class="form-control" id="trackName" name="track" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" id="trackDescription" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select class="form-select" id="trackLevel" name="level_id" required>
                            <option value="">Select level</option>
                            <!-- You'll need to populate this with actual levels -->
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" id="trackStatus" name="status_id" required>
                            <option value="1">Active</option>
                            <option value="2">Draft</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="trackSubmitBtn">Create Track</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const FIELD_ID = {{ $fieldId }};
const ENDPOINTS = {
    updateField: `/admin/fields/${FIELD_ID}`,
    deleteField: `/admin/fields/${FIELD_ID}`,
    export: `/admin/fields/${FIELD_ID}/export`,
    duplicate: `/admin/fields/${FIELD_ID}/duplicate`,
    createTrack: '/admin/tracks',
    updateTrack: trackId => `/admin/tracks/${trackId}`,
    deleteTrack: trackId => `/admin/tracks/${trackId}`
};

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
let trackModal, currentTrackId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeInlineEditing();
    trackModal = new bootstrap.Modal(document.getElementById('trackModal'));
    document.getElementById('trackForm').addEventListener('submit', handleTrackSubmit);
});

// Inline editing for field information
function initializeInlineEditing() {
    document.querySelectorAll('.editable-field').forEach(el => {
        el.addEventListener('click', function() {
            if (this.querySelector('input, textarea, select')) return;
            
            const { field: fieldName, type: fieldType, value: currentValue, options } = this.dataset;
            const input = createInput(fieldType, currentValue, options);
            
            this.innerHTML = `
                <div>${input.outerHTML}</div>
                <div class="mt-2">
                    <button class="btn btn-sm btn-success me-2" onclick="saveField('${fieldName}', this)">Save</button>
                    <button class="btn btn-sm btn-secondary" onclick="cancelEdit('${fieldName}', this)">Cancel</button>
                </div>
            `;
            
            this.querySelector('input, textarea, select').focus();
        });
    });
}

function createInput(type, value, options) {
    let input;
    if (type === 'textarea') {
        input = document.createElement('textarea');
        input.className = 'form-control';
        input.rows = 3;
    } else if (type === 'select') {
        input = document.createElement('select');
        input.className = 'form-select';
        const opts = JSON.parse(options || '{}');
        Object.entries(opts).forEach(([val, label]) => {
            input.add(new Option(label, val, false, val === value));
        });
    } else {
        input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control';
    }
    input.value = value || '';
    return input;
}

function saveField(fieldName, button) {
    const container = button.closest('.editable-field');
    const input = container.querySelector('input, textarea, select');
    const newValue = input.value;

    fetch(ENDPOINTS.updateField, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ field: fieldName, value: newValue })
    })
    .then(handleResponse)
    .then(() => {
        container.dataset.value = newValue;
        container.innerHTML = formatFieldValue(fieldName, newValue);
        showToast('Field updated successfully', 'success');
    })
    .catch(() => {
        showToast('Error updating field', 'error');
        cancelEdit(fieldName, button);
    });
}

function cancelEdit(fieldName, button) {
    const container = button.closest('.editable-field');
    const originalValue = container.dataset.value;
    container.innerHTML = formatFieldValue(fieldName, originalValue);
}

function formatFieldValue(fieldName, value) {
    if (!value) return '<span class="text-muted">Not set</span>';
    
    switch (fieldName) {
        case 'status_id':
            const isActive = value === '1';
            return `<span class="badge bg-${isActive ? 'success' : 'warning'}">${isActive ? 'Active' : 'Draft'}</span>`;
        case 'complexity':
            const badge = { basic: 'success', intermediate: 'warning', advanced: 'danger' }[value] || 'secondary';
            return `<span class="badge bg-${badge}">${value.charAt(0).toUpperCase() + value.slice(1)}</span>`;
        case 'icon':
            return `<i class="${value}"></i> ${value}`;
        default:
            return value;
    }
}

// Track management functions
function openCreateTrackModal() {
    currentTrackId = null;
    document.getElementById('trackModalTitle').textContent = 'Create New Track';
    document.getElementById('trackSubmitBtn').textContent = 'Create Track';
    document.getElementById('trackForm').reset();
    document.getElementById('trackId').value = '';
    trackModal.show();
}

function editTrack(trackId) {
    // In a real application, you'd fetch track data here
    // For now, we'll show a placeholder
    currentTrackId = trackId;
    document.getElementById('trackModalTitle').textContent = 'Edit Track';
    document.getElementById('trackSubmitBtn').textContent = 'Update Track';
    document.getElementById('trackId').value = trackId;
    
    // You would fetch and populate track data here
    // fetch(`/admin/tracks/${trackId}`)...
    
    trackModal.show();
}

function handleTrackSubmit(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const isEdit = currentTrackId !== null;
    const url = isEdit ? ENDPOINTS.updateTrack(currentTrackId) : ENDPOINTS.createTrack;
    const method = isEdit ? 'PUT' : 'POST';
    
    if (!isEdit) {
        formData.append('field_id', FIELD_ID);
    }

    // Debug: Log what we're sending
    console.log('Submitting track:', {
        url: url,
        method: method,
        field_id: FIELD_ID,
        csrf_token: CSRF_TOKEN ? 'present' : 'missing'
    });

    // Log form data
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    const submitBtn = document.getElementById('trackSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = isEdit ? 'Updating...' : 'Creating...';

    fetch(url, {
        method: method,
        body: formData,
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(response => {
        console.log('Full response object:', response);
        console.log('Response status:', response.status);
        console.log('Response statusText:', response.statusText);
        console.log('Response URL:', response.url);
        
        // Always get the text first to see what we received
        return response.text().then(text => {
            console.log('Raw response text:', text);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}\nResponse: ${text}`);
            }
            
            // Check if it's JSON
            try {
                const data = JSON.parse(text);
                return data;
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response was not JSON:', text);
                throw new Error('Server returned invalid JSON response: ' + text.substring(0, 100) + '...');
            }
        });
    })
    .then(data => {
        console.log('Parsed JSON data:', data);
        if (data.message) {
            showToast(data.message, 'success');
            trackModal.hide();
            location.reload();
        } else {
            throw new Error('No message in response: ' + JSON.stringify(data));
        }
    })
    .catch(error => {
        console.error('Detailed error information:');
        console.error('Error message:', error.message);
        console.error('Error stack:', error.stack);
        console.error('Full error object:', error);
        showToast('Error: ' + error.message, 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = isEdit ? 'Update Track' : 'Create Track';
    });
}

function deleteTrack(trackId) {
    if (!confirm('Are you sure you want to delete this track? This action cannot be undone.')) return;
    
    fetch(ENDPOINTS.deleteTrack(trackId), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    })
    .then(handleResponse)
    .then(data => {
        if (data.success) {
            showToast('Track deleted successfully', 'success');
            document.getElementById(`track-${trackId}`).remove();
        } else {
            throw new Error(data.message || 'Error deleting track');
        }
    })
    .catch(error => showToast(error.message, 'error'));
}

// Field management functions
function deleteField() {
    if (!confirm('Are you sure you want to delete this field? This will also delete all associated tracks.')) return;
    
    fetch(ENDPOINTS.deleteField, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } })
        .then(handleResponse)
        .then(() => {
            showToast('Field deleted successfully', 'success');
            window.location.href = '/admin/fields';
        })
        .catch(() => showToast('Error deleting field', 'error'));
}

function exportField() { 
    window.location.href = ENDPOINTS.export; 
}

function duplicateField() {
    if (!confirm('Create a duplicate of this field?')) return;
    
    fetch(ENDPOINTS.duplicate, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } })
        .then(handleResponse)
        .then(data => {
            showToast('Field duplicated successfully', 'success');
            window.location.href = `/admin/fields/${data.field_id}`;
        })
        .catch(() => showToast('Error duplicating field', 'error'));
}

// Utility functions
const handleResponse = r => r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`));

function showToast(message, type = 'info') {
    alert(`${type.toUpperCase()}: ${message}`);
}
</script>
@endpush