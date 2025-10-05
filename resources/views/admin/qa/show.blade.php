@extends('layouts.admin')

@section('title', 'Review Question #'.$question->id)

@php
use Illuminate\Support\Carbon;

$human = function ($d, $fallback = null) {
  if (empty($d)) return $fallback;
  if ($d instanceof \DateTimeInterface) return $d->diffForHumans();
  try { return Carbon::parse($d)->diffForHumans(); } catch (\Throwable $e) { return $fallback; }
};

$u = auth()->user();

$canQA = $u && (
  (method_exists($u,'canAccessQA') && $u->canAccessQA())
  || (method_exists($u,'canAccessAdmin') && $u->canAccessAdmin())
);

// Force read-only on this page
$editMode = false;

$qaStatus = $question->qa_status ?? 'unreviewed';
$statusConfig = [
  'unreviewed'     => ['color'=>'warning','icon'=>'clock','text'=>'Unreviewed'],
  'approved'       => ['color'=>'success','icon'=>'check-circle','text'=>'Approved'],
  'flagged'        => ['color'=>'danger','icon'=>'flag','text'=>'Flagged'],
  'needs_revision' => ['color'=>'info','icon'=>'edit','text'=>'Needs Revision'],
  'ai_generated'   => ['color'=>'secondary','icon'=>'robot','text'=>'AI-Generated'],
];
$config = $statusConfig[$qaStatus] ?? $statusConfig['unreviewed'];

