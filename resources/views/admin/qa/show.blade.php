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

$canEdit = $u && (
(method_exists($u,'canAccessAdmin') && $u->canAccessAdmin())
|| ($u->id === ($question->user_id ?? null))
|| (method_exists($u,'hasPermission') && $u->hasPermission('qa_edit_content'))
);

$canQA = $u && (
(method_exists($u,'canAccessQA') && $u->canAccessQA())
|| (method_exists($u,'canAccessAdmin') && $u->canAccessAdmin())
);

$editMode = request()->boolean('edit');

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
</div></div>
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

  @if(!$editMode && $canEdit)
  <a href="{{ route('admin.questions.show', [$question->id, 'edit' => 1]) }}" class="btn btn-primary">
      <i class="fas fa-pen-to-square me-1"></i> Edit Content
  </a>
  @endif
</div>

<div class="card-body">

  @if(!$editMode)
  {{-- Read only view --}}
  {{-- Question image block --}}
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
  @if($canEdit)
  <div class="d-flex gap-2 justify-content-center">
    <input class="form-control form-control-sm u-file" type="file" accept="image/*" style="max-width:260px">
    <button type="button" class="btn btn-sm btn-outline-primary u-upload"
    data-url="{{ route('admin.questions.upload-image', $question->id) }}">
    <i class="fas fa-upload me-1"></i>Upload
</button>
@if($qImg)
<button type="button" class="btn btn-sm btn-outline-danger u-delete"
data-url="{{ route('admin.questions.delete-image', $question->id) }}">
<i class="fas fa-trash me-1"></i>Remove
</button>
@endif
</div>
<div class="form-text text-center">PNG, JPG, GIF, WebP, up to 6 MB.</div>
@endif
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

@else
{{-- Edit mode --}}
<form id="qEditForm"
action="{{ route('admin.questions.update', $question->id) }}"
method="POST" class="mt-2">
@csrf
@method('PUT')

{{-- Hidden fields receive HTML after client conversion --}}
<input type="hidden" name="question" id="questionHtml">
@if($question->type_id == 1)
<input type="hidden" name="answer0" id="ans0Html">
<input type="hidden" name="answer1" id="ans1Html">
<input type="hidden" name="answer2" id="ans2Html">
<input type="hidden" name="answer3" id="ans3Html">
<input type="hidden" name="correct_answer" id="correctHidden">
@else
<input type="hidden" name="correct_answer" id="fibHidden">
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
      <label class="form-label">Type</label>
      <select class="form-select" name="type_id" id="typeSelect">
        <option value="1" {{ $question->type_id==1?'selected':'' }}>Multiple Choice</option>
        <option value="2" {{ $question->type_id==2?'selected':'' }}>Fill in the Blank</option>
    </select>
</div>
<div class="col-md-3">
  <label class="form-label">Difficulty</label>
  <select class="form-select" name="difficulty_id">
    <option value="1" {{ $question->difficulty_id==1?'selected':'' }}>Easy</option>
    <option value="2" {{ $question->difficulty_id==2?'selected':'' }}>Medium</option>
    <option value="3" {{ $question->difficulty_id==3?'selected':'' }}>Hard</option>
</select>
</div>
<div class="col-md-3">
  <label class="form-label">Calculator</label>
  <select class="form-select" name="calculator">
    @php $calc = $question->calculator ?? 'none'; @endphp
    <option value="none" {{ $calc==='none'?'selected':'' }}>None</option>
    <option value="basic" {{ $calc==='basic'?'selected':'' }}>Basic</option>
    <option value="scientific" {{ $calc==='scientific'?'selected':'' }}>Scientific</option>
</select>
</div>
</div>

{{-- Question editor --}}
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
      <h6 class="text-muted small mb-2">QUESTION (plain text with LaTeX)</h6>
      <div class="small text-muted">Inline math: <code>\( a^2+b^2 \)</code> · Block math: <code>$$ \int ... $$</code></div>
  </div>
  <div class="row g-3">
      <div class="col-lg-6">
        <textarea id="qSource" class="form-control" rows="10"
        placeholder="Type your question here with LaTeX...">{{ old('qSource', strip_tags($question->question)) }}</textarea>
        <div class="form-text">You write LaTeX, the page renders it. No HTML required.</div>
    </div>
    <div class="col-lg-6">
        <div class="border rounded p-3 bg-light" id="qPreview" style="min-height: 220px; overflow:auto"></div>
        <div class="form-text">Live preview</div>
    </div>
