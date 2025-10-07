@extends('layouts.admin')

@section('title', 'QA Dashboard')

@php
use Illuminate\Support\Carbon;
$human = function ($d, $fallback = 'Unknown') {
    if (empty($d)) return $fallback;
    if ($d instanceof \DateTimeInterface) return $d->diffForHumans();
    try { return Carbon::parse($d)->diffForHumans(); } catch (\Throwable $e) { return $fallback; }
};
@endphp

@push('styles')
<style>
  .question-row { transition: background-color .15s ease; }
  .question-row:hover { background-color: rgba(0,0,0,.02); }
  .question-link { display:block; color:inherit; text-decoration:none; }
  .question-link:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
      <div>
        <h2 class="mb-1">QA Dashboard</h2>
        <p class="text-muted mb-0">Review and approve questions for publication</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
          <i class="fas fa-question-circle me-1"></i>QA Guidelines
        </button>
        <a href="{{ route('admin.qa.export') }}" class="btn btn-outline-primary">
          <i class="fas fa-download me-1"></i>Export Report
        </a>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" style="max-width:90%;">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="helpModalLabel">QA Reviewer Manual</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="height:80vh;">
            <iframe src="{{ asset('assets/QA_Reviewer_Manual.pdf') }}" width="100%" height="100%" style="border:none;"></iframe>
        </div>
    </div>
