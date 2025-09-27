@extends('layouts.admin')

@section('title', 'Questions Management')

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    @include('admin.components.page-header', [
        'title' => isset($skill) ? 'Questions for: ' . $skill->skill : 'Questions Management',
        'subtitle' => isset($skill) ? 'Manage questions for this specific skill' : 'Manage all questions in the system',
        'breadcrumbs' => [
            ['title' => 'Dashboard', 'url' => url('/admin')],
            ['title' => 'Questions']
        ],
        'actions' => [
            [
                'text' => 'Create New Question',
                'url' => route('admin.questions.create') . (isset($skill) ? '?skill_id=' . $skill->id : ''),
                'icon' => 'plus',
                'class' => 'primary'
            ],
            [
                'type' => 'dropdown',
                'class' => 'secondary',
                'icon' => 'ellipsis-v',
                'text' => 'Actions',
                'items' => [
                    ['icon' => 'download', 'text' => 'Export Questions', 'onclick' => 'exportQuestions()'],
                    ['icon' => 'upload',   'text' => 'Import Questions', 'onclick' => 'importQuestions()'],
                    ['icon' => 'copy',     'text' => 'Bulk Duplicate Selected', 'onclick' => 'bulkDuplicate()', 'id' => 'dropdownBulkDuplicate'],
                    ['icon' => 'trash',    'text' => 'Bulk Delete Selected',    'onclick' => 'bulkDelete()',     'id' => 'dropdownBulkDelete'],
                    ['icon' => 'sync',     'text' => 'Refresh', 'onclick' => 'refreshData()'],
                ]
            ],
        ]
    ])

    {{-- Stats --}}
    @include('admin.components.stats-row', [
        'stats' => [
            ['value' => 'Loading...', 'label' => 'Total Questions',  'color' => 'primary', 'icon' => 'question-circle', 'id' => 'totalQuestionsCount'],
            ['value' => '0',          'label' => 'Approved',         'color' => 'success', 'icon' => 'check-circle',     'id' => 'approvedCount'],
            ['value' => '0',          'label' => 'Pending Review',   'color' => 'warning', 'icon' => 'clock',            'id' => 'pendingCount'],
            ['value' => '0',          'label' => 'Flagged',          'color' => 'danger',  'icon' => 'flag',             'id' => 'flaggedCount'],
        ]
    ])

    {{-- Filters --}}
    @component('admin.components.filters-card', ['items' => []])
        <div class="col-md-2">
            <select class="form-select" id="fieldFilter" data-populate="fields">
                <option value="">All Fields</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="skillFilter" data-populate="skills">
                <option value="">All Skills</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="difficultyFilter" data-populate="difficulties">
                <option value="">All Difficulties</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="statusFilter" data-populate="statuses" name="status_id">
                <option value="">All Status</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="qaStatusFilter" data-populate="qa-statuses">
                <option value="">All QA Status</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="typeFilter" data-populate="types">
                <option value="">All Types</option>
            </select>
        </div>
        <div class="col-md-4">
            <input type="search" class="form-control" id="searchInput" placeholder="Search questions...">
        </div>
    @endcomponent

    {{-- Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-question-circle me-2"></i>Questions List
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                                <label class="form-check-label" for="selectAll"><small>Select All</small></label>
                            </div>
                            <div class="form-check d-none">
                                <input type="checkbox" class="form-check-input" id="selectAllHeader"> {{-- optional header checkbox --}}
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
                        <table id = "question-table" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                        </div>
                                    </th>
                                    <th>Question Details</th>
                                    <th>Skill & Difficulty</th>
                                    <th>QA Status</th>
                                    <th>Source</th>
                                    <th>Author</th>
                                    <th width="150" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="questionsTableBody">
                                @include('admin.questions.table-body', ['questions' => $questions])
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
                        <nav aria-label="Questions pagination">
                            <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
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
let currentPage = 1;
let totalPages  = 1;
let totalRecords = 0;

document.addEventListener('DOMContentLoaded', () => {
  loadQuestions();
  setupFilters();

  // Body checkboxes -> bulk buttons
  document.addEventListener('change', e => {
    if (e.target.classList.contains('question-checkbox')) updateBulkButtons();
  });

  // Select-all handling
  document.getElementById('selectAll')?.addEventListener('change', toggleSelectAll);
  document.getElementById('selectAllHeader')?.addEventListener('change', e => {
    const master = document.getElementById('selectAll');
    if (master) { master.checked = e.target.checked; }
    toggleSelectAll();
  });

  // Optional: populate dropdowns if you have a global helper
  if (typeof populateGlobalDropdowns === 'function') {
    setTimeout(populateGlobalDropdowns, 100);
  }
});

function loadQuestions(page = 1) {
  const tbody = document.getElementById('questionsTableBody');
  tbody.innerHTML = loadingRow();

  const url = new URL(window.location.href);
  url.searchParams.set('page', page);
  const filters = getFilters();
  for (const [k,v] of Object.entries(filters)) v ? url.searchParams.set(k,v) : url.searchParams.delete(k);

  fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }})
    .then(res => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.json(); })
    .then(data => {
      tbody.innerHTML = data.html;               // server-rendered rows
      currentPage     = data.current_page || page;
      totalPages      = data.num_pages || 1;
      totalRecords    = data.total ?? 0;
hydrateFiltersOnce(data);
      updatePagination();
      updateStats(data.totals);
      updateBulkButtons();

      history.replaceState(null, '', url.toString());
    })
    .catch(err => { console.error(err); tbody.innerHTML = errorRow(); });
}