</div>
</div>

{{-- MCQ answers editor --}}
<div id="mcqBlock" style="{{ $question->type_id==1 ? '' : 'display:none' }}">
    <h6 class="text-muted small mb-3">ANSWER OPTIONS (LaTeX supported)</h6>
    @php
    $answers = [
    old('ans0Source', $question->answer0 ?? ''),
    old('ans1Source', $question->answer1 ?? ''),
    old('ans2Source', $question->answer2 ?? ''),
    old('ans3Source', $question->answer3 ?? ''),
    ];
    $correctIndex = (int)($question->correct_answer ?? 0);
    @endphp
    @for($i=0; $i<4; $i++)
    <div class="border rounded p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong>Option {{ chr(65+$i) }}</strong>
          <div class="form-check">
            <input class="form-check-input corrRadio" type="radio" name="correct_pick" id="corr{{ $i }}" value="{{ $i }}" {{ $correctIndex===$i?'checked':'' }}>
            <label class="form-check-label" for="corr{{ $i }}">Correct</label>
        </div>
    </div>
    <div class="row g-3">
      <div class="col-lg-6">
        <textarea class="form-control ansSource" rows="3" data-idx="{{ $i }}" placeholder="Type option {{ chr(65+$i) }} with LaTeX...">{{ $answers[$i] }}</textarea>
        <div class="form-text">Optional images can still be managed below in read only mode for now.</div>
    </div>
    <div class="col-lg-6">
        <div class="border rounded p-2 bg-light ansPreview" style="min-height: 60px"></div>
    </div>
</div>
</div>
@endfor
</div>

{{-- FIB answer editor --}}
<div id="fibBlock" style="{{ $question->type_id==2 ? '' : 'display:none' }}">
    <h6 class="text-muted small mb-2">CORRECT ANSWER (Fill in the Blank)</h6>
    <input class="form-control" id="fibSource" value="{{ old('fibSource', $question->correct_answer ?? '') }}" placeholder="Enter the correct numeric or text answer">
    <div class="form-text">If you support multiple blanks, keep your existing backend convention for answer0, answer1, and so on.</div>
</div>

<div class="d-flex gap-2 mt-4">
    <a href="{{ route('admin.questions.show', $question->id) }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-success">
      <i class="fas fa-save me-1"></i> Save Changes
  </button>
</div>
</form>
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

</div>
{{-- Hints --}}
<div class="mb-4">
  <label class="form-label text-muted small">HINTS</label>
  
  @if($question->hints && $question->hints->count() > 0)
  <div class="hints-container">
      @foreach($question->hints->sortBy('hint_level') as $hint)
      <div class="hint-item mb-2 p-2 border-start border-3 border-info bg-light" data-hint-id="{{ $hint->id }}">
          <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1 d-flex align-items-center gap-2">
              <span class="badge bg-info me-2">Level</span>
              
              @if($canEdit && $editMode)
              <span class="editable-field editable-hint-level d-inline-block"
              data-hint-id="{{ $hint->id }}"
              data-field="hint_level"
              data-type="number"
              title="Click to edit level">
              {{ $hint->hint_level }}
              <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
          </span>
          <span class="editable-field editable-hint-text d-inline-block ms-2"
          data-hint-id="{{ $hint->id }}"
          data-field="hint_text"
          data-type="textarea"
          title="Click to edit hint text">
          {{ $hint->hint_text }}
          <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
      </span>
      @else
      <span>{{ $hint->hint_level }}</span>
      <span class="ms-2">{{ $hint->hint_text }}</span>
      @endif
  </div>

  @if($canEdit && $editMode)
  <button class="btn btn-sm btn-outline-danger delete-hint"
  data-hint-id="{{ $hint->id }}"
  title="Delete hint">
  <i class="fas fa-times"></i>
</button>
@endif
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