</div>
</div>
{{-- Statistics --}}
  <div class="row g-3 mb-4">
    {{-- Pending --}}
    <div class="col-md-3">
      <a class="text-decoration-none"
         href="{{ route('admin.qa.index', ['status' => 'unreviewed']) }}">
        <div class="card h-100"><div class="card-body text-center">
          <div class="display-6 text-warning">{{ $stats['pending'] ?? 0 }}</div>
          <div class="text-muted">Pending Review</div>
        </div></div>
      </a>
    </div>

    {{-- Flagged --}}
    <div class="col-md-3">
      <a class="text-decoration-none"
         href="{{ route('admin.qa.index', array_merge(request()->query(), ['status' => 'flagged'])) }}">
        <div class="card h-100"><div class="card-body text-center">
          <div class="display-6 text-danger">{{ $stats['flagged'] ?? 0 }}</div>
          <div class="text-muted">Flagged Issues</div>
        </div></div>
      </a>
    </div>

    {{-- Needs Revision --}}
    <div class="col-md-3">
      <a class="text-decoration-none"
         href="{{ route('admin.qa.index', array_merge(request()->query(), ['status' => 'needs_revision'])) }}">
        <div class="card h-100"><div class="card-body text-center">
          <div class="display-6 text-info">{{ $stats['needs_revision'] ?? 0 }}</div>
          <div class="text-muted">Needs Revision</div>
        </div></div>
      </a>
    </div>

    {{-- Approved Today --}}
    <div class="col-md-3">
      <a class="text-decoration-none"
         href="{{ route('admin.qa.index', array_merge(request()->query(), ['status' => 'approved', 'today' => 1])) }}">
        <div class="card h-100"><div class="card-body text-center">
          <div class="display-6 text-success">{{ $stats['approved'] ?? 0 }}</div>
          <div class="text-muted">Approved Today</div>
        </div></div>
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <form method="GET" action="{{ route('admin.qa.index') }}" id="filterForm" class="row g-2 align-items-end">
            {{-- Status --}}
            <div class="col-md-2">
              <label class="form-label small">Status</label>
              @php $status = request('status'); @endphp
              <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="unreviewed"     {{ $status === 'unreviewed'     ? 'selected' : '' }}>Unreviewed</option>
                <option value="flagged"        {{ $status === 'flagged'        ? 'selected' : '' }}>Flagged</option>
                <option value="needs_revision" {{ $status === 'needs_revision' ? 'selected' : '' }}>Needs Revision</option>
                <option value="approved"       {{ $status === 'approved'       ? 'selected' : '' }}>Approved</option>
              </select>
            </div>

            {{-- Type --}}
            <div class="col-md-2">
              <label class="form-label small">Type</label>
              @php $type = request('type'); @endphp
              <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="1" {{ $type === '1' ? 'selected' : '' }}>Multiple Choice</option>
                <option value="2" {{ $type === '2' ? 'selected' : '' }}>Fill in Blank</option>
              </select>
            </div>

            {{-- Skill --}}
            <div class="col-md-3">
              <label class="form-label small">Skill</label>
              @php $selectedSkill = request('skill_id', request('skill')); @endphp
              <select name="skill_id" class="form-select form-select-sm">
                <option value="">All Skills</option>
                @foreach(($skills ?? []) as $skill)
                  <option value="{{ $skill->id }}" {{ (string)$selectedSkill === (string)$skill->id ? 'selected' : '' }}>
                    {{ $skill->skill }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Level --}}
            <div class="col-md-2">
              <label class="form-label small">Level</label>
              @php $selectedLevel = request('level'); @endphp
              <select name="level" class="form-select form-select-sm">
                <option value="">All Levels</option>
                @foreach(($levels ?? []) as $lvl)
                  @php $label = $lvl->name ?? $lvl->description ?? $lvl->level; @endphp
                  <option value="{{ $lvl->id }}" {{ (string)$selectedLevel === (string)$lvl->id ? 'selected' : '' }}>
                    {{ is_numeric($label) ? 'Level '.$label : $label }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Assigned To --}}
            <div class="col-md-2">
              <label class="form-label small">Assigned To</label>
              @php $reviewer = request('reviewer'); @endphp
              <select name="reviewer" class="form-select form-select-sm">
                <option value="">All Reviewers</option>
                <option value="me"         {{ $reviewer === 'me' ? 'selected' : '' }}>Assigned to Me</option>
                <option value="unassigned" {{ $reviewer === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
              </select>
            </div>

            {{-- Sort --}}
            <div class="col-md-2">
              <label class="form-label small">Sort By</label>
              @php $sort = request('sort','created_at'); @endphp
              <select name="sort" class="form-select form-select-sm">
                <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>Date Created</option>
                <option value="updated_at" {{ $sort === 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                <option value="priority"   {{ $sort === 'priority'   ? 'selected' : '' }}>Priority</option>
              </select>
            </div>

            {{-- Reset --}}
            <div class="col-12 col-md-auto ms-md-auto">
              <a href="{{ route('admin.qa.index') }}" class="btn btn-link p-0 text-decoration-none">Reset</a>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Batch Actions --}}
  <div class="alert alert-primary d-none" id="batchActions">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <strong><span id="selectedCount">0</span> questions selected</strong>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-success btn-sm" id="approveSelected">
          <i class="fas fa-check me-1"></i>Approve Selected
        </button>
        <button type="button" class="btn btn-warning btn-sm" id="flagSelected">
          <i class="fas fa-flag me-1"></i>Flag Selected
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSelected">
          Clear Selection
        </button>
      </div>
    </div>
  </div>

  {{-- Questions Table --}}
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" id="qaTable">
          <thead class="table-light">
            <tr>
              <th width="40"><input type="checkbox" id="selectAll" class="form-check-input"></th>
              <th width="60">ID</th>
              <th>Question</th>
              <th width="120">Type</th>
              <th width="170">QA / Visibility</th>
              <th width="160">Skill</th>
              <th width="120">Issues</th>
              <th width="160">Last Updated</th>
              <th width="110">Actions</th>
            </tr>
          </thead>
          <tbody>
          @forelse($questions as $question)
            @php
              $qaStatus   = $question->qa_status ?? 'unreviewed';
              $issueCount = $question->qa_issues_count ?? 0;
              $openIssues = $question->open_qa_issues_count ?? 0;
              $reviewUrl  = route('admin.qa.questions.review', $question->id);

              $statusConfig = [
                'unreviewed'     => ['color' => 'warning', 'icon' => 'clock',        'text' => 'Unreviewed'],
                'approved'       => ['color' => 'success', 'icon' => 'check-circle', 'text' => 'Approved'],
                'flagged'        => ['color' => 'danger',  'icon' => 'flag',         'text' => 'Flagged'],
                'needs_revision' => ['color' => 'info',    'icon' => 'edit',         'text' => 'Needs Revision'],
              ];
              $config = $statusConfig[$qaStatus] ?? $statusConfig['unreviewed'];

              $isPublic = (int)($question->status_id ?? 4) === 3;
            @endphp
            <tr class="question-row">
              <td>
                <input type="checkbox" class="form-check-input question-checkbox" value="{{ $question->id }}" data-id="{{ $question->id }}">
              </td>
              <td><a href="{{ $reviewUrl }}" class="text-decoration-none">#{{ $question->id }}</a></td>
              <td>
                <a href="{{ $reviewUrl }}" class="question-link question-field">
                  {{ \Illuminate\Support\Str::limit(strip_tags($question->question ?? ''), 80) }}
                  @if(strlen(strip_tags($question->question ?? '')) > 80)…@endif
                  @if($question->question_image)
                    <small class="text-muted d-block mt-1"><i class="fas fa-image me-1"></i>Has image</small>
                  @endif
                </a>
              </td>
              <td>
                @if($question->is_diagnostic)
                <span class="badge text-danger">
                  Sentinel
                </span>
                @endif
                <span class="badge text-bg-{{ $question->type_id == 1 ? 'primary' : 'info' }}">
                  {{ $question->type_id == 1 ? 'Multiple Choice' : 'Fill in Blank' }}
                </span>
              </td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <span class="badge text-bg-{{ $config['color'] }}">
                    <i class="fas fa-{{ $config['icon'] }} me-1"></i>{{ $config['text'] }}
                  </span>
                  <span class="badge {{ $isPublic ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $isPublic ? 'Public' : 'Draft' }}
                    @if($isPublic && $question->published_at)
                      <small class="ms-1">{{ $human($question->published_at) }}</small>
                    @endif
                  </span>
                </div>
              </td>
              <td>
                @if($question->skill)
                  <span class="badge text-bg-secondary">{{ $question->skill->skill }}</span>
                @else
                  <span class="text-muted">No skill</span>
                @endif
              </td>
              <td>
                @if($issueCount > 0)
                  <span class="badge text-bg-{{ $openIssues > 0 ? 'warning' : 'success' }}">
                    {{ $issueCount }} {{ $openIssues > 0 ? 'open' : 'resolved' }}
                  </span>
                @else
                  <span class="text-muted">None</span>
                @endif
              </td>
              <td><small class="text-muted">
                {{ $human($question->updated_at) }}
              </small></td>
              <td>
                <div class="btn-group" role="group">
                  <a href="{{ $reviewUrl }}" class="btn btn-outline-primary btn-sm" title="Review">
                    <i class="fas fa-eye"></i>
                  </a>
                  @if($qaStatus === 'unreviewed' || $qaStatus === 'needs_revision')
                    <button type="button" class="btn btn-outline-success btn-sm" data-approve="{{ $question->id }}" title="Quick Approve">
                      <i class="fas fa-check"></i>
                    </button>
                  @endif
                  <button type="button" class="btn btn-outline-warning btn-sm" data-flag="{{ $question->id }}" title="Flag Issue">
                    <i class="fas fa-flag"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No questions found matching your criteria.</p>
                <a href="{{ route('admin.qa.index') }}" class="btn btn-outline-primary">View All Questions</a>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Pagination --}}
  @if($questions->hasPages())
    <div class="d-flex justify-content-center mt-4">
      {{ $questions->appends(request()->query())->links() }}
    </div>
  @endif

