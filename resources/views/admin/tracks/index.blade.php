@extends('layouts.admin')

@section('title', 'Track Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header - Using standard component --}}
    @include('admin.components.page-header', [
    'title' => 'Track Management',
    'subtitle' => 'Manage educational tracks, levels, and skills',
    'icon' => 'route',
    'breadcrumbs' => [
    ['title' => 'Tracks', 'url' => '']
    ],
    'actions' => [
    [
    'text' => 'Add Track',
    'url' => route('admin.tracks.create'),
    'icon' => 'plus',
    'class' => 'success'
    ],
    [
    'text' => 'Actions',
    'type' => 'dropdown',
    'icon' => 'ellipsis-v',
    'class' => 'outline-secondary',
    'items' => [
    ['text' => 'Import Tracks', 'icon' => 'upload', 'onclick' => 'showImportModal()'],
    ['text' => 'Export All', 'icon' => 'download', 'onclick' => 'exportTracks()'],
    'divider',
    ['text' => 'Bulk Operations', 'icon' => 'cogs', 'onclick' => 'showBulkOperations()']
    ]
    ]
    ]
    ])

    {{-- Statistics Row - Using standard component --}}
    @include('admin.components.stats-row', [
    'stats' => [
    [
    'value' => $tracks->count(),
    'label' => 'Total Tracks',
    'color' => 'primary',
    'icon' => 'route',
    'id' => 'totalTracksCount'
    ],
    [
    'value' => $tracks->where('status_id', 3)->count(),
    'label' => 'Active Tracks',
    'color' => 'success',
    'icon' => 'check-circle',
    'id' => 'activeTracksCount'
    ],
    [
    'value' => $tracks->sum(function($track) { return $track->skills ? $track->skills->count() : 0; }),
    'label' => 'Total Skills',
    'color' => 'info',
    'icon' => 'brain',
    'id' => 'totalSkillsCount'
    ],
    [
    'value' => $tracks->where('status_id', 4)->count(),
    'label' => 'Draft Tracks',
    'color' => 'warning',
    'icon' => 'edit',
    'id' => 'draftTracksCount'
    ]
    ]
    ])

    {{-- Filters - Using standard filters-card component --}}
    @component('admin.components.filters-card', ['items' => $tracks])
    <div class="col-md-3">
        <label class="form-label fw-bold">Status</label>
        <select class="form-select" id="statusFilter">
            <option value="">All Statuses</option>
            @foreach($tracks->pluck('status')->filter()->unique('id')->sortBy('status') as $status)
            <option value="{{ strtolower($status->status) }}">{{ ucfirst($status->status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label fw-bold">Level</label>
        <select class="form-select" id="levelFilter">
            <option value="">All Levels</option>
            @foreach($levels as $level)
            <option value="{{ $level->description }}">{{ $level->description }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label fw-bold">Search</label>
        <div class="input-group">
            <input type="text" class="form-control" placeholder="Search tracks, fields, descriptions..." id="searchInput">
            <span class="input-group-text">
                <i class="fas fa-search"></i>
            </span>
        </div>
    </div>
    <div class="col-md-2">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
            <i class="fas fa-times me-1"></i>Clear
        </button>
    </div>
    @endcomponent

    {{-- Data Table --}}
    @if($tracks->isEmpty())
    @include('admin.components.empty-state', [
    'icon' => 'route',
    'title' => 'No Tracks Found',
    'message' => 'Create your first track to get started'
    ])
    @else
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tracks Overview</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tracksTable">
                    <thead class="table-light">
                        <tr>
                            <th>Track</th>
                            <th width="120">Field</th>
                            <th width="150">Level</th>
                            <th width="80">Skills</th>
                            <th width="100">Status</th>
                            <th width="120">Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tracks as $track)
                        <tr class="item-row" 
                        data-id="{{ $track->id }}" 
                        data-status="{{ $track->status ? strtolower($track->status->status) : 'unknown' }}" 
                        data-level="{{ $track->level ? $track->level->description : '' }}"
                        data-name="{{ strtolower($track->track) }}"
                        data-description="{{ strtolower($track->description ?? '') }}"
                        data-field="{{ strtolower($track->field->field ?? '') }}"
                        data-skills="{{ $track->skills ? $track->skills->count() : 0 }}">                        <td>
                            <div>
                                <h6 class="mb-0 track-name searchable">{{ $track->track }}</h6>
                                <small class="text-muted track-desc searchable">
                                    @if(strlen($track->description ?? '') > 80)
                                    {{ substr($track->description, 0, 80) }}...
                                    @else
                                    {{ $track->description ?? 'No description' }}
                                    @endif
                                </small>
                            </div>
                        </td>
                        <td>
                            @if($track->field)
                            <span class="badge bg-secondary field-name searchable">{{ $track->field->field }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($track->level)
                            <span class="badge bg-info">{{ $track->level->description }}</span>
                            @else
                            <span class="text-muted">No level assigned</span>
                            @endif
                        </td>
                        <td>
                            @if($track->skills && $track->skills->count() > 0)
                            <span class="badge bg-primary">{{ $track->skills->count() }}</span>
                            @else
                            <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>
                            @if($track->status)
                            @php
                            $statusConfig = [
                            'Public' => ['class' => 'success', 'icon' => 'globe'],
                            'Draft' => ['class' => 'warning', 'icon' => 'edit'],
                            'Only Me' => ['class' => 'info', 'icon' => 'lock'],
                            'Restricted' => ['class' => 'secondary', 'icon' => 'ban']
                            ];
                            $config = $statusConfig[$track->status->status] ?? ['class' => 'secondary', 'icon' => 'question'];
                            @endphp
                            <span class="badge bg-{{ $config['class'] }}">
                                <i class="fas fa-{{ $config['icon'] }} me-1"></i>{{ $track->status->status }}
                            </span>
                            @else
                            <span class="badge bg-secondary">Unknown</span>
                            @endif
                        </td>                                <td>
                            <small class="text-muted">{{ $track->created_at->format('M j, Y') }}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.tracks.show', $track) }}" class="btn btn-outline-info" title="View Track">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-outline-secondary" onclick="copyTrack({{ $track->id }})" title="Duplicate Track">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteTrack({{ $track->id }})" title="Delete Track">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- No Results State (for filtering) --}}
        <div id="noResults" class="d-none text-center py-5">
            @include('admin.components.empty-state', [
            'icon' => 'search',
            'title' => 'No matching results',
            'message' => 'Try adjusting your search filters'
            ])
        </div>
    </div>
</div>
@endif
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
<script src="{{ asset('js/admin/admin.js') }}"></script>
<script>
// Standard configuration for the Tracks page
window.adminConfig = {
    filters: [
    {id: 'statusFilter', key: 'status', type: 'select'},
    {id: 'levelFilter', key: 'level', type: 'select'},
    {id: 'searchInput', key: 'search', type: 'search'}
    ],
    searchFields: ['name', 'description', 'field'], // Maps to data-name, data-description, data-field
    highlightSelectors: ['.track-name', '.track-desc', '.field-name'],
    dynamicStats: true,
    stats: [
    {
        id: 'totalTracksCount', 
        calculator: items => items.length
    },
    {
        id: 'activeTracksCount', 
        calculator: items => items.filter(item => item.status === 'active').length
    },
    {
        id: 'draftTracksCount', 
        calculator: items => items.filter(item => item.status === 'draft').length
    },
    {
        id: 'totalSkillsCount', 
        calculator: items => items.reduce((sum, item) => sum + parseInt(item.skills || 0), 0)
    }
    ]
};

// Page-specific functions using standard patterns
function copyTrack(trackId) {
    // Get track data from the row
    const row = document.querySelector(`tr[data-id="${trackId}"]`);
    const trackName = row.querySelector('.track-name').textContent;
    const skillCount = row.dataset.skills;
    
    AdminModals.show('copyTrackModal', {
        copyTrackId: trackId,
        copyTrackName: trackName,
        copySkillCount: skillCount,
        copyTrackNewName: trackName + ' (Copy)'
    });
}

function executeCopyTrack() {
  const trackId = document.getElementById('copyTrackId').value;
  const copySkills = document.getElementById('copySkillsOption').checked;

  const copyButton = document.querySelector('.modal-footer .btn-primary');
  adminAjax.setLoadingState(copyButton, true);

  adminAjax.create(`/admin/tracks/${trackId}/duplicate`, { copy_skills: copySkills })
  .then(data => {
      if (!data || data.success !== true) {
        throw new Error((data && data.message) || 'Error copying track');
    }
    AdminToast.show(data.message || 'Track copied successfully', 'success');
    AdminModals.hide('copyTrackModal');

      // ✅ Use the keys your controller returns
      if (data.redirect) {
        window.location.assign(data.redirect);
    } else if (data.track_id) {
        window.location.assign(`/admin/tracks/${data.track_id}`);
    } else {
        // Fallback, but you shouldn't hit this if controller returns as coded
        window.location.assign('/admin/tracks');
    }
})
  .catch(error => {
      AdminToast.show(error.message || 'Network error occurred', 'error');
  })
  .finally(() => {
      adminAjax.setLoadingState(copyButton, false);
  });
}


function deleteTrack(trackId) {
    const row = document.querySelector(`tr[data-id="${trackId}"]`);
    const trackName = row.querySelector('.track-name').textContent;
    
    if (!confirm(`Delete "${trackName}"? This action cannot be undone.`)) {
        return;
    }
    
    const deleteButton = document.querySelector(`button[onclick*="deleteTrack(${trackId})"]`);
    
    fetch(`/admin/tracks/${trackId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.status === 409) {
            // Handle conflict - track has dependencies
            return response.json().then(data => {
                if (data.requires_confirmation) {
                    // Ask user if they want to remove dependencies first
                    if (confirm(data.message + '\n\nClick OK to remove dependencies and delete the track.')) {
                        deleteTrackWithDependencies(trackId);
                    }
                } else {
                    showToast(data.message || 'Cannot delete track - it has dependencies', 'error');
                }
            });
        } else if (response.ok) {
            return response.json().then(data => {
                showToast('Track deleted successfully', 'success');
                row.remove();
            });
        } else {
            throw new Error('Delete failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error deleting track', 'error');
    });
}

function deleteTrackWithDependencies(trackId) {
    fetch(`/admin/tracks/${trackId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            force: true,
            remove_dependencies: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Track and dependencies deleted successfully', 'success');
            const row = document.querySelector(`tr[data-id="${trackId}"]`);
            if (row) row.remove();
        } else {
            showToast(data.message || 'Error deleting track', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error deleting track', 'error');
    });
}

function showImportModal() {
    AdminToast.show('Import functionality coming soon', 'info');
}

function exportTracks() {
    window.location.href = '/admin/tracks/export';
}

function showBulkOperations() {
    AdminToast.show('Bulk operations functionality coming soon', 'info');
}
</script>
@endpush