@if($canEdit && $editMode)
{{-- Inline add hint box --}}
<div id="add-hint-box" class="inline-add-box mt-2">
  <div class="row g-2">
    <div class="col-12 col-md-3">
      <label class="form-label small">Hint Level</label>
      <select class="form-select form-select-sm" id="new-hint-level">
        <option value="1">1 (easy nudge)</option>
        <option value="2">2 (medium clue)</option>
        <option value="3">3 (almost reveals)</option>
    </select>
</div>
<div class="col-12 col-md-9">
  <label class="form-label small">Hint Text</label>
  <textarea class="form-control form-control-sm" id="new-hint-text" rows="3" placeholder="Short, progressive hint..."></textarea>
</div>
</div>
<div class="d-flex justify-content-end mt-2 inline-actions">
    <button class="btn btn-sm btn-secondary" id="cancel-add-hint">Cancel</button>
    <button class="btn btn-sm btn-primary" id="save-new-hint">Save Hint</button>
</div>
</div>

<button class="btn btn-sm btn-outline-primary mt-2" id="toggle-add-hint">
  <i class="fas fa-plus me-1"></i>Add Hint
</button>
@endif
</div>

{{-- Solutions --}}
<div class="mb-2">
  <label class="form-label text-muted small">SOLUTIONS</label>

  @if($question->solutions && $question->solutions->count() > 0)
  <div class="solutions-container">
      @foreach($question->solutions as $solution)
      <div class="solution-item mb-3 p-3 border rounded bg-light" data-solution-id="{{ $solution->id }}">
          @if($canEdit && $editMode)
          <div class="solution-content editable-field editable-solution"
          data-solution-id="{{ $solution->id }}"
          data-field="solution"
          data-type="textarea"
          title="Click to edit solution">
          {!! nl2br(e($solution->solution)) !!}
          <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
      </div>
      @else
      <div class="solution-content">
          {!! nl2br(e($solution->solution)) !!}
      </div>
      @endif

      <div class="mt-2 d-flex justify-content-between align-items-center">
        <small class="text-muted">
          <i class="fas fa-user me-1"></i>
          By {{ $solution->user->name ?? 'Unknown' }} on {{ optional($solution->created_at)->format('M d, Y') }}
      </small>
      @if($canEdit && $editMode)
      <button class="btn btn-sm btn-outline-danger delete-solution"
      data-solution-id="{{ $solution->id }}"
      title="Delete solution">
      <i class="fas fa-trash"></i>
  </button>
  @endif
</div>
</div>
@endforeach
</div>
@else
<p class="text-muted">No solutions available</p>
@endif

@if($canEdit && $editMode)
{{-- Inline add solution box --}}
<div id="add-solution-box" class="inline-add-box mt-2">
  <div class="mb-2">
    <label class="form-label small">Solution</label>
    <textarea class="form-control form-control-sm" id="new-solution-text" rows="6" placeholder="Type the solution..."></textarea>
</div>
<div class="d-flex justify-content-end inline-actions">
    <button class="btn btn-sm btn-secondary" id="cancel-add-solution">Cancel</button>
    <button class="btn btn-sm btn-primary" id="save-new-solution">Save Solution</button>
</div>
</div>

<button class="btn btn-sm btn-outline-primary mt-2" id="toggle-add-solution">
  <i class="fas fa-plus me-1"></i>Add Solution
</button>
@endif
</div>      
</div>
</div>

{{-- Right: QA actions --}}
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

  <div class="d-flex gap-2 mb-3">
      <a class="btn btn-outline-dark" href="{{ route('admin.qa.previous', ['before' => $question->id]) }}">
          <i class="fas fa-backward me-1"></i> Prev
      </a>
      <a class="btn btn-outline-dark" href="{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}">
          <i class="fas fa-forward me-1"></i> Next
      </a>
  </div>

  <label class="form-label fw-semibold">Reviewer Notes</label>
  <textarea id="qaNotes" class="form-control mb-2" rows="3" placeholder="Add context for the author or other reviewers">{{ $question->qa_notes }}</textarea>
  <button type="button" class="btn btn-outline-success w-100" onclick="saveNotes({{ $question->id }})">
    <i class="fas fa-save me-1"></i> Save Notes