function getFilters() {
  const v = id => document.getElementById(id)?.value || '';
  return {
    field_id:      v('fieldFilter'),
    skill_id:      v('skillFilter'),
    difficulty_id: v('difficultyFilter'),
    status_id:     v('statusFilter'),
    qa_status:     v('qaStatusFilter'),
    type_id:       v('typeFilter'),
    author_id:     v('authorFilter'),
    source:        v('sourceFilter'),
    search:        v('searchInput'),
  };
}

function setupFilters() {
  const ids = ['fieldFilter','skillFilter','difficultyFilter','statusFilter','qaStatusFilter','typeFilter','authorFilter','sourceFilter'];
  ids.forEach(id => document.getElementById(id)?.addEventListener('change', () => loadQuestions(1)));
  const search = document.getElementById('searchInput');
  if (search) {
    let t; search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => loadQuestions(1), 500); });
  }
}

function updatePagination() {
  const ul = document.getElementById('pagination');
  if (!ul) return;

  const items = [];
  const li = (cls, html) => `<li class="page-item ${cls}">${html}</li>`;
  const a  = (p, label) => `<a class="page-link" href="#" onclick="loadQuestions(${p});return false;">${label}</a>`;
  const span = label => `<span class="page-link">${label}</span>`;

  // Prev
  items.push(li(currentPage===1?'disabled':'',
    currentPage===1 ? span('<i class="fas fa-chevron-left"></i> Previous') : a(currentPage-1,'<i class="fas fa-chevron-left"></i> Previous')
  ));

  // Windowed page numbers
  const start = Math.max(1, currentPage-2);
  const end   = Math.min(totalPages, currentPage+2);

  if (start>1) { items.push(li('', a(1,'1'))); if (start>2) items.push(li('disabled', span('...'))); }
  for (let i=start;i<=end;i++) items.push(li(i===currentPage?'active':'', a(i,String(i))));
  if (end<totalPages) { if (end<totalPages-1) items.push(li('disabled', span('...'))); items.push(li('', a(totalPages, String(totalPages)))); }

  // Next
  items.push(li(currentPage===totalPages?'disabled':'',
    currentPage===totalPages ? span('Next <i class="fas fa-chevron-right"></i>') : a(currentPage+1,'Next <i class="fas fa-chevron-right"></i>')
  ));

  ul.innerHTML = items.join('');

  // "Showing x–y of z"
  const perPage = 50; // matches your paginate(50)
  const startIdx = totalRecords ? (currentPage-1)*perPage + 1 : 0;
  const endIdx   = totalRecords ? Math.min(currentPage*perPage, totalRecords) : 0;
  document.getElementById('showing-start').textContent  = startIdx;
  document.getElementById('showing-end').textContent    = endIdx;
  document.getElementById('total-records').textContent  = totalRecords;
}

function updateStats(totals) {
  if (!totals) return;
  document.getElementById('totalQuestionsCount').textContent = totals.total ?? 0;
  document.getElementById('approvedCount').textContent      = totals.approved ?? 0;
  document.getElementById('pendingCount').textContent       = totals.pending ?? 0;
  document.getElementById('flaggedCount').textContent       = totals.flagged ?? 0;
}

