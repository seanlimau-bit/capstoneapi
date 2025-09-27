@extends('layouts.admin')

@section('title', 'Track Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
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

    {{-- Stats --}}
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

    {{-- Filters --}}
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
                <input type="text" class="form-control" placeholder="Search tracks or descriptions..." id="searchInput">
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

    {{-- Table --}}
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
                                <th data-sort="id" class="sortable">Id</th>
                                <th data-sort="track" class="sortable">Track</th>
                                <th data-sort="description" class="sortable" width="320">Description</th>
                                <th data-sort="level" class="sortable" width="150">Level</th>
                                <th data-sort="skills" class="sortable" width="80">Skills</th>
                                <th data-sort="status" class="sortable" width="120">Status</th>
                                <th data-sort="created" class="sortable" width="140">Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $statusConfig = [
                                    'Public' => ['class' => 'success', 'icon' => 'globe'],
                                    'Draft' => ['class' => 'warning', 'icon' => 'edit'],
                                    'Only Me' => ['class' => 'info', 'icon' => 'lock'],
                                    'Restricted' => ['class' => 'secondary', 'icon' => 'ban']
                                ];
                                $allStatusLabels = array_keys($statusConfig);
                            @endphp
                            @foreach($tracks as $track)
                                @php
                                    $statusText = $track->status->status ?? 'Unknown';
                                    $statusMeta = $statusConfig[$statusText] ?? ['class' => 'secondary', 'icon' => 'question'];
                                @endphp
                                <tr class="item-row"
                                    data-id="{{ $track->id }}"
                                    data-status="{{ $track->status ? strtolower($statusText) : 'unknown' }}"
                                    data-level="{{ $track->level->description ?? '' }}"
                                    data-name="{{ strtolower($track->track) }}"
                                    data-description="{{ strtolower($track->description ?? '') }}"
                                    data-skills="{{ $track->skills ? $track->skills->count() : 0 }}"
                                    data-created="{{ $track->created_at->timestamp }}">
                                    {{-- Id --}}
                                    <td class="text-muted fw-semibold">{{ $track->id }}</td>

                                    {{-- Track (editable text) --}}
                                    <td data-editable="true" data-field="track" data-edit="text">
                                        <h6 class="mb-0 track-name searchable">{{ $track->track }}</h6>
                                    </td>

                                    {{-- Description (editable text) --}}
                                    <td data-editable="true" data-field="description" data-edit="text">
                                        <small class="text-muted track-desc searchable">
                                            @if(strlen($track->description ?? '') > 160)
                                                {{ substr($track->description, 0, 160) }}...
                                            @else
                                                {{ $track->description ?? 'No description' }}
                                            @endif
                                        </small>
                                    </td>

                                    {{-- Level (editable select) --}}
                                    <td data-editable="true" data-field="level_description" data-edit="select"
                                        data-options='@json($levels->pluck("description"))'>
                                        @if($track->level)
                                            <span class="badge bg-info">{{ $track->level->description }}</span>
                                        @else
                                            <span class="text-muted">No level assigned</span>
                                        @endif
                                    </td>

                                    {{-- Skills (read-only) --}}
                                    <td>
                                        @if($track->skills && $track->skills->count() > 0)
                                            <span class="badge bg-primary">{{ $track->skills->count() }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>

                                    {{-- Status (editable select) --}}
                                    <td data-editable="true" data-field="status" data-edit="select" data-options='@json($allStatusLabels)'>
                                        <span class="badge bg-{{ $statusMeta['class'] }}">
                                            <i class="fas fa-{{ $statusMeta['icon'] }} me-1"></i>{{ $statusText }}
                                        </span>
                                    </td>

                                    {{-- Created --}}
                                    <td><small class="text-muted">{{ $track->created_at->format('M j, Y') }}</small></td>

                                    {{-- Actions --}}
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

                {{-- No Results --}}
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
/**
 * Tracks page: filters, sorting, inline editing (Track, Description, Level, Status)
 * - Level & Status use dropdowns
 * - Optimistic PATCH saves
 * - Stable sorting + debounced filtering
 */
(function() {
  const table = document.getElementById('tracksTable');
  if (!table) return;

  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr.item-row'));
  const noResults = document.getElementById('noResults');

  const statusFilter = document.getElementById('statusFilter');
  const levelFilter  = document.getElementById('levelFilter');
  const searchInput  = document.getElementById('searchInput');

  let sortState = { key: 'id', dir: 'asc' };

  // debounce
  const debounce = (fn, ms = 200) => {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };

  // Build cache for performance
  const modelCache = new Map();
  rows.forEach(r => {
    modelCache.set(r, {
      id: Number(r.dataset.id),
      track: (r.querySelector('.track-name')?.textContent || '').toLowerCase(),
      description: (r.dataset.description || '').toLowerCase(),
      level: (r.dataset.level || '').toLowerCase(),
      status: (r.dataset.status || '').toLowerCase(),
      skills: Number(r.dataset.skills || 0),
      created: Number(r.dataset.created || 0),
      textBlob: ((r.dataset.name || '') + ' ' + (r.dataset.description || '')).toLowerCase()
    });
  });

  // FILTERING
  function applyFilters() {
    const sStatus = (statusFilter?.value || '').toLowerCase();
    const sLevel  = (levelFilter?.value || '').toLowerCase();
    const sQuery  = (searchInput?.value || '').trim().toLowerCase();

    let visible = 0;
    for (const r of rows) {
      const m = modelCache.get(r);
      const okStatus = !sStatus || m.status === sStatus;
      const okLevel  = !sLevel  || m.level === sLevel;
      const okQuery  = !sQuery  || m.textBlob.includes(sQuery);
      const show = okStatus && okLevel && okQuery;
      r.classList.toggle('d-none', !show);
      if (show) visible++;
    }
    noResults?.classList.toggle('d-none', visible !== 0);
    updateStats();
  }
  const debouncedFilter = debounce(applyFilters, 120);

  statusFilter?.addEventListener('change', debouncedFilter);
  levelFilter?.addEventListener('change', debouncedFilter);
  searchInput?.addEventListener('input', debouncedFilter);

  window.clearFilters = function() {
    if (statusFilter) statusFilter.value = '';
    if (levelFilter) levelFilter.value = '';
    if (searchInput) searchInput.value = '';
    applyFilters();
  };

  // SORTING
  function applySort(key, dir) {
    const liveRows = rows.filter(r => !r.classList.contains('d-none'));
    const withIdx = liveRows.map((r, i) => ({ r, i, m: modelCache.get(r) }));

    const cmp = (a, b) => {
      let va = a.m[key], vb = b.m[key];
      if (typeof va === 'string' && typeof vb === 'string') {
        const res = va.localeCompare(vb);
        return dir === 'asc' ? res : -res;
      }
      if (va < vb) return dir === 'asc' ? -1 : 1;
      if (va > vb) return dir === 'asc' ? 1 : -1;
      return a.i - b.i;
    };

    withIdx.sort(cmp);

    const frag = document.createDocumentFragment();
    const hiddenRows = rows.filter(r => r.classList.contains('d-none'));
    withIdx.forEach(({ r }) => frag.appendChild(r));
    hiddenRows.forEach(r => frag.appendChild(r));
    tbody.appendChild(frag);
  }

  function setSortIndicator(th, active, dir) {
    th.querySelectorAll('.sort-caret')?.forEach(el => el.remove());
    if (!active) return;
    const caret = document.createElement('span');
    caret.className = 'sort-caret ms-1';
    caret.innerHTML = dir === 'asc' ? '&uarr;' : '&darr;';
    th.appendChild(caret);
  }

  table.querySelectorAll('th.sortable').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const key = th.dataset.sort;
      sortState.dir = (sortState.key === key && sortState.dir === 'asc') ? 'desc' : 'asc';
      sortState.key = key;

      table.querySelectorAll('th.sortable').forEach(other => setSortIndicator(other, false));
      setSortIndicator(th, true, sortState.dir);

      applySort(sortState.key, sortState.dir);
    });
  });
  // default sort
  const defaultTh = table.querySelector('th[data-sort="id"]');
  if (defaultTh) setSortIndicator(defaultTh, true, 'asc');

  // DYNAMIC STATS
  function updateStats() {
    const items = rows.filter(r => !r.classList.contains('d-none')).map(r => ({
      status: (r.dataset.status || '').toLowerCase(),
      skills: Number(r.dataset.skills || 0)
    }));
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };

    setTxt('totalTracksCount', items.length);
    setTxt('activeTracksCount', items.filter(i => i.status === 'public' || i.status === 'active').length);
    setTxt('draftTracksCount', items.filter(i => i.status === 'draft').length);
    setTxt('totalSkillsCount', items.reduce((s, i) => s + (i.skills || 0), 0));
  }

  // INITIAL RENDER
  applyFilters();
  applySort(sortState.key, sortState.dir);

  // INLINE EDITING
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  async function patchTrack(trackId, payload) {
    const res = await fetch(`/admin/tracks/${trackId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error('Update failed');
    return await res.json();
  }

  function makeInput(initial, type, options) {
    if (type === 'select') {
      const sel = document.createElement('select');
      sel.className = 'form-select form-select-sm';
      (options || []).forEach(opt => {
        const o = document.createElement('option');
        o.value = opt;
        o.textContent = opt;
        if ((initial || '').toLowerCase() === String(opt).toLowerCase()) o.selected = true;
        sel.appendChild(o);
      });
      return sel;
    }
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'form-control form-control-sm';
    inp.value = initial || '';
    return inp;
  }

  function startEdit(td) {
    const tr = td.closest('tr');
    const trackId = tr.dataset.id;
    const field   = td.dataset.field;
    const edit    = td.dataset.edit || 'text';
    const opts    = td.dataset.options ? JSON.parse(td.dataset.options) : null;

    if (!trackId || !field) return;

    const currentText = td.innerText.trim();
    td.dataset.originalHtml = td.innerHTML;

    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-center gap-2';
    const input = makeInput(currentText, edit, opts);

    const okBtn = document.createElement('button');
    okBtn.className = 'btn btn-sm btn-primary';
    okBtn.innerHTML = '<i class="fas fa-check"></i>';

    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-sm btn-outline-secondary';
    cancelBtn.innerHTML = '<i class="fas fa-times"></i>';

    wrapper.appendChild(input);
    wrapper.appendChild(okBtn);
    wrapper.appendChild(cancelBtn);

    td.innerHTML = '';
    td.appendChild(wrapper);
    input.focus();
    if (input.select) input.select();

    const restore = () => {
      td.innerHTML = td.dataset.originalHtml;
      delete td.dataset.originalHtml;
    };

    cancelBtn.addEventListener('click', restore);
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') restore();
      if (e.key === 'Enter' && edit === 'text') okBtn.click();
    });

    okBtn.addEventListener('click', async () => {
      const newValue = edit === 'select' ? input.value : input.value.trim();
      const payload = { field, value: newValue };

      okBtn.disabled = true;
      okBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

      try {
        const data = await patchTrack(trackId, payload);

        if (field === 'track') {
          td.innerHTML = `<h6 class="mb-0 track-name searchable">${newValue}</h6>`;
          const m = modelCache.get(tr);
          m.track = newValue.toLowerCase();
          m.textBlob = (m.track + ' ' + (tr.dataset.description || '')).toLowerCase();
        } else if (field === 'description') {
          const display = newValue.length > 160 ? newValue.slice(0,160) + '...' : (newValue || 'No description');
          td.innerHTML = `<small class="text-muted track-desc searchable">${display}</small>`;
          tr.dataset.description = (newValue || '').toLowerCase();
          const m = modelCache.get(tr);
          m.description = tr.dataset.description;
          m.textBlob = (m.track + ' ' + m.description).toLowerCase();
        } else if (field === 'level_description') {
          td.innerHTML = newValue ? `<span class="badge bg-info">${newValue}</span>` : '<span class="text-muted">No level assigned</span>';
          tr.dataset.level = (newValue || '').toLowerCase();
          modelCache.get(tr).level = tr.dataset.level;
        } else if (field === 'status') {
          const s = (newValue || 'Unknown');
          const sLower = s.toLowerCase();
          const map = {
            'public': { cls: 'success', icon: 'globe' },
            'draft': { cls: 'warning', icon: 'edit' },
            'only me': { cls: 'info', icon: 'lock' },
            'restricted': { cls: 'secondary', icon: 'ban' },
            'unknown': { cls: 'secondary', icon: 'question' }
          };
          const meta = map[sLower] || map['unknown'];
          td.innerHTML = `<span class="badge bg-${meta.cls}"><i class="fas fa-${meta.icon} me-1"></i>${s}</span>`;
          tr.dataset.status = sLower;
          modelCache.get(tr).status = sLower;
          updateStats();
        } else {
          td.textContent = newValue;
        }

        AdminToast?.show?.(data.message || 'Saved', 'success');
        applyFilters();
        applySort(sortState.key, sortState.dir);
      } catch (e) {
        AdminToast?.show?.('Save failed. Please try again.', 'error');
        restore();
      }
    });
  }

  // Activate editing on double-click
  tbody.addEventListener('dblclick', (e) => {
    const td = e.target.closest('td[data-editable="true"]');
    if (td) startEdit(td);
  });

})();

// ===== Existing helpers (unchanged) =====
window.adminConfig = {
  filters: [
    {id: 'statusFilter', key: 'status', type: 'select'},
    {id: 'levelFilter',  key: 'level',  type: 'select'},
    {id: 'searchInput',  key: 'search', type: 'search'}
  ],
  searchFields: ['name', 'description'],
  highlightSelectors: ['.track-name', '.track-desc'],
  dynamicStats: true,
  stats: [
    { id: 'totalTracksCount',  calculator: items => items.length },
    { id: 'activeTracksCount', calculator: items => items.filter(item => item.status === 'active').length },
    { id: 'draftTracksCount',  calculator: items => items.filter(item => item.status === 'draft').length },
    { id: 'totalSkillsCount',  calculator: items => items.reduce((sum, item) => sum + parseInt(item.skills || 0), 0) }
  ]
};

function copyTrack(trackId) {
  const row = document.querySelector(`tr[data-id="${trackId}"]`);
  const trackName = row?.querySelector('.track-name')?.textContent || '';
  const skillCount = row?.dataset.skills || 0;

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
  const btn = document.querySelector('.modal-footer .btn-primary');

  adminAjax?.setLoadingState?.(btn, true);
  fetch(`/admin/tracks/${trackId}/duplicate`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ copy_skills: copySkills })
  })
  .then(r => r.json())
  .then(data => {
    if (!data || data.success !== true) throw new Error(data?.message || 'Error copying track');
    AdminToast.show(data.message || 'Track copied successfully', 'success');
    AdminModals.hide('copyTrackModal');
    if (data.redirect)      window.location.assign(data.redirect);
    else if (data.track_id) window.location.assign(`/admin/tracks/${data.track_id}`);
    else                    window.location.assign('/admin/tracks');
  })
  .catch(err => AdminToast.show(err.message || 'Network error occurred', 'error'))
  .finally(() => adminAjax?.setLoadingState?.(btn, false));
}

function deleteTrack(trackId) {
  const row = document.querySelector(`tr[data-id="${trackId}"]`);
  const trackName = row?.querySelector('.track-name')?.textContent || '';
  if (!confirm(`Delete "${trackName}"? This action cannot be undone.`)) return;

  fetch(`/admin/tracks/${trackId}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json'
    }
  })
  .then(response => {
    if (response.status === 409) {
      return response.json().then(data => {
        if (data.requires_confirmation) {
          if (confirm(data.message + '\n\nClick OK to remove dependencies and delete the track.')) {
            deleteTrackWithDependencies(trackId);
          }
        } else {
          AdminToast.show(data.message || 'Cannot delete track, it has dependencies', 'error');
        }
      });
    } else if (response.ok) {
      return response.json().then(() => {
        AdminToast.show('Track deleted successfully', 'success');
        row?.remove();
      });
    } else {
      throw new Error('Delete failed');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    AdminToast.show('Error deleting track', 'error');
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
    body: JSON.stringify({ force: true, remove_dependencies: true })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      AdminToast.show('Track and dependencies deleted successfully', 'success');
      document.querySelector(`tr[data-id="${trackId}"]`)?.remove();
    } else {
      AdminToast.show(data.message || 'Error deleting track', 'error');
    }
  })
  .catch(e => {
    console.error('Error:', e);
    AdminToast.show('Error deleting track', 'error');
  });
}

function showImportModal() { AdminToast.show('Import functionality coming soon', 'info'); }
function exportTracks()    { window.location.href = '/admin/tracks/export'; }
function showBulkOperations(){ AdminToast.show('Bulk operations functionality coming soon', 'info'); }
</script>
@endpush
