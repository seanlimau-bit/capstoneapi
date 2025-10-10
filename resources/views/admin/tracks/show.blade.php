@extends('layouts.admin')

@section('title', 'Track: ' . $track->track)

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    @include('admin.components.page-header', [
        'title' => $track->track,
        'subtitle' => 'Manage track details, levels, and skills',
        'icon' => 'route',
        'breadcrumbs' => [
            ['title' => 'Tracks', 'url' => route('admin.tracks.index')],
            ['title' => $track->track, 'url' => '']
        ],
        'actions' => [
            [
                'text' => 'Add Skill',
                'onclick' => 'showAddSkillForm()',
                'icon' => 'plus',
                'class' => 'success'
            ],
            [
                'text' => 'Actions',
                'type' => 'dropdown',
                'icon' => 'ellipsis-v',
                'class' => 'outline-secondary',
                'items' => [
                    ['text' => 'Duplicate Track', 'icon' => 'copy', 'onclick' => 'copyTrack(' . $track->id . ', \'' . addslashes($track->track) . '\', ' . $track->skills->count() . ')'],
                    ['text' => 'Export Skills', 'icon' => 'download', 'onclick' => 'exportSkills()'],
                    'divider',
                    ['text' => 'Delete Track', 'icon' => 'trash', 'onclick' => 'deleteTrack(' . $track->id . ')']
                ]
            ]
        ]
    ])

    {{-- Statistics Row --}}
    @include('admin.components.stats-row', [
        'stats' => [
            [
                'value' => $track->skills->count(),
                'label' => 'Skills',
                'color' => 'primary',
                'icon' => 'brain'
            ],
            [
                'value' => $track->skills->sum(function($skill) { return $skill->questions ? $skill->questions->count() : 0; }),
                'label' => 'Questions',
                'color' => 'info',
                'icon' => 'question-circle'
            ],
            [
                'value' => $track->skills->where('status_id', 3)->count(),
                'label' => 'Active Skills',
                'color' => 'success',
                'icon' => 'check-circle'
            ],
            [
                'value' => $track->level ? $track->level->level : 'N/A',
                'label' => 'Level',
                'color' => 'warning',
                'icon' => 'layer-group'
            ]
        ]
    ])

    <div class="row">
        {{-- Track Details Card --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Track Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Track Name</label>
                            <div class="editable-field" data-field="track" data-type="text">
                                <span class="field-display">{{ $track->track }}</span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="editable-field" data-field="status_id" data-type="select">
                                <span class="field-display">
                                    @if($track->status_id == 3)
                                        <span class="badge bg-success">Active</span>
                                    @elseif($track->status_id == 4)
                                        <span class="badge bg-warning">Draft</span>
                                    @else
                                        <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Level</label>
                            <div class="editable-field" data-field="level_id" data-type="select">
                                <span class="field-display">
                                    @if($track->level)
                                        <span class="badge bg-info">Level {{ $track->level->description }}</span>
                                    @else
                                        <span class="text-muted">No level assigned</span>
                                    @endif
                                </span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Field</label>
                            <div class="editable-field" data-field="field_id" data-type="select">
                                <span class="field-display">
                                    @if($track->field)
                                        <span class="badge bg-secondary">{{ $track->field->field }}</span>
                                    @else
                                        <span class="text-muted">No field assigned</span>
                                    @endif
                                </span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <div class="editable-field" data-field="description" data-type="textarea">
                                <span class="field-display">{{ $track->description ?? 'No description' }}</span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Created</label>
                            <div>{{ $track->created_at->format('M j, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Skills Card --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Skills ({{ $track->skills->count() }})</h5>
                    <div>
                        <button class="btn btn-sm btn-success" onclick="showAddSkillForm()">
                            <i class="fas fa-plus"></i> Add Skill
                        </button>
                        @if($track->skills->count() > 0)
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportSkills()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($track->skills->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Id</th>
                                        <th>Skill</th>
                                        <th width="120">Questions</th>
                                        <th width="120">Status</th>
                                        <th width="80">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($track->skills as $skill)
                                    <tr>
                                        <td>
                                            <div>
                                                <h6 class="mb-0">{{ $skill->id }}</h6>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <h6 class="mb-0">{{ $skill->skill }}</h6>
                                                <small class="text-muted">
                                                    @if(strlen($skill->description ?? '') > 100)
                                                        {{ substr($skill->description, 0, 100) }}...
                                                    @else
                                                        {{ $skill->description ?? 'No description' }}
                                                    @endif
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($skill->questions && $skill->questions->count() > 0)
                                                <span class="badge bg-primary">{{ $skill->questions->count() }}</span>
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $skill->status && $skill->status->status === 'active' ? 'success' : 'warning' }}">
                                                {{ $skill->status ? ucfirst($skill->status->status) : 'Unknown' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.skills.show', $skill) }}" class="btn btn-outline-info" title="View Skill">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" onclick="removeSkill({{ $skill->id }})" title="Remove from Track">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        @include('admin.components.empty-state', [
                            'icon' => 'brain',
                            'title' => 'No Skills Assigned',
                            'message' => 'Add skills to this track to get started'
                        ])
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Field Information Card --}}
            @if($track->field)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Field Information</h5>
                </div>
                <div class="card-body text-center">
                    <h6 class="text-secondary mb-1">{{ $track->field->field }}</h6>
                    <p class="text-muted small mb-2">{{ $track->field->description }}</p>
                    <small class="text-muted">{{ $track->field->tracks->count() ?? 0 }} tracks in this field</small>
                </div>
            </div>
            @endif

            {{-- Maxile Level Information Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Maxile Level Information</h5>
                </div>
                <div class="card-body text-center">
                    @if($track->level)
                        <h4 class="text-primary mb-1">Level {{ $track->level->level }}</h4>
                        <p class="text-muted small mb-2">{{ $track->level->description }}</p>
                        <small class="text-muted">{{ $track->level->tracks->count() }} tracks at this level</small>
                    @else
                        @include('admin.components.empty-state', [
                            'icon' => 'layer-group',
                            'title' => 'No Maxile Level Assigned',
                            'message' => 'Assign a level to organize this track'
                        ])
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            @include('admin.components.management-grid', [
                'columns' => 12,
                'items' => [
                    [
                        'title' => 'Skills Analytics',
                        'description' => 'View detailed skills performance in this track',
                        'icon' => 'chart-line',
                        'color' => 'info',
                        'onclick' => 'showAnalytics()',
                        'action_text' => 'View Analytics',
                        'action_icon' => 'chart-bar'
                    ],
                    [
                        'title' => 'Bulk Operations',
                        'description' => 'Perform bulk actions on track skills',
                        'icon' => 'tasks',
                        'color' => 'warning',
                        'onclick' => 'showBulkOperations()',
                        'action_text' => 'Bulk Actions',
                        'action_icon' => 'cogs'
                    ]
                ]
            ])

            {{-- System Status --}}
            @include('admin.components.system-status', [
                'title' => 'Track Status',
                'icon' => 'heartbeat',
                'lastUpdated' => $track->updated_at->diffForHumans(),
                'statuses' => [
                    [
                        'label' => 'Track Status',
                        'value' => $track->status ? ucfirst($track->status->status) : 'Unknown',
                        'type' => 'status',
                        'color' => $track->status && $track->status->status === 'active' ? 'success' : 'warning',
                        'icon' => 'route'
                    ],
                    [
                        'label' => 'Field',
                        'value' => $track->field ? $track->field->field : 'Not assigned',
                        'type' => 'badge',
                        'color' => 'secondary'
                    ],
                    [
                        'label' => 'Skills Count',
                        'value' => $track->skills->count(),
                        'type' => 'badge',
                        'color' => 'primary'
                    ],
                    [
                        'label' => 'Questions Total',
                        'value' => $track->skills->sum(function($skill) { return $skill->questions ? $skill->questions->count() : 0; }),
                        'type' => 'badge',
                        'color' => 'info'
                    ]
                ]
            ])
        </div>
    </div>
</div>

{{-- Add Skill Modal --}}
<div class="modal fade" id="addSkillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Skill to "{{ $track->track }}"</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSkillForm">
                    <div class="mb-3">
                        <label class="form-label">Select Skill</label>
                        <select name="skill_id" class="form-select" required id="skillSelect">
                            <option value="">Choose a skill...</option>
                            @php
                                $availableSkills = \App\Models\Skill::whereNotIn('id', $track->skills->pluck('id'))
                                    ->orderBy('skill')
                                    ->get();
                            @endphp
                            @foreach($availableSkills as $skill)
                                <option value="{{ $skill->id }}">
                                    {{ $skill->skill }}
                                    @if($skill->questions) ({{ $skill->questions->count() }} questions) @endif
                                </option>
                            @endforeach
                        </select>
                        @if($availableSkills->count() == 0)
                            <small class="text-muted">All skills are already assigned to this track.</small>
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="addSkillToTrack()" 
                        {{ $availableSkills->count() == 0 ? 'disabled' : '' }}>Add Skill</button>
            </div>
        </div>
    </div>
</div>

{{-- Copy Track Modal --}}
<div class="modal fade" id="copyTrackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Copy Track</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="copyTrackId">
                <p class="mb-3">You are about to create a copy of "<strong id="copyTrackName"></strong>".</p>
                <p>Choose what to copy with the track:</p>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="copySkillsOption" checked>
                    <label class="form-check-label" for="copySkillsOption">
                        <strong>Copy all assigned skills (<span id="copySkillCount">0</span> skills)</strong>
                    </label>
                    <small class="form-text text-muted d-block mt-1">
                        If unchecked, only track details will be copied (no skills attached)
                    </small>
                </div>
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-1"></i>
                    The new track will be named "<span id="copyTrackNewName"></span>" and you'll be taken to it after creation.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeCopyTrack()">
                    <i class="fas fa-copy me-1"></i> Copy Track
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const trackId = {{ $track->id }};

// Reference data
const referenceData = {
    statuses: @json($statuses ?? []),
    levels: @json($levels ?? []),
    fields: @json($fields ?? [])
};

document.addEventListener('DOMContentLoaded', function() {
    setupInlineEditing();
});

function setupInlineEditing() {
    document.querySelectorAll('.editable-field').forEach(element => {
        element.addEventListener('click', function() {
            const field = this.dataset.field;
            const type = this.dataset.type;
            const fieldDisplay = this.querySelector('.field-display');
            
            // Get the actual text content, removing any HTML tags for textarea
            let currentValue;
            if (type === 'textarea') {
                currentValue = fieldDisplay.textContent || fieldDisplay.innerText || '';
                if (currentValue === 'No description') currentValue = '';
            } else {
                currentValue = fieldDisplay.textContent.trim();
            }
            
            showInlineEditor(this, field, type, currentValue);
        });
    });
}

function showInlineEditor(element, field, type, currentValue) {
    const fieldDisplay = element.querySelector('.field-display');
    let input;

    switch(type) {
        case 'text':
            input = `<input type="text" class="form-control form-control-sm" value="${escapeHtml(currentValue)}" onblur="saveInlineEdit(this, '${field}')" onkeypress="handleEnterKey(event, this, '${field}')" autofocus>`;
            break;
        case 'textarea':
            input = `
                <textarea class="form-control form-control-sm mb-2" rows="3" autofocus data-field="${field}">${escapeHtml(currentValue)}</textarea>
                <div>
                    <button class="btn btn-sm btn-success me-1" onclick="saveTextareaEdit('${field}')">Save</button>
                    <button class="btn btn-sm btn-secondary" onclick="cancelEdit(this)">Cancel</button>
                </div>`;
            break;
        case 'select':
            if (field === 'status_id') {
                let options = referenceData.statuses.map(status => 
                    `<option value="${status.id}" ${currentValue.includes(status.status) ? 'selected' : ''}>${status.status}</option>`
                ).join('');
                input = `<select class="form-select form-select-sm" onchange="saveInlineEdit(this, '${field}')" autofocus>${options}</select>`;
            } else if (field === 'level_id') {
                let options = '<option value="">No level</option>' + referenceData.levels.map(level => 
                    `<option value="${level.id}" ${currentValue.includes(level.description) || currentValue.includes('Level ' + level.level) ? 'selected' : ''}>${level.description} (Level ${level.level})</option>`
                ).join('');
                input = `<select class="form-select form-select-sm" onchange="saveInlineEdit(this, '${field}')" autofocus>${options}</select>`;
            } else if (field === 'field_id') {
                let options = '<option value="">No field</option>' + referenceData.fields.map(fieldItem => 
                    `<option value="${fieldItem.id}" ${currentValue.includes(fieldItem.field) ? 'selected' : ''}>${fieldItem.field}</option>`
                ).join('');
                input = `<select class="form-select form-select-sm" onchange="saveInlineEdit(this, '${field}')" autofocus>${options}</select>`;
            }
            break;
    }

    fieldDisplay.innerHTML = input;
    element.dataset.originalValue = currentValue;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function saveTextareaEdit(field) {
    const textarea = document.querySelector(`textarea[data-field="${field}"]`);
    if (textarea) {
        saveInlineEdit(textarea, field);
    }
}

function cancelEdit(button) {
    const container = button.closest('.editable-field');
    const originalValue = container.dataset.originalValue;
    const fieldDisplay = container.querySelector('.field-display');
    
    // Restore original value
    if (originalValue === '' || originalValue === 'No description') {
        fieldDisplay.innerHTML = '<span class="text-muted">No description</span>';
    } else {
        fieldDisplay.textContent = originalValue;
    }
}

function handleEnterKey(event, input, field) {
    if (event.key === 'Enter') {
        saveInlineEdit(input, field);
    }
}

function saveInlineEdit(input, field) {
    const value = input.value;
    const container = input.closest('.editable-field');
    
    fetch(`/admin/tracks/${trackId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ field: field, value: value })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let displayValue = value;
            
            if (field === 'status_id') {
                const status = referenceData.statuses.find(s => s.id == value);
                const badgeClass = value == 3 ? 'bg-success' : (value == 4 ? 'bg-warning' : 'bg-secondary');
                displayValue = `<span class="badge ${badgeClass}">${status ? status.status : 'Unknown'}</span>`;
            } else if (field === 'level_id') {
                if (value) {
                    const level = referenceData.levels.find(l => l.id == value);
                    displayValue = `<span class="badge bg-info">Level ${level.level}</span>`;
                } else {
                    displayValue = '<span class="text-muted">No level assigned</span>';
                }
            } else if (field === 'field_id') {
                if (value) {
                    const fieldItem = referenceData.fields.find(f => f.id == value);
                    displayValue = `<span class="badge bg-secondary">${fieldItem ? fieldItem.field : 'Unknown'}</span>`;
                } else {
                    displayValue = '<span class="text-muted">No field assigned</span>';
                }
            } else if (field === 'description') {
                if (value.trim() === '') {
                    displayValue = '<span class="text-muted">No description</span>';
                } else {
                    displayValue = value;
                }
            }
            
            container.querySelector('.field-display').innerHTML = displayValue;
            showToast('Updated successfully', 'success');
        } else {
            showToast(data.message || 'Error updating field', 'error');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
        location.reload();
    });
}

function showAddSkillForm() {
    const modal = new bootstrap.Modal(document.getElementById('addSkillModal'));
    modal.show();
}

function addSkillToTrack() {
    const skillId = document.getElementById('skillSelect').value;
    
    if (!skillId) {
        showToast('Please select a skill', 'warning');
        return;
    }
    
    fetch(`/admin/tracks/${trackId}/skills/${skillId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ skill_id: skillId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Skill added to track successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addSkillModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Error adding skill to track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    });
}

function removeSkillFromTrack(skillId) {
    if (confirm('Remove this skill from the track?')) {
        fetch(`/admin/tracks/${trackId}/skills/${skillId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Skill removed from track', 'success');
                setTimeout(() => {
                    window.location.href = `/admin/tracks/${trackId}`;
                }, 1000);
            } else {
                showToast(data.message || 'Error removing skill', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        });
    }
}

function removeSkill(skillId) {
    removeSkillFromTrack(skillId);
}

function copyTrack(trackId, trackName = '', skillCount = 0) {
    // Populate modal with track details
    document.getElementById('copyTrackId').value = trackId;
    document.getElementById('copyTrackName').textContent = trackName || 'this track';
    document.getElementById('copySkillCount').textContent = skillCount;
    document.getElementById('copyTrackNewName').textContent = (trackName || 'Track') + ' (Copy)';
    
    const modal = new bootstrap.Modal(document.getElementById('copyTrackModal'));
    modal.show();
}

function executeCopyTrack() {
    const trackId = document.getElementById('copyTrackId').value;
    const copySkills = document.getElementById('copySkillsOption').checked;
    
    fetch(`/admin/tracks/${trackId}/copy`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            copy_skills: copySkills
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Track copied successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('copyTrackModal')).hide();
            window.location.href = `/admin/tracks/${data.track.id}`;
        } else {
            showToast(data.message || 'Error copying track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    });
}

function deleteTrack(trackId) {
    if (confirm('Delete this track? This action cannot be undone.')) {
        fetch(`/admin/tracks/${trackId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Track deleted successfully', 'success');
                window.location.href = '/admin/tracks';
            } else {
                showToast(data.message || 'Error deleting track', 'error');
            }
        });
    }
}

function exportSkills() {
    window.location.href = `/admin/tracks/${trackId}/export-skills`;
}

function showAnalytics() {
    alert('Analytics feature would open here');
}

function showBulkOperations() {
    alert('Bulk operations feature would open here');
}

function showToast(message, type = 'info') {
    alert(`${type.toUpperCase()}: ${message}`);
}
</script>
@endpush