function toggleSelectAll() {
  const master = document.getElementById('selectAll');
  document.querySelectorAll('.question-checkbox').forEach(cb => cb.checked = !!master.checked);
  updateBulkButtons();
}
function updateBulkButtons() {
  const has = document.querySelectorAll('.question-checkbox:checked').length > 0;
  const set = (id, d) => { const el = document.getElementById(id); if (el) el.disabled = d; };
  set('bulkDeleteBtn', !has);
  set('bulkDuplicateBtn', !has);
}

function getSelectedIds() {
  return Array.from(document.querySelectorAll('.question-checkbox:checked')).map(cb => cb.value);
}

function bulkDuplicate() {
  const ids = getSelectedIds();
  if (!ids.length) return showToast('Please select questions to duplicate','warning');
  if (!confirm(`Duplicate ${ids.length} selected questions?`)) return;

  const btn = document.getElementById('bulkDuplicateBtn');
  const old = btn.innerHTML; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Duplicating...'; btn.disabled = true;

  fetch('/admin/questions/bulk-duplicate', {
    method:'POST',
    headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type':'application/json','Accept':'application/json' },
    body: JSON.stringify({ question_ids: ids })
  }).then(r=>r.json()).then(d=>{
    d.success ? showToast(`${ids.length} questions duplicated!`,'success') : showToast(d.message||'Error duplicating','error');
    document.getElementById('selectAll').checked = false;
    loadQuestions(currentPage);
  }).catch(()=>showToast('Error duplicating questions','error'))
    .finally(()=>{ btn.innerHTML = old; updateBulkButtons(); });
}

function bulkDelete() {
  const ids = getSelectedIds();
  if (!ids.length) return showToast('Please select questions to delete','warning');
  if (!confirm(`Delete ${ids.length} selected questions? This cannot be undone.`)) return;

  fetch('/admin/questions/bulk-delete', {
    method:'POST',
    headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type':'application/json','Accept':'application/json' },
    body: JSON.stringify({ question_ids: ids })
  }).then(r=>r.json()).then(d=>{
    d.success ? showToast('Selected questions deleted!','success') : showToast(d.message||'Error deleting','error');
    document.getElementById('selectAll').checked = false;
    loadQuestions(currentPage);
  }).catch(()=>showToast('Error deleting questions','error'));
}

function viewQuestion(id){ window.location.href = `/admin/questions/${id}`; }
function copyQuestion(id){
  if (!confirm('Duplicate this question?')) return;
  fetch(`/admin/questions/${id}/duplicate`, {
    method:'POST', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' }
  }).then(r=>r.json()).then(d=>{
    if (d.success) { showToast('Question duplicated!','success'); d.redirect_url ? location.href=d.redirect_url : loadQuestions(currentPage); }
    else showToast(d.message||'Error duplicating question','error');
  }).catch(()=>showToast('Error duplicating question','error'));
}
function deleteQuestion(id){
  if (!confirm('Delete this question? This cannot be undone.')) return;
  fetch(`/admin/questions/${id}`, {
    method:'DELETE', headers:{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept':'application/json' }
  }).then(r=> r.ok ? (showToast('Question deleted!','success'), loadQuestions(currentPage)) : r.json().then(d=>showToast(d.message||'Error deleting','error')))
   .catch(()=>showToast('Error deleting question','error'));
}

function loadingRow(){ return `<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2">Loading questions...</div></td></tr>`; }
function errorRow(){ return `<tr><td colspan="7" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading questions. Please refresh.</td></tr>`; }
function showToast(msg,type='info'){ window.showToast ? window.showToast(msg,type) : alert(`${type.toUpperCase()}: ${msg}`); }

function exportQuestions(){ window.location.href = '/admin/questions/export'; }
function importQuestions(){ showToast('Import functionality coming soon','info'); }
function refreshData(){ loadQuestions(currentPage); }
// --- helpers to normalize and fill selects ---
function populateSelect(selectId, items, { valueKey = 'id', labelKey = 'text' } = {}) {
  const sel = document.getElementById(selectId);
  if (!sel || !Array.isArray(items)) return;

  // keep the first option (e.g., "All ...")
  const first = sel.querySelector('option')?.outerHTML || '<option value=""></option>';
  sel.innerHTML = first + items.map(it => {
    // item can be a primitive, object with value/text, or object with custom keys
    if (typeof it === 'string' || typeof it === 'number') {
      return `<option value="${it}">${String(it)}</option>`;
    }
    const v = it.value ?? it[valueKey] ?? '';
    const l = it.text ?? it[labelKey] ?? v;
    return `<option value="${v}">${l}</option>`;
  }).join('');
}

function populateFromQaStatuses(selectId, qa) {
  const sel = document.getElementById(selectId);
  if (!sel || !qa) return;

  const first = sel.querySelector('option')?.outerHTML || '<option value=""></option>';
  let options = '';

  // qa may be: object map {approved:"Approved",...} OR array ["approved","flagged",...]
  if (Array.isArray(qa)) {
    options = qa.map(v => `<option value="${v}">${titleize(v)}</option>`).join('');
  } else if (typeof qa === 'object') {
    options = Object.entries(qa).map(([v,l]) => `<option value="${v}">${l || titleize(v)}</option>`).join('');
  }
  sel.innerHTML = first + options;

  function titleize(s){ return String(s).replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()); }
}