</div>

{{-- Quick Flag Modal --}}
<div class="modal fade" id="quickFlagModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="quickFlagForm">@csrf
        <div class="modal-header">
          <h5 class="modal-title">Flag Question</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="flagQuestionId" name="question_id">
          <div class="mb-3">
            <label for="flag_issue_type" class="form-label">Issue Type <span class="text-danger">*</span></label>
            <select class="form-select" name="issue_type" id="flag_issue_type" required>
              <option value="">Select issue type</option>
              <option value="unclear">Unclear Question</option>
              <option value="incorrect">Incorrect Answer</option>
              <option value="grammar">Grammar/Spelling</option>
              <option value="formatting">Formatting Issues</option>
              <option value="duplicate">Duplicate Question</option>
              <option value="inappropriate">Inappropriate Content</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="flag_description" class="form-label">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description" id="flag_description" rows="3" placeholder="Please provide details about the issue..." required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">
            <i class="fas fa-flag me-1"></i>Flag Question
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const token = document.querySelector('meta[name="csrf-token"]').content;

  // selection
  const table = document.getElementById('qaTable');
  const selectAll = document.getElementById('selectAll');
  const batchEl = document.getElementById('batchActions');
  const selectedCount = document.getElementById('selectedCount');

  table.addEventListener('change', e => {
    if (e.target.classList.contains('question-checkbox')) updateBatch();
  });
  selectAll.addEventListener('change', () => {
    table.querySelectorAll('.question-checkbox').forEach(cb => cb.checked = selectAll.checked);
    updateBatch();
  });
  function updateBatch() {
    const selected = table.querySelectorAll('.question-checkbox:checked');
    selectedCount.textContent = selected.length;
    batchEl.classList.toggle('d-none', selected.length === 0);
    const all = table.querySelectorAll('.question-checkbox').length;
    selectAll.checked = selected.length && selected.length === all;
    selectAll.indeterminate = selected.length > 0 && selected.length < all;
  }

  // tiny helper for JSON POSTs
  async function postJSON(url, body = {}) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': token, 'Content-Type': 'application/json'},
      body: JSON.stringify(body)
    });
    return res.json();
  }

  // delegated actions for approve/flag
  table.addEventListener('click', async e => {
    const approveBtn = e.target.closest('[data-approve]');
    const flagBtn = e.target.closest('[data-flag]');

    if (approveBtn) {
      const id = approveBtn.dataset.approve;
      if (!confirm('Approve this question?')) return;
      try {
        const data = await postJSON(`/admin/qa/questions/${id}/approve`);
        if (data.success) {
          showToast?.(data.message || 'Approved', 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showToast?.(data.message || 'Failed to approve question', 'error');
        }
      } catch {
        showToast?.('Failed to approve question', 'error');
      }
    }

    if (flagBtn) {
      document.getElementById('flagQuestionId').value = flagBtn.dataset.flag;
      new bootstrap.Modal(document.getElementById('quickFlagModal')).show();
    }
  });

  // bulk actions
  document.getElementById('approveSelected').addEventListener('click', () => bulk('approve'));
  document.getElementById('flagSelected').addEventListener('click', () => bulk('flag'));
  document.getElementById('clearSelected').addEventListener('click', clearSelection);

  async function bulk(action) {
    const ids = [...table.querySelectorAll('.question-checkbox:checked')].map(cb => cb.value);
    if (!ids.length) return showToast?.('Please select questions first', 'warning');
    if (!confirm(`${action} ${ids.length} selected questions?`)) return;
    try {
      const data = await postJSON(`/admin/qa/bulk-${action}`, {question_ids: ids});
      if (data.success) {
        showToast?.(data.message || 'Done', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast?.(data.message || `Failed to ${action} questions`, 'error');
      }
    } catch {
      showToast?.(`Failed to ${action} questions`, 'error');
    }
  }

  function clearSelection() {
    table.querySelectorAll('.question-checkbox').forEach(cb => cb.checked = false);
    selectAll.checked = false;
    selectAll.indeterminate = false;
    batchEl.classList.add('d-none');
  }

  // quick flag submit
  document.getElementById('quickFlagForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('flagQuestionId').value;
    const body = {
      issue_type: document.getElementById('flag_issue_type').value,
      description: document.getElementById('flag_description').value
    };
    try {
      const data = await postJSON(`/admin/qa/questions/${id}/flag`, body);
      if (data.success) {
        showToast?.(data.message || 'Flagged', 'success');
        bootstrap.Modal.getInstance(document.getElementById('quickFlagModal')).hide();
        setTimeout(() => location.reload(), 800);
      } else {
        showToast?.(data.message || 'Failed to flag question', 'error');
      }
    } catch {
      showToast?.('Failed to flag question', 'error');
    }
  });

  // filters auto-submit
  document.getElementById('filterForm').addEventListener('change', e => {
    if (e.target.matches('select')) e.currentTarget.submit();
  });

  // flash
  @if(session('success')) showToast?.(@json(session('success')), 'success'); @endif
  @if(session('error'))   showToast?.(@json(session('error')),   'error');   @endif
});
</script>
@endpush