</button>
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

  // Helpers
  async function postForm(url, formData, method = 'POST') {
    const res = await fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData,
    credentials: 'same-origin'
});
    return handleJson(res);
}
async function del(url) {
    const res = await fetch(url, {
      method:'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
});
    return handleJson(res);
}
async function handleJson(res) {
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const text = await res.text();
      return { success:false, message:`Unexpected response (${res.status}). Likely a redirect or auth issue.`, html:text };
  }
  return res.json();
}
function toast(msg, type='info') { window.showToast ? showToast(msg, type) : alert(msg); }

  // Image preview helper
  function previewFile(file, imgEl, emptyEl) {
    if (!file) return;
    const maxMb = 6;
    if (file.size > maxMb * 1024 * 1024) { toast(`Image is too large (max ${maxMb}MB).`, 'warning'); return; }
    if (!/^image\/(png|jpe?g|gif|webp)$/i.test(file.type)) { toast('Unsupported image type. Use PNG, JPG, GIF, or WebP.', 'warning'); return; }
    const url = URL.createObjectURL(file);
    if (imgEl) { imgEl.src = url; imgEl.style.display = ''; }
    if (emptyEl) emptyEl.style.display = 'none';
}

  // Question image block wiring
  const qBlock = document.getElementById('qImageBlock');
  if (qBlock) {
    const qInput   = qBlock.querySelector('.u-file');
    const qPreview = document.getElementById('qImagePreview');
    const qEmpty   = document.getElementById('qImageEmpty');
    qInput?.addEventListener('change', (e) => previewFile(e.target.files?.[0], qPreview, qEmpty));

    qBlock.querySelector('.u-upload')?.addEventListener('click', async (e) => {
      const file = qInput?.files?.[0];
      if (!file) return toast('Pick an image first.');
      const fd = new FormData();
      fd.append('image', file);
      try {
        const data = await postForm(e.currentTarget.dataset.url, fd, 'POST');
        if (data?.success) {
          qPreview.src = data.image_url || qPreview.src;
          qPreview.style.display = '';
          qEmpty.style.display = 'none';
          toast(data.message || 'Image uploaded', 'success');
      } else {
          toast(data?.message || 'Upload failed', 'error');
      }
  } catch { toast('Upload failed (network).', 'error'); }
});

    qBlock.querySelector('.u-delete')?.addEventListener('click', async (e) => {
      if (!confirm('Remove question image?')) return;
      try {
        const data = await del(e.currentTarget.dataset.url);
        if (data?.success) {
          qPreview.src = '';
          qPreview.style.display = 'none';
          qEmpty.style.display = '';
          toast(data.message || 'Image removed', 'success');
      } else {
          toast(data?.message || 'Remove failed', 'error');
      }
  } catch { toast('Remove failed (network).', 'error'); }
});
}

  // ===== Inline LaTeX editing pipeline =====
  const editForm = document.getElementById('qEditForm');
  if (editForm) {
    const typeSelect = document.getElementById('typeSelect');
    const mcqBlock   = document.getElementById('mcqBlock');
    const fibBlock   = document.getElementById('fibBlock');

    const qSource  = document.getElementById('qSource');
    const qPreview = document.getElementById('qPreview');
    const qHidden  = document.getElementById('questionHtml');

    const ansSources = Array.from(document.querySelectorAll('.ansSource'));
    const ansPreviews= Array.from(document.querySelectorAll('.ansPreview'));
    const ansHidden  = [
    document.getElementById('ans0Html'),
    document.getElementById('ans1Html'),
    document.getElementById('ans2Html'),
    document.getElementById('ans3Html')
    ];

    const corrRadios = Array.from(document.querySelectorAll('.corrRadio'));
    const corrHidden = document.getElementById('correctHidden');

    const fibSource  = document.getElementById('fibSource');
    const fibHidden  = document.getElementById('fibHidden');

    function syncType() {
      const isMCQ = String(typeSelect.value) === '1';
      mcqBlock.style.display = isMCQ ? '' : 'none';
      fibBlock.style.display = isMCQ ? 'none' : '';
  }
  typeSelect?.addEventListener('change', syncType);
  syncType();

  function renderLatexToHtmlString(text) {
      if (!window.katex) return text;
      const esc = (s) => s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

      let html = '';
      let rest = text ?? '';
      const blockRe = /\$\$([\s\S]+?)\$\$/g;
      let lastIdx = 0, m;

      while ((m = blockRe.exec(rest)) !== null) {
        const pre = rest.slice(lastIdx, m.index);
        html += '<p>' + esc(pre).replace(/\n{2,}/g,'</p><p>').replace(/\n/g,'<br>') + '</p>';
        try {
          html += '<div class="math-block">' + katex.renderToString(m[1], {throwOnError:false, displayMode:true}) + '</div>';
      } catch { html += '<pre class="text-danger">[math error]</pre>'; }
      lastIdx = m.index + m[0].length;
  }
  const tail = rest.slice(lastIdx);

  const inlineRe = /\\\((.+?)\\\)/g;
  let inlineHtml = '';
  lastIdx = 0;
  let mi;
  while ((mi = inlineRe.exec(tail)) !== null) {
    const pre = tail.slice(lastIdx, mi.index);
    inlineHtml += esc(pre);
    try {
      inlineHtml += katex.renderToString(mi[1], {throwOnError:false, displayMode:false});
  } catch { inlineHtml += '<span class="text-danger">[math error]</span>'; }
  lastIdx = mi.index + mi[0].length;
}
inlineHtml += esc(tail.slice(lastIdx));

if (inlineHtml.trim().length) {
    inlineHtml = '<p>' + inlineHtml.replace(/\n{2,}/g,'</p><p>').replace(/\n/g,'<br>') + '</p>';
}
html += inlineHtml;
html = html.replace(/<p>\s*<\/p>/g,'');
return html || '';
}

function renderInto(el, latexSrc) {
  el.innerHTML = renderLatexToHtmlString(latexSrc);
}

function bindPreview(srcEl, previewEl) {
  if (!srcEl || !previewEl) return;
  const upd = () => renderInto(previewEl, srcEl.value);
  srcEl.addEventListener('input', upd);
  upd();
}

bindPreview(qSource, qPreview);
ansSources.forEach((ta, i) => bindPreview(ta, ansPreviews[i]));

function syncCorrectHidden() {
  const picked = corrRadios.find(r => r.checked);
  if (corrHidden) corrHidden.value = picked ? picked.value : '';
}
corrRadios.forEach(r => r.addEventListener('change', syncCorrectHidden));
syncCorrectHidden();

editForm.addEventListener('submit', () => {
  if (qHidden) qHidden.value = renderLatexToHtmlString(qSource.value);
  const isMCQ = String(typeSelect.value) === '1';
  if (isMCQ) {
    ansSources.forEach((ta, i) => {
      if (ansHidden[i]) ansHidden[i].value = renderLatexToHtmlString(ta.value);
  });
    syncCorrectHidden();
} else {
    if (fibHidden) fibHidden.value = (fibSource?.value ?? '').trim();
}
});
}

  // QA endpoints
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
    assign: (id) => fetch(`/admin/qa/questions/${id}/assign`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
}).then(handleJson),
    notes: (id, notes) => fetch(`/admin/qa/questions/${id}/notes`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ notes }),
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
window.assignToMe = (id) => {
    QA.assign(id).then(d => {
      toast(d.message || (d.success ? 'Assigned' : 'Failed'), d.success ? 'success' : 'error');
      if (d.success) setTimeout(() => location.reload(), 400);
  });
};
window.saveNotes = (id) => {
    const notes = document.getElementById('qaNotes')?.value || '';
    QA.notes(id, notes).then(d => {
      toast(d.message || (d.success ? 'Notes saved' : 'Failed'), d.success ? 'success' : 'error');
      if (d.success) setTimeout(() => location.reload(), 400);
  });
};

  // Keyboard shortcuts (disabled in edit mode to avoid conflicts with typing)
  const isEditMode = {{ $editMode ? 'true' : 'false' }};
  if (!isEditMode) {
    document.addEventListener('keydown', (e) => {
      if (e.target && ['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
      const id = {{ (int)$question->id }};
      const k = e.key.toLowerCase();
      if (k === 'a') window.setStatus(id, 'approved');
      if (k === 'r') window.needsRevision(id);
      if (k === 'f') window.flagWithReason(id);
      if (k === 'u') window.setStatus(id, 'unreviewed');
      if (k === 'm') window.setStatus(id, 'ai_generated');
      if (k === 's') window.saveNotes(id);
      if (k === 'n') window.location.href = "{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}";
  });
}
});
</script>
@endpush