// Map data-populate attribute -> data key (handles hyphen/underscore)
const POPULATE_KEY_MAP = {
  'fields': 'fields',
  'skills': 'skills',
  'difficulties': 'difficulties',
  'statuses': 'statuses',
  'types': 'types',
  'qa-statuses': 'qa_statuses'
};

function populateFiltersFromData(data) {
  if (!data) return;

  // Fields
  if (data.fields?.length) {
    populateSelect('fieldFilter', data.fields, { valueKey: 'id', labelKey: 'field' });
  }
  // Skills
  if (data.skills?.length) {
    populateSelect('skillFilter', data.skills, { valueKey: 'id', labelKey: 'skill' });
  }
  // Difficulties (can be empty in your snapshot, that’s okay)
  if (Array.isArray(data.difficulties)) {
    // server shape (getDropdownOptions) is [{value, text}], but your localStorage snapshot shows []
    const arr = data.difficulties.map(d => d.value ? d : { id: d.id, text: d.short_description || d.text || d.label || 'Unknown' });
    populateSelect('difficultyFilter', arr, { valueKey: 'value', labelKey: 'text' });
  }
  // Statuses (public/draft/etc)
  if (data.statuses?.length) {
    const arr = data.statuses.map(s => ({ value: s.id ?? s.value, text: s.status ?? s.text }));
    populateSelect('statusFilter', arr, { valueKey: 'value', labelKey: 'text' });
  }
  // Types
  if (data.types?.length) {
    const arr = data.types.map(t => ({ value: t.id ?? t.value, text: t.type ?? t.text }));
    populateSelect('typeFilter', arr, { valueKey: 'value', labelKey: 'text' });
  }
  // QA statuses
  if (data.qa_statuses) {
    populateFromQaStatuses('qaStatusFilter', data.qa_statuses);
  }
}

// Try server-provided options first (from index() JSON), else localStorage
function hydrateFiltersOnce(dataFromServer) {
  // avoid repopulating if already filled (keeps user selection)
  const already = document.getElementById('skillFilter')?.options?.length > 1;
  if (already) return;

  if (dataFromServer && dataFromServer.filter_options) {
    // Your controller sends { qa_statuses, skills, authors, sources }
    const fo = dataFromServer.filter_options;

    // Normalize to the shapes our helfpers expect
    const normalized = {
      qa_statuses: fo.qa_statuses, // array like ['approved','flagged',...]
      skills: (fo.skills || []).map(s => ({ id: s.id, skill: s.skill })),
      // difficulties/types/statuses are not in filter_options here; we may fill from localStorage below
    };

    populateFiltersFromData(normalized);
  }

  // Fallback to localStorage snapshot the app saved earlier
  try {
    // Adjust the key to whatever you actually used; guessing 'filtersCache' or similar
    const raw = localStorage.getItem('filtersCache') || localStorage.getItem('metaFilters') || localStorage.getItem('filters');
    if (raw) {
      const parsed = JSON.parse(raw);
      // Your snapshot shows the object under { data: { ... } }
      const payload = parsed.data || parsed;
      populateFiltersFromData(payload);
    }
  } catch (e) {
    console.warn('Failed to load filters from localStorage', e);
  }
}

</script>
@endpush
