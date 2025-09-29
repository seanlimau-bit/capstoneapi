@extends('layouts.admin')
@section('title','Questions Management')

@push('styles')
  {{-- KaTeX --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --}}
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
            'onclick' => 'openCreateQuestionModal()',
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
                  ['icon' => 'magic',    'text' => 'Bulk Generate Selected', 'onclick' => 'openGenerateModalForSelected()', 'id' => 'dropdownBulkGenerate'],
                  ['icon' => 'trash',    'text' => 'Bulk Delete Selected', 'onclick' => 'bulkDelete()', 'id' => 'dropdownBulkDelete'],
                  ['icon' => 'sync',     'text' => 'Refresh', 'onclick' => 'refreshData()'],
              ]
          ],
      ]
  ])

  {{-- Stats --}}
  @include('admin.components.stats-row', [
      'stats' => [
          ['value' => $totals['total'] ?? 0, 'label' => 'Total Questions', 'color' => 'primary', 'icon' => 'question-circle', 'id' => 'totalQuestionsCount'],
          ['value' => $totals['approved'] ?? 0, 'label' => 'Approved', 'color' => 'success', 'icon' => 'check-circle', 'id' => 'approvedCount'],
          ['value' => $totals['pending'] ?? 0, 'label' => 'Pending Review', 'color' => 'warning', 'icon' => 'clock', 'id' => 'pendingCount'],
          ['value' => $totals['flagged'] ?? 0, 'label' => 'Flagged', 'color' => 'danger', 'icon' => 'flag', 'id' => 'flaggedCount'],
      ]
  ])

  {{-- Filters --}}
  @component('admin.components.filters-card', ['items' => []])
    <div class="col-md-2">
      <select class="form-select" id="fieldFilter">
        <option value="">All Fields</option>
        @foreach(($filterOptions['fields'] ?? []) as $f)
          <option value="{{ $f['id'] ?? $f->id }}">{{ $f['field'] ?? $f->field }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="skillFilter">
        <option value="">All Skills</option>
        @foreach(($filterOptions['skills'] ?? []) as $s)
          <option value="{{ $s->id }}">{{ $s->skill }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="difficultyFilter">
        <option value="">All Difficulties</option>
        @foreach(($filterOptions['difficulties'] ?? []) as $d)
          <option value="{{ $d['id'] ?? $d->id }}">{{ $d['text'] ?? ($d->short_description ?? '') }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="statusFilter" name="status_id">
        <option value="">All Status</option>
        @foreach(($filterOptions['statuses'] ?? []) as $st)
          <option value="{{ $st['id'] ?? $st->id }}">{{ $st['text'] ?? ($st->status ?? '') }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="qaStatusFilter">
        <option value="">All QA Status</option>
        @php $qas = $filterOptions['qa_statuses'] ?? []; @endphp
        @if(is_array($qas))
          @foreach($qas as $key => $val)
            @if(is_string($key))
              <option value="{{ $key }}">{{ $val ?: ucfirst(str_replace('_',' ', $key)) }}</option>
            @else
              <option value="{{ $val }}">{{ ucfirst(str_replace('_',' ', $val)) }}</option>
            @endif
          @endforeach
        @endif
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="typeFilter">
        <option value="">All Types</option>
        @foreach(($filterOptions['types'] ?? []) as $t)
          <option value="{{ $t['id'] ?? $t->id }}">{{ $t['text'] ?? ($t->type ?? '') }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <select class="form-select" id="sourceFilter">
        <option value="">All Sources</option>
        @foreach(($filterOptions['sources'] ?? []) as $src)
          <option value="{{ $src }}">{{ $src }}</option>
        @endforeach
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
            <h5 class="card-title mb-0"><i class="fas fa-question-circle me-2"></i>Questions List</h5>
            <div class="d-flex align-items-center gap-3">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="selectAll">
                <label class="form-check-label" for="selectAll"><small>Select All</small></label>
              </div>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary" onclick="openGenerateModalForSelected()" disabled id="bulkGenerateBtn">
                  <i class="fas fa-magic me-1"></i>Generate Selected
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
            <table id="question-table" class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th width="50">
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="selectAllHeader"></div>
                  </th>
                  <th>Question Details</th>
                  <th>Skill & Difficulty</th>
                  <th>Status</th>
                  <th>QA Status</th>
                  <th>Source</th>
                  <th class="text-center" style="width: 1%;">Actions</th>
                </tr>
              </thead>
              <tbody id="questionsTableBody">
                @include('admin.questions.table-body', ['questions' => $questions])
              </tbody>
            </table>
          </div>
        </div>

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

{{-- Create Question Modal --}}
<div class="modal fade" id="createQuestionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" id="createQuestionForm">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Question</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert d-none" id="cq-alert" role="alert"></div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Skill (optional)</label>
            <select class="form-select" name="skill_id" id="cq-skill">
              <option value="">— Select Skill —</option>
              @foreach(($filterOptions['skills'] ?? []) as $s)
                <option value="{{ $s->id }}">{{ $s->skill }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select class="form-select" name="type_id" id="cq-type" required>
              @foreach(($filterOptions['types'] ?? []) as $t)
                @php $tid = $t['id'] ?? $t->id; $ttext = $t['text'] ?? ($t->type ?? "Type #$tid"); @endphp
                <option value="{{ $tid }}">{{ $ttext }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Question <span class="text-danger">*</span></label>
            <textarea class="form-control" name="question" id="cq-question" rows="3" required></textarea>
            <div id="cq-blank-hint" class="form-text d-none">
              For fill-in-the-blank: insert a blank like <code>[[blank]]</code> or <code>____</code>.
              <button type="button" class="btn btn-link p-0 ms-1" id="cq-insert-blank">Insert [[blank]]</button>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Answer 0 <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="answer0" id="cq-answer0" required>
          </div>
          <div class="col-md-6" id="grp-answer1">
            <label class="form-label">Answer 1 <span class="text-danger" id="req-answer1" style="display:none">*</span></label>
            <input type="text" class="form-control" name="answer1" id="cq-answer1">
          </div>

          <div class="col-md-6" id="grp-answer2">
            <label class="form-label">Answer 2</label>
            <input type="text" class="form-control" name="answer2" id="cq-answer2">
          </div>
          <div class="col-md-6" id="grp-answer3">
            <label class="form-label">Answer 3</label>
            <input type="text" class="form-control" name="answer3" id="cq-answer3">
          </div>

          <div class="col-md-6" id="grp-correct">
            <label class="form-label">Correct (index)</label>
            <select class="form-select" name="correct_answer" id="cq-correct">
              <option value="">— Choose —</option>
              <option value="0">Answer 0</option>
              <option value="1">Answer 1</option>
              <option value="2">Answer 2</option>
              <option value="3">Answer 3</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Difficulty</label>
            <select class="form-select" name="difficulty_id" id="cq-difficulty">
              <option value="">—</option>
              @foreach(($filterOptions['difficulties'] ?? []) as $d)
                @php $did = $d['id'] ?? $d->id; $dtext = $d['text'] ?? ($d->short_description ?? "Difficulty #$did"); @endphp
                <option value="{{ $did }}">{{ $dtext }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select class="form-select" name="status_id" id="cq-status">
              @foreach(($filterOptions['statuses'] ?? []) as $st)
                @php $sid = $st['id'] ?? $st->id; $slabel = $st['text'] ?? ($st->status ?? "Status #$sid"); @endphp
                <option value="{{ $sid }}">{{ $slabel }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Source</label>
            <input type="text" class="form-control" name="source" id="cq-source">
          </div>

          <div class="col-12">
            <label class="form-label">Explanation (optional)</label>
            <textarea class="form-control" name="explanation" id="cq-explanation" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="cq-submit" type="submit">
          <i class="fas fa-save me-1"></i>Create
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Generate Questions Modal --}}
<div class="modal fade" id="genQModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="genQForm">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-magic me-2"></i>Generate Questions</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert d-none" id="genQ-alert" role="alert"></div>
        <input type="hidden" id="genQ-questionIds">
        <div class="mb-3">
          <label class="form-label">How many to generate?</label>
          <input type="number" class="form-control" id="genQ-count" min="1" max="50" value="10" required>
          <div class="form-text">Enter 1–50</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" type="submit" id="genQ-submit">
          <i class="fas fa-magic me-1"></i>Generate
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  {{-- KaTeX --}}
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>

  <script>
  // --- State
  let currentPage = 1, totalPages = 1, totalRecords = 0;
  let _createModal, _genQModal;

  document.addEventListener('DOMContentLoaded', () => {
    _createModal = new bootstrap.Modal(document.getElementById('createQuestionModal'));
    _genQModal   = new bootstrap.Modal(document.getElementById('genQModal'));

    loadQuestions();
    bindFilters();

    document.getElementById('selectAll')?.addEventListener('change', toggleSelectAll);
    document.getElementById('selectAllHeader')?.addEventListener('change', e => {
      const master = document.getElementById('selectAll'); if (master) master.checked = e.target.checked; toggleSelectAll();
    });

    // Delegate row checkbox changes
    document.addEventListener('change', e => { if (e.target.classList.contains('question-checkbox')) updateBulkButtons(); });

    // Forms
    document.getElementById('createQuestionForm')?.addEventListener('submit', submitCreate);
    document.getElementById('cq-type')?.addEventListener('change', onTypeChange);
    document.getElementById('cq-insert-blank')?.addEventListener('click', insertBlankToken);

    document.getElementById('genQForm')?.addEventListener('submit', submitGenerate);

    onTypeChange();
  });

  // --- Modal alert helpers
  function showModalMessage(elId, type, html) {
    const box = document.getElementById(elId);
    if (!box) return;
    box.className = `alert alert-${type}`;
    box.innerHTML = html;
    box.classList.remove('d-none');
  }
  function clearModalMessage(elId) {
    const box = document.getElementById(elId);
    if (!box) return;
    box.className = 'alert d-none';
    box.innerHTML = '';
  }

  // --- KaTeX render for Question column (2nd cell)
  function renderKatexInQuestions(){
    const tbody = document.getElementById('questionsTableBody');
    if (!tbody || typeof renderMathInElement !== 'function') return;
    const questionCells = tbody.querySelectorAll('tr td:nth-child(2)');
    questionCells.forEach(cell => {
      renderMathInElement(cell, {
        delimiters: [
          { left: "$$", right: "$$", display: true },
          { left: "\\[", right: "\\]", display: true },
          { left: "\\(", right: "\\)", display: false },
          { left: "$", right: "$", display: false }
        ],
        throwOnError: false
      });
    });
  }

  // --- Fetch and render
  function loadQuestions(page = 1) {
    const tbody = document.getElementById('questionsTableBody');
    tbody.innerHTML = loadingRow();

    const url = new URL("{{ route('admin.questions.index') }}", window.location.origin);
    url.searchParams.set('page', page);
    const filters = getFilters();
    Object.entries(filters).forEach(([k,v]) => v ? url.searchParams.set(k,v) : url.searchParams.delete(k));

    fetch(url, { headers: { 'Accept':'application/json','X-Requested-With':'XMLHttpRequest' }})
      .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
      .then(d => {
        tbody.innerHTML = d.html;
        sanitizeRows();

        currentPage   = d.current_page || page;
        totalPages    = d.num_pages || 1;
        totalRecords  = d.total ?? 0;

        hydrateFiltersOnce(d.filter_options);
        updateStats(d.totals);
        updatePagination();
        updateBulkButtons();

        history.replaceState(null, '', url.toString());
        renderKatexInQuestions();
      })
      .catch(err => { console.error(err); tbody.innerHTML = errorRow(); });
  }

  // --- Row sanitization (legacy compatibility)
  function sanitizeRows(){
    const table = document.getElementById('question-table');
    if (!table) return;
    const ths = Array.from(table.querySelectorAll('thead th'));
    const authorIdx = ths.findIndex(th => th.textContent.trim().toLowerCase() === 'author');
    if (authorIdx >= 0) {
      ths[authorIdx].remove();
      table.querySelectorAll('tbody tr').forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if (tds[authorIdx]) tds[authorIdx].remove();
      });
    }
    table.querySelectorAll('tbody tr').forEach(tr => {
      const id = getRowQuestionId(tr);
      if (!id) return;
      let btn = tr.querySelector('button.btn-duplicate, button[data-action="duplicate"], a.btn-duplicate');
      if (!btn) {
        btn = Array.from(tr.querySelectorAll('button, a.btn, a')).find(b => /\bduplicate\b/i.test(b.textContent || ''));
      }
      if (btn) {
        const gen = document.createElement('button');
        gen.type = 'button';
        gen.className = 'btn btn-sm btn-outline-primary btn-generate-row btn-icon-only';
        gen.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i>';
        gen.title = 'Generate';
        gen.addEventListener('click', () => openGenerateModal(id));
        gen.title = 'Generate';
        btn.replaceWith(gen);
      } 
    });
  }
  function getRowQuestionId(tr){
    const cb = tr.querySelector('.question-checkbox');
    if (cb && cb.value) return cb.value;
    const did = tr.getAttribute('data-id');
    if (did) return did;
    const link = tr.querySelector('a[href*="/admin/questions/"]');
    if (link) {
      const m = link.getAttribute('href').match(/\/admin\/questions\/(\d+)/);
      if (m) return m[1];
    }
    return null;
  }

  // --- Filters
  function getFilters() {
    const val = id => document.getElementById(id)?.value || '';
    return {
      field_id:      val('fieldFilter'),
      skill_id:      val('skillFilter'),
      difficulty_id: val('difficultyFilter'),
      status_id:     val('statusFilter'),
      qa_status:     val('qaStatusFilter'),
      type_id:       val('typeFilter'),
      source:        val('sourceFilter'),
      search:        val('searchInput'),
    };
  }
  function bindFilters() {
    const ids = ['fieldFilter','skillFilter','difficultyFilter','statusFilter','qaStatusFilter','typeFilter','sourceFilter'];
    ids.forEach(id => document.getElementById(id)?.addEventListener('change', () => loadQuestions(1)));
    const search = document.getElementById('searchInput');
    if (search) { let t; search.addEventListener('input', () => { clearTimeout(t); t=setTimeout(()=>loadQuestions(1), 450); }); }
  }
  let _filtersHydrated = false;
  function hydrateFiltersOnce(opts) {
    if (_filtersHydrated || !opts) return;
    if ((document.getElementById('skillFilter')?.options.length || 0) <= 1) {
      populateSelect('skillFilter', opts.skills, { valueKey:'id', labelKey:'skill' });
    }
    if ((document.getElementById('fieldFilter')?.options.length || 0) <= 1) {
      const fields = (opts.fields || []).map(f => ({ value: f.id ?? f['id'], text: f.field ?? f['field'] }));
      populateSelect('fieldFilter', fields);
    }
    if ((document.getElementById('difficultyFilter')?.options.length || 0) <= 1) {
      const diffs = (opts.difficulties || []).map(d => ({ value: d.id ?? d['id'], text: d.text ?? d['short_description'] ?? d['label'] ?? 'Difficulty' }));
      populateSelect('difficultyFilter', diffs);
    }
    if ((document.getElementById('statusFilter')?.options.length || 0) <= 1) {
      const statuses = (opts.statuses || []).map(s => ({ value: s.id ?? s['id'], text: s.text ?? s['status'] ?? `Status #${s.id}` }));
      populateSelect('statusFilter', statuses);
    }
    if ((document.getElementById('typeFilter')?.options.length || 0) <= 1) {
      const types = (opts.types || []).map(t => ({ value: t.id ?? t['id'], text: t.text ?? t['type'] ?? `Type #${t.id}` }));
      populateSelect('typeFilter', types);
    }
    if ((document.getElementById('sourceFilter')?.options.length || 0) <= 1) {
      const sources = (opts.sources || []).map(s => ({ value: s, text: s }));
      populateSelect('sourceFilter', sources);
    }
    _filtersHydrated = true;
  }
  function populateSelect(selectId, items, { valueKey='value', labelKey='text' } = {}) {
    const sel = document.getElementById(selectId);
    if (!sel || !Array.isArray(items)) return;
    const keep = sel.querySelector('option')?.outerHTML || '<option value=""></option>';
    sel.innerHTML = keep + items.map(it => {
      if (typeof it === 'string' || typeof it === 'number') return `<option value="${it}">${String(it)}</option>`;
      const v = it[valueKey]; const l = it[labelKey];
      return `<option value="${v}">${l}</option>`;
    }).join('');
  }

  // --- Pagination & stats
  function updatePagination() {
    const ul = document.getElementById('pagination'); if (!ul) return;
    const li = (cls, html) => `<li class="page-item ${cls}">${html}</li>`;
    const a  = (p, html) => `<a class="page-link" href="#" onclick="loadQuestions(${p});return false;">${html}</a>`;
    const span = html => `<span class="page-link">${html}</span>`;
    const items = [];
    items.push(li(currentPage===1?'disabled':'', currentPage===1?span('&laquo;'):a(currentPage-1,'&laquo;')));
    const s = Math.max(1, currentPage-2), e = Math.min(totalPages, currentPage+2);
    if (s>1){ items.push(li('', a(1,'1'))); if (s>2) items.push(li('disabled', span('...'))); }
    for (let i=s;i<=e;i++) items.push(li(i===currentPage?'active':'', a(i, String(i))));
    if (e<totalPages){ if (e<totalPages-1) items.push(li('disabled', span('...'))); items.push(li('', a(totalPages, String(totalPages)))); }
    items.push(li(currentPage===totalPages?'disabled':'', currentPage===totalPages?span('&raquo;'):a(currentPage+1,'&raquo;')));
    ul.innerHTML = items.join('');
    const perPage = 50;
    const startIdx = totalRecords ? (currentPage-1)*perPage + 1 : 0;
    const endIdx   = totalRecords ? Math.min(currentPage*perPage, totalRecords) : 0;
    document.getElementById('showing-start').textContent = startIdx;
    document.getElementById('showing-end').textContent   = endIdx;
    document.getElementById('total-records').textContent = totalRecords;
  }
  function updateStats(totals) {
    if (!totals) return;
    document.getElementById('totalQuestionsCount').textContent = totals.total ?? 0;
    document.getElementById('approvedCount').textContent       = totals.approved ?? 0;
    document.getElementById('pendingCount').textContent        = totals.pending ?? 0;
    document.getElementById('flaggedCount').textContent        = totals.flagged ?? 0;
  }

  // --- Selection / Bulk controls
  function toggleSelectAll(){ const m=document.getElementById('selectAll'); document.querySelectorAll('.question-checkbox').forEach(cb=>cb.checked=!!m.checked); updateBulkButtons(); }
  function updateBulkButtons(){
    const has=!!document.querySelectorAll('.question-checkbox:checked').length;
    ['bulkDeleteBtn','bulkGenerateBtn'].forEach(id=>{const el=document.getElementById(id); if(el) el.disabled=!has;});
  }
  function getSelectedIds(){ return Array.from(document.querySelectorAll('.question-checkbox:checked')).map(cb=>cb.value); }

  // --- Navigation actions
  function viewQuestion(id){ window.location.href = `/admin/questions/${id}`; }

  // --- Open modals (single definitions)
  function openCreateQuestionModal(){
    document.getElementById('createQuestionForm')?.reset();
    clearModalMessage('cq-alert');
    onTypeChange();
    _createModal.show();
  }
  function openGenerateModal(questionId){
    clearModalMessage('genQ-alert');
    document.getElementById('genQ-questionIds').value = String(questionId || '');
    document.getElementById('genQ-count').value = 10;
    _genQModal.show();
  }
  function openGenerateModalForSelected(){
    clearModalMessage('genQ-alert');
    const ids=getSelectedIds();
    if(!ids.length) return showToast('Please select questions to generate from','warning');
    document.getElementById('genQ-questionIds').value = ids.join(',');
    document.getElementById('genQ-count').value = 10;
    _genQModal.show();
  }

  // --- Generate submit
 function submitGenerate(e){
  e.preventDefault();
  const btn   = document.getElementById('genQ-submit');
  const alertId = 'genQ-alert';

  const idsStr = document.getElementById('genQ-questionIds').value.trim();
  const count  = parseInt(document.getElementById('genQ-count').value || '1', 10);
  if (!idsStr) return showModalMessage(alertId,'danger','Missing question id(s).');
  if (count < 1 || count > 50) return showModalMessage(alertId,'warning','Count must be between 1 and 50.');

  const ids = idsStr.split(',').map(s => s.trim()).filter(Boolean);
  const payload = ids.length === 1
    ? { question_id: ids[0], question_count: count }
    : { question_ids: ids,  question_count: count };

  btn.disabled = true; const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';

  fetch("{{ url('/admin/questions/generate') }}", {
    method:'POST',
    headers:{
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      'Content-Type':'application/json',
      'Accept':'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(async r => {
    // Read as text first so we can handle non-JSON responses safely
    const text = await r.text();
    let json;
    try { json = text ? JSON.parse(text) : {}; } catch { json = null; }

    // Treat payload truth as the source of truth
    const success = !!(json && json.success === true);
    if (success) {
      const created = json.questions_created ?? json.count_used ?? 0;
      const msg = json.message || `${created} question(s) generated.`;
      showModalMessage(alertId, 'success', msg);
      showToast(msg, 'success');
      setTimeout(() => { _genQModal.hide(); loadQuestions(1); }, 800);
      return;
    }

    // If not explicitly success, surface detailed validation if present
    if (json && json.errors) {
      const list = Object.entries(json.errors)
        .map(([k, arr]) => `<li><strong>${k}</strong>: ${arr.join(', ')}</li>`).join('');
      throw { status: r.status, message: `<div>Cannot generate:</div><ul class="mb-0">${list}</ul>`, html: true };
    }

    // Fallback: show raw text (useful if it was an HTML login redirect)
    throw { status: r.status, message: (json && json.message) || (text || 'Generation failed.') };
  })
  .catch(err => {
    const msg = err?.message || 'Generation failed.';
    showModalMessage('genQ-alert', 'danger', err?.html ? msg : escapeHtml(msg));
    console.error('Generate error:', err);
  })
  .finally(()=> { btn.disabled = false; btn.innerHTML = orig; });
}


  // --- Create modal logic
  function onTypeChange(){
    const typeId = parseInt(document.getElementById('cq-type')?.value || '1', 10);
    const isMcq   = typeId === 1;
    const isBlank = typeId === 2;

    toggleBlock('grp-answer1', isMcq);
    toggleBlock('grp-answer2', isMcq);
    toggleBlock('grp-answer3', isMcq);
    toggleBlock('grp-correct', isMcq);

    setRequired('cq-answer1', isMcq);
    const req = document.getElementById('req-answer1'); if (req) req.style.display = isMcq ? '' : 'none';
    setRequired('cq-correct', isMcq);

    const hint = document.getElementById('cq-blank-hint'); if (hint) hint.classList.toggle('d-none', !isBlank);
  }
  function toggleBlock(id, show){
    const el = document.getElementById(id); if(!el) return;
    el.classList.toggle('d-none', !show);
    el.querySelectorAll('input,select,textarea').forEach(i => i.disabled = !show);
  }
  function setRequired(id, yes){
    const el = document.getElementById(id); if(!el) return;
    if (yes) el.setAttribute('required','required'); else el.removeAttribute('required');
  }
  function insertBlankToken(){
    const ta = document.getElementById('cq-question'); if(!ta) return;
    const token = '[[blank]]';
    const start = ta.selectionStart ?? ta.value.length;
    const end   = ta.selectionEnd ?? ta.value.length;
    ta.value = ta.value.slice(0,start) + token + ta.value.slice(end);
    ta.focus();
    ta.setSelectionRange(start + token.length, start + token.length);
  }
function submitCreate(e){
  e.preventDefault();
  const form = e.currentTarget;
  const btn  = document.getElementById('cq-submit');
  const alertId = 'cq-alert';

  const data = new FormData(form);
  const typeId = parseInt(data.get('type_id') || '1', 10);

  if (typeId === 1) {
    if (!data.get('answer1')?.trim()) return showModalMessage(alertId,'warning','For MCQ, Answer 1 is required.');
    if (!data.get('correct_answer'))  return showModalMessage(alertId,'warning','For MCQ, please choose the correct answer index.');
  }
  if (typeId === 2) {
    const q = (data.get('question') || '').trim();
    if (!/\[\[blank\]\]|_{3,}/i.test(q)) return showModalMessage(alertId,'warning','For fill-in-the-blank, include [[blank]] or ____ in the question.');
    data.set('correct_answer','');
  }

  btn.disabled = true; const orig = btn.innerHTML;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

  fetch("{{ url('/admin/questions') }}", {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    body: data
  })
  .then(async r => {
    const text = await r.text();
    let json; try { json = text ? JSON.parse(text) : {}; } catch { json = null; }

    const success = !!(json && json.success === true);
    if (success) {
      const msg = json.message || 'Question created.';
      showModalMessage(alertId, 'success', msg);
      showToast(msg, 'success');
      setTimeout(() => { _createModal.hide(); loadQuestions(1); }, 800);
      return;
    }

    if (json && json.errors) {
      const list = Object.entries(json.errors)
        .map(([k, arr]) => `<li><strong>${k}</strong>: ${arr.join(', ')}</li>`).join('');
      throw { status: r.status, message: `<div>Fix the following:</div><ul class="mb-0">${list}</ul>`, html: true };
    }

    throw { status: r.status, message: (json && json.message) || (text || 'Create failed.') };
  })
  .catch(err => {
    const msg = err?.message || 'Create failed.';
    showModalMessage('cq-alert', 'danger', err?.html ? msg : escapeHtml(msg));
    console.error('Create error:', err);
  })
  .finally(() => { btn.disabled = false; btn.innerHTML = orig; });

}
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

// 1) Per-row delete (delegated). Works for any button/link with data-action="delete"
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-action="delete"]');
  if (!btn) return;
  e.preventDefault();

  const id = btn.dataset.id || btn.closest('tr')?.getAttribute('data-id');
  if (!id) return showToast('Could not determine question id.', 'error');
  if (!confirm('Delete this question?')) return;

  const orig = btn.innerHTML; btn.disabled = true; btn.innerHTML = '…';
  try {
    const r = await fetch(`/admin/questions/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
    });
    if (!r.ok) throw await r.json().catch(() => ({ message: 'Delete failed.' }));
    showToast('Question deleted.', 'success');
    loadQuestions(currentPage);
  } catch (err) {
    showToast(err?.message || 'Delete failed.', 'error');
  } finally {
    btn.disabled = false; btn.innerHTML = orig;
  }
});

// 2) Bulk delete for your toolbar button (calls /bulk-delete if present; else falls back)
async function bulkDelete(){
  const ids = getSelectedIds();
  if (!ids.length) return showToast('Select at least one question.', 'warning');
  if (!confirm(`Delete ${ids.length} selected question(s)?`)) return;

  // Try bulk endpoint
  try {
    const r = await fetch(`/admin/questions/bulk-delete`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json', 'Content-Type':'application/json' },
      body: JSON.stringify({ ids })
    });
    if (r.ok) {
      const j = await r.json().catch(() => ({}));
      if (j.success) {
        showToast(j.message || `Deleted ${j.deleted_count ?? ids.length} question(s).`, 'success');
        return loadQuestions(1);
      }
    }
  } catch { /* ignore and fall back */ }

  // Fallback: delete sequentially (short & sweet)
  let ok=0, fail=0;
  for (const id of ids) {
    try {
      const r = await fetch(`/admin/questions/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept':'application/json' }
      });
      r.ok ? ok++ : fail++;
    } catch { fail++; }
  }
  if (ok)  showToast(`Deleted ${ok} question(s).`, 'success');
  if (fail) showToast(`Failed to delete ${fail}.`, 'error');
  loadQuestions(1);
}
  // --- UX helpers
  function loadingRow(){ return `<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2">Loading questions...</div></td></tr>`; }
  function errorRow(){ return `<tr><td colspan="6" class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading questions. Please refresh.</td></tr>`; }
  function showToast(msg,type='info'){ window.showToast ? window.showToast(msg,type) : alert(`${type.toUpperCase()}: ${msg}`); }
  function exportQuestions(){ window.location.href = '/admin/questions/export'; }
  function importQuestions(){ showToast('Import functionality coming soon','info'); }
  function refreshData(){ loadQuestions(currentPage); }
  </script>
@endpush