$isPublic = (int)($question->status_id ?? 4) === 3;
$difficultyNames = ['', 'Easy', 'Medium', 'Hard'];
$difficulty = $difficultyNames[$question->difficulty_id ?? 0] ?? 'Unknown';
$difficultyColors = ['', 'success', 'warning', 'danger'];
$difficultyColor = $difficultyColors[$question->difficulty_id ?? 0] ?? 'secondary';
@endphp

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
<style>
  .question-link { text-decoration:none; }
  .thumb { max-width:100%; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .ans-letter { width:40px;height:40px;font-weight:700 }
  .cursor-pointer { cursor:pointer; }
  .img-empty { border:1px dashed #ccc; border-radius:8px; padding:12px; text-align:center; color:#888; }
  .badge-status { font-size:.9rem; padding:.4rem .6rem; }
  .katex-block { margin: 8px 0; }
  .form-text code { background:#f6f6f6; padding:.1rem .25rem; border-radius:4px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
  {{-- Header --}}
  <div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-start gap-2">
      <div>
        <h2 class="mb-1">Review Question #{{ $question->id }}</h2>
        <div class="small text-muted">
          Created: {{ $question->created_at ? $question->created_at->format('M d, Y') : 'Unknown' }}
          @if(!empty($question->published_at))
            &nbsp;•&nbsp; Published {{ $human($question->published_at, 'recently') }}
          @endif
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.qa.index') }}" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Back to QA Dashboard
        </a>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#helpModal">
          <i class="fas fa-question-circle me-1"></i>QA Guidelines
        </button>
        <a href="{{ route('admin.qa.export') }}" class="btn btn-outline-primary">
          <i class="fas fa-download me-1"></i>Export Report
        </a>
      </div>
    </div>
  </div>

  {{-- QA Manual Modal --}}
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

  <div class="row">
    {{-- Left: Content and metadata --}}
    <div class="col-lg-8">

      {{-- Status card --}}
      <div class="card mb-4">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex gap-2 align-items-center">
              <span class="badge badge-status bg-{{ $config['color'] }}">
                <i class="fas fa-{{ $config['icon'] }} me-1"></i>{{ $config['text'] }}
              </span>
              <span class="badge badge-status {{ $isPublic ? 'bg-success' : 'bg-secondary' }}">
                {{ $isPublic ? 'Public' : 'Draft' }}
              </span>
            </div>
            <div class="text-muted small">
              @if($question->skill)
                <i class="fas fa-brain me-1"></i>Skill: {{ $question->skill->skill }} &nbsp;&nbsp;
              @endif
              <i class="fas fa-signal me-1"></i>Difficulty: {{ $difficulty }}
            </div>
          </div>
        </div>
      </div>

      {{-- Question content --}}
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            Question Content
            <span class="badge bg-{{ $question->type_id == 1 ? 'primary' : 'success' }} ms-2">
              {{ $question->type->type }}
            </span>
          </h5>

          {{-- Only path to editing is via Questions.show --}}
          <a href="{{ route('admin.questions.show', [$question->id, 'edit' => 1]) }}" class="btn btn-primary">
            <i class="fas fa-pen-to-square me-1"></i> Edit Content
          </a>
        </div>

        <div class="card-body">
          {{-- Read-only view --}}
          {{-- Question image block (no upload/remove controls) --}}
          <div id="qImageBlock" class="mb-4">
            <h6 class="text-muted small mb-2">QUESTION IMAGE</h6>
            @php $qImg = $question->question_image; @endphp
            <div class="text-center mb-2">
              <img id="qImagePreview" class="thumb" style="{{ $qImg ? '' : 'display:none' }}"
                   src="{{ $qImg ? asset($qImg) : '' }}" alt="Question image">
              <div id="qImageEmpty" class="img-empty" style="{{ $qImg ? 'display:none' : '' }}">
                No image uploaded
              </div>
            </div>
          </div>

          {{-- Question text --}}
          <div class="mb-4">
            <h6 class="text-muted small mb-2">QUESTION TEXT</h6>
            <div class="border rounded p-3 bg-light question-field">{!! $question->question ?: '[No question text provided]' !!}</div>
          </div>

          {{-- Answers read only --}}
          @if($question->type_id == 1)
            @php
              $answers = [
                ['text' => $question->answer0 ?? '', 'image' => $question->answer0_image ?? '', 'i' => 0],
                ['text' => $question->answer1 ?? '', 'image' => $question->answer1_image ?? '', 'i' => 1],
                ['text' => $question->answer2 ?? '', 'image' => $question->answer2_image ?? '', 'i' => 2],
                ['text' => $question->answer3 ?? '', 'image' => $question->answer3_image ?? '', 'i' => 3],
              ];
              $correctIndex = (int)($question->correct_answer ?? 0);
            @endphp
            <h6 class="text-muted small mb-3">ANSWER OPTIONS</h6>
            @foreach($answers as $ans)
              @php
                $i = $ans['i'];
                $img = $ans['image'];
                $isCorrect = $i === $correctIndex;
                $letter = chr(65 + $i);
              @endphp
              <div class="border rounded p-3 mb-3 {{ $isCorrect ? 'bg-success bg-opacity-10 border-success' : '' }}">
                <div class="row align-items-start">
                  <div class="col-auto">
                    <div class="rounded-circle d-flex align-items-center justify-content-center position-relative
                                {{ $isCorrect ? 'bg-success text-white' : 'bg-secondary text-white' }} ans-letter">
                      {{ $letter }}
                      @if($isCorrect)
                        <i class="fas fa-check position-absolute" style="font-size:0.7em;top:2px;right:2px;"></i>
                      @endif
                    </div>
                  </div>
                  <div class="col">
                    @if($ans['text'])
                      <div class="mb-2 mcq-option">{!! $ans['text'] !!}</div>
                    @endif
                    <div class="d-flex align-items-center gap-3">
                      <div style="min-width:120px">
                        <img class="thumb ans-preview" style="{{ $img ? '' : 'display:none' }}" src="{{ $img ? asset($img) : '' }}" alt="Answer {{ $letter }} image">
                        <div class="img-empty ans-empty" style="{{ $img ? 'display:none' : '' }}">No image</div>
                      </div>
                    </div>
                    @if($isCorrect)
                      <small class="text-success fw-bold d-block mt-2">
                        <i class="fas fa-check-circle me-1"></i>Correct Answer
                      </small>
                    @endif
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <div class="mb-4">
              <h6 class="text-muted small mb-2">CORRECT ANSWER</h6>
              <div class="border rounded p-3 bg-success bg-opacity-10 border-success">
                <div class="d-flex align-items-center">
                  <i class="fas fa-check-circle text-success me-2"></i>
                  <strong>{{ $question->correct_answer ?: '[No answer provided]' }}</strong>
                </div>
              </div>
            </div>
          @endif

          {{-- Meta row --}}
          <div class="row mt-4">
            <div class="col-md-4">
              <h6 class="text-muted small mb-2">DIFFICULTY</h6>
              <span class="badge bg-{{ $difficultyColor }}">{{ $question->difficulty->short_description }}</span>
            </div>
            <div class="col-md-6">
              <h6 class="text-muted small mb-2">SKILL</h6>
              @if($question->skill)
                <span class="badge bg-primary">{{ $question->skill->skill }}</span>
              @else
                <span class="text-muted">No skill assigned</span>
              @endif
            </div>
            <div class="col-md-1 text-center">
              <h6 class="text-muted small mb-2"><i class="fas fa-calculator"></i></h6>
              <span class="badge bg-secondary">{{ $question->calculator ? ucfirst($question->calculator) : 'None' }}</span>
            </div>
          </div>

          {{-- Hints (read-only) --}}
          <div class="mt-4">
            <label class="form-label text-muted small">HINTS</label>
            @if($question->hints && $question->hints->count() > 0)
              <div class="hints-container">
                @foreach($question->hints->sortBy('hint_level') as $hint)
                  <div class="hint-item mb-2 p-2 border-start border-3 border-info bg-light">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1 d-flex align-items-center gap-2">
                        <span class="badge bg-info me-2">Level</span>
                        <span>{{ $hint->hint_level }}</span>
                        <span class="ms-2">{{ $hint->hint_text }}</span>
                      </div>
                    </div>
                    @if($hint->user)
                      <small class="text-muted">Added by {{ $hint->user->name }}</small>
                    @endif
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted">No hints available</p>
            @endif
          </div>

          {{-- Solutions (read-only) --}}
          <div class="mt-3">
            <label class="form-label text-muted small">SOLUTIONS</label>
            @if($question->solutions && $question->solutions->count() > 0)
              <div class="solutions-container">
                @foreach($question->solutions as $solution)
                  <div class="solution-item mb-3 p-3 border rounded bg-light">
                    <div class="solution-content">{!! nl2br(e($solution->solution)) !!}</div>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                      <small class="text-muted">
                        <i class="fas fa-user me-1"></i>
                        By {{ $solution->user->name ?? 'Unknown' }} on {{ optional($solution->created_at)->format('M d, Y') }}
                      </small>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <p class="text-muted">No solutions available</p>
            @endif
          </div>

        </div>
      </div>
    </div>

    {{-- Right: QA actions (status only) --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">QA Actions</h5>
          @if($question->qa_reviewer_id)
            <small class="text-muted">
              <i class="fas fa-user-check me-1"></i>
              Assigned to: {{ optional(\App\Models\User::find($question->qa_reviewer_id))->name ?? 'Unknown' }}
            </small>
          @endif
        </div>

        <div class="card-body">
          @if($canQA)
            <div class="d-grid gap-2 mb-3">
              <button type="button" class="btn btn-success" onclick="setStatus({{ $question->id }}, 'approved')">
                <i class="fas fa-check me-1"></i> Approve
              </button>
              <button type="button" class="btn btn-info" onclick="needsRevision({{ $question->id }})">
                <i class="fas fa-edit me-1"></i> Needs Revision…
              </button>
              <button type="button" class="btn btn-warning" onclick="flagWithReason({{ $question->id }})">
                <i class="fas fa-flag me-1"></i> Report Issue…
              </button>
              <button type="button" class="btn btn-outline-danger" onclick="setStatus({{ $question->id }}, 'ai_generated')">
                <i class="fas fa-robot me-1"></i> Mark as AI-generated
              </button>
              <button type="button" class="btn btn-outline-secondary" onclick="setStatus({{ $question->id }}, 'unreviewed')">
                <i class="fas fa-undo me-1"></i> Unreview
              </button>
            </div>

            <div class="d-flex gap-2">
              <a class="btn btn-outline-dark" href="{{ route('admin.qa.previous', ['before' => $question->id]) }}">
                <i class="fas fa-backward me-1"></i> Prev
              </a>
              <a class="btn btn-outline-dark" href="{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}">
                <i class="fas fa-forward me-1"></i> Next
              </a>
            </div>
          @else
            <div class="alert alert-info mb-0">You do not have QA privileges on this item.</div>
          @endif

          @include('admin.components.math-help')

          @if($question->qa_reviewed_at)
            <div class="text-muted small mt-3"><i class="fas fa-clock me-1"></i> Reviewed {{ $human($question->qa_reviewed_at) }}</div>
          @endif
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/contrib/auto-render.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

  // Lightweight toast helper
  function toast(msg, type='info') { window.showToast ? showToast(msg, type) : alert(msg); }

  async function handleJson(res) {
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const text = await res.text();
      return { success:false, message:`Unexpected response (${res.status}).`, html:text };
    }
    return res.json();
  }

  // QA endpoints (status only)
  const QA = {
    status: (id, status, extra = {}) => fetch(`/admin/qa/questions/${id}/status`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ status, ...extra }),
      credentials: 'same-origin'
    }).then(handleJson),
  };

  window.setStatus = (id, status) => {
    QA.status(id, status).then(d => {
      toast(d.message || (d.success ? 'Updated' : 'Failed'), d.success ? 'success' : 'error');
      if (d.success) setTimeout(() => location.reload(), 400);
    });
  };

  window.needsRevision = (id) => {
    const note = prompt('What needs to be changed?');
    if (!note) return;
    QA.status(id, 'needs_revision', { note }).then(d => {
      toast(d.message || (d.success ? 'Marked as needs revision' : 'Failed'), d.success ? 'success' : 'error');
      if (d.success) setTimeout(() => location.reload(), 400);
    });
  };

  window.flagWithReason = (id) => {
    const reason = prompt('Describe the issue');
    if (!reason) return;
    QA.status(id, 'flagged', { issue_type: 'other', note: reason }).then(d => {
      toast(d.message || (d.success ? 'Flagged' : 'Failed'), d.success ? 'success' : 'error');
      if (d.success) setTimeout(() => location.reload(), 400);
    });
  };

  // Keyboard shortcuts for status changes only
  document.addEventListener('keydown', (e) => {
    if (e.target && ['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
    const id = {{ (int)$question->id }};
    const k = e.key.toLowerCase();
    if (k === 'a') window.setStatus(id, 'approved');
    if (k === 'r') window.needsRevision(id);
    if (k === 'f') window.flagWithReason(id);
    if (k === 'u') window.setStatus(id, 'unreviewed');
    if (k === 'm') window.setStatus(id, 'ai_generated');
    if (k === 'n') window.location.href = "{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}";
  });

  // Optional KaTeX auto-render in case raw TeX appears
  if (window.renderMathInElement) {
    const targets = document.querySelectorAll('.question-field, .mcq-option');
    targets.forEach(el => {
      renderMathInElement(el, {
        delimiters: [
          {left: '$$', right: '$$', display: true},
          {left: '$', right: '$', display: false},
          {left: '\\(', right: '\\)', display: false},
          {left: '\\[', right: '\\]', display: true}
        ],
        throwOnError: false
      });
    });
  }
});
</script>
@endpush