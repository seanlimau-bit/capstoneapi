@extends('layouts.admin')

@section('title', 'View Question #' . $question->id)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css">
<link rel="stylesheet" href="{{ ('/css/admin-question.css') }}">
<style>
  /* Make display math not force big line breaks */
  .katex-display { display: inline-block; margin: 0; }
  .katex-display > .katex { display: inline-block; }

  /* Small UX polish */
  .inline-add-box { display:none; background:#f8f9fa; border:1px dashed #ced4da; border-radius:.5rem; padding:1rem; }
  .inline-actions { gap:.5rem; }
  .editable-field .edit-icon { opacity:.5; }
  .editable-field:hover .edit-icon { opacity:1; }
  .mcq-option { border:1px solid #dee2e6; border-radius:.5rem; padding:.75rem; display:flex; gap:1rem; align-items:flex-start; }
  .mcq-option-label { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; background:#e9ecef; position:relative; }
  .image-preview-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1055; }
  .image-preview-content { max-width:700px; margin:4rem auto; background:#fff; padding:1rem 1.25rem; border-radius:.5rem; box-shadow:0 1rem 3rem rgba(0,0,0,.2); }
  .preview-image { max-width:100%; height:auto; border-radius:.25rem; }
  .file-info { font-size:.875rem; color:#6c757d; margin:.75rem 0; }
  .card .answer-image, .card .question-image { max-width: 100%; height: auto; }
  .card-header .card-title { display: flex; align-items: center; gap: .5rem; }

  /* Rich field shell */
  .rich-field .rich-view { min-height: 42px; cursor: text; }
  .rich-field .rich-edit {
    white-space: pre-wrap;
    min-height: 160px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  }
  .rich-toolbar { display:flex; gap:.5rem; align-items:center; justify-content:space-between; margin-bottom:.25rem; }
  .rich-toolbar .btn { padding: .15rem .5rem; }
  .sentinel-bg {
    background-color: #fff8e1; /* pale amber */
  }

  .dark-mode .sentinel-bg {
    background-color: #3b2f00; /* deeper tone for dark themes if needed */
  }

</style>
@endpush

@section('content')
<div class="container-fluid {{ $question->is_diagnostic ? 'sentinel-bg' : '' }}" data-question-id="{{ $question->id }}">
  {{-- Page Header --}}
  @include('admin.components.page-header', [
  'title' => 'View Question' . ($question->is_diagnostic ? ' ⚡' : ''),
  'subtitle' => 
  'Question ID: ' . $question->id .
  ' | Type: ' . ($question->type->type ?? 'Unknown') .
  ($question->is_diagnostic ? ' | ' .
  '<span class="badge bg-warning text-dark ms-2" title="Diagnostic sentinel">SENTINEL</span>' : ''),
  'breadcrumbs' => [
  ['title' => 'Dashboard', 'url' => url('/admin')],
  ['title' => 'Questions', 'url' => route('admin.questions.index')],
  ['title' => 'View Question']
  ],
  'actions' => [
  ['text' => 'QA Review', 'url' => route('admin.qa.questions.review', $question), 'icon' => 'clipboard-check', 'style' => 'warning'],
  ['text' => 'Duplicate Question', 'onclick' => 'duplicateQuestion(' . $question->id . ')', 'icon' => 'copy', 'style' => 'info'],
  ['text' => 'Delete Question', 'onclick' => 'deleteQuestion(' . $question->id . ')', 'icon' => 'trash', 'style' => 'danger']
  ]
  ])


  <div class="row gy-4">
    {{-- MAIN --}}
    <div class="col-lg-8">
      {{-- Question Content --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-question-circle me-2"></i>Question Content
            <span class="badge bg-{{ $question->type_id == 1 ? 'primary' : 'success' }} ms-2">
              {{ $question->type->type ?? 'Unknown Type' }}
            </span>
          </h5>
        </div>
        <div class="card-body">

          {{-- Question Image --}}
          <div class="mb-4" id="question-image-section">
            <label class="form-label text-muted small">QUESTION IMAGE</label>
            @if($question->question_image)
            <div class="image-container">
              <div class="image-wrapper">
                <img src="{{ Storage::url($question->question_image) }}" alt="Question Image" class="question-image">
                <div class="image-overlay">
                  <button class="btn btn-light btn-sm" onclick="changeQuestionImage({{ $question->id }})" title="Change Image"><i class="fas fa-edit"></i></button>
                  <button class="btn btn-danger btn-sm" onclick="removeQuestionImage({{ $question->id }})" title="Remove Image"><i class="fas fa-trash"></i></button>
                </div>
              </div>
            </div>
            @else
            <div class="upload-area" onclick="addQuestionImage({{ $question->id }})">
              <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
              <h5 class="text-muted mb-2">Upload Question Image</h5>
              <p class="text-muted small mb-3">Click to browse or drag and drop</p>
              <p class="text-muted small">Supports: JPG, PNG, GIF, WebP (Max 6MB)</p>
            </div>
            @endif
          </div>

          {{-- QUESTION TEXT (HTML + KaTeX) --}}
          <div class="mb-3 rich-field" data-id="{{ $question->id }}" data-field="question">
            <div class="rich-toolbar">
              <label class="form-label text-muted small m-0">QUESTION TEXT</label>
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-secondary rich-toggle" type="button">
                  <i class="fas fa-pencil me-1"></i>Edit
                </button>
                @if($question->type->type != "MCQ")
                <button class="btn btn-sm btn-outline-primary" id="fib-add-blank" type="button">
                  <i class="fas fa-plus me-1"></i>Add Blank
                </button>
                <button class="btn btn-sm btn-outline-danger" id="fib-remove-blank" type="button">
                  <i class="fas fa-minus me-1"></i>Remove Last Blank
                </button>
                <small class="text-muted">Blanks: <span id="fib-blank-count">0</span> / 4</small>
                @endif
              </div>
            </div>
            <div class="rich-view question-field border rounded p-2">
              <div class="rich-content fib-content">{!! $question->question !!}</div>
            </div>
            <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $question->question !!}</textarea>
            <small class="text-muted">Supports HTML and KaTeX.</small>
          </div>

          {{-- ANSWERS --}}
          @if((int)$question->type_id === 1)
          {{-- ===== Type 1: MCQ with answer images ===== --}}
          @php
          $options = [
          ['label'=>'A','key'=>'answer0','index'=>0],
          ['label'=>'B','key'=>'answer1','index'=>1],
          ['label'=>'C','key'=>'answer2','index'=>2],
          ['label'=>'D','key'=>'answer3','index'=>3],
          ['label'=>'E','key'=>'answer4','index'=>4],
          ];
          @endphp

          <div class="mb-4">
            <label class="form-label text-muted small">MULTIPLE CHOICE OPTIONS</label>

            @foreach($options as $o)
            @php
            $val = $question->{$o['key']} ?? '';
            $img = $question->{$o['key'].'_image'} ?? null;
            $isCorrect = (string)$question->correct_answer === (string)$o['index'];
            @endphp

            <div class="mcq-option {{ $isCorrect ? 'border-success' : '' }}" data-option-index="{{ $o['index'] }}">
              <div class="mcq-option-label {{ $isCorrect ? 'bg-success text-white' : 'bg-light' }}">
                {{ $o['label'] }}
                @if($isCorrect)
                <i class="fas fa-check position-absolute small" style="top:3px;right:3px;"></i>
                @endif
              </div>

              <div class="flex-grow-1">
                {{-- Rich text / KaTeX --}}
                <div class="rich-field" data-id="{{ $question->id }}" data-field="{{ $o['key'] }}">
                  <div class="rich-toolbar">
                    <button class="btn btn-sm btn-outline-secondary rich-toggle"><i class="fas fa-pencil me-1"></i>Edit</button>
                  </div>
                  <div class="rich-view editable-field">
                    <div class="rich-content math-render">{!! $val !!}</div>
                  </div>
                  <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $val !!}</textarea>
                </div>

                {{-- Optional image --}}
                @if($img)
                <div class="mt-2 image-wrapper-small">
                  <img src="{{ Storage::url($img) }}" class="answer-image" alt="Option {{ $o['label'] }}">
                  <div class="image-overlay-small">
                    <button class="btn btn-light btn-sm" onclick="changeAnswerImage({{ $question->id }}, {{ $o['index'] }})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="removeAnswerImage({{ $question->id }}, {{ $o['index'] }})"><i class="fas fa-trash"></i></button>
                  </div>
                </div>
                @else
                <div class="upload-area-small mt-2" onclick="addAnswerImage({{ $question->id }}, {{ $o['index'] }})">
                  <i class="fas fa-plus me-1"></i>Add Image for {{ $o['label'] }}
                </div>
                @endif
              </div>
            </div>
            @endforeach

            {{-- Correct answer dropdown --}}
            <div class="mt-3">
              <label class="form-label text-muted small">CORRECT ANSWER</label>
              <select class="form-select form-select-sm correct-answer-selector"
              data-field="correct_answer"
              data-id="{{ $question->id }}"
              data-current="{{ $question->correct_answer }}">
              <option value="">Select...</option>
              @foreach($options as $o)
              <option value="{{ $o['index'] }}" {{ $isCorrect ? 'selected' : '' }}>
                Option {{ $o['label'] }}
              </option>
              @endforeach
            </select>
          </div>
        </div>

        @elseif((int)$question->type_id === 2)
        {{-- ===== Type 2: FIB – text only, no images or correct_answer ===== --}}

        <div class="mb-4">
          <label class="form-label text-muted small">EXPECTED ANSWERS</label>

          @for($i = 0; $i < 4; $i++)
          @php $val = $question->{'answer'.$i} ?? ''; @endphp
          @if ($val)
          <div class="rich-field mb-2" data-id="{{ $question->id }}" data-field="answer{{ $i }}">
            <div class="rich-toolbar">
              <button class="btn btn-sm btn-outline-secondary rich-toggle"><i class="fas fa-pencil me-1"></i>Edit</button>
            </div>
            <div class="rich-view editable-field">
              <div class="rich-content math-render">{!! $val !!}</div>
            </div>
            <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $val !!}</textarea>
          </div>
          @endif
          @endfor
        </div>
        @endif

        {{-- Settings --}}
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label text-muted small">QA STATUS</label>
            <select class="form-select form-select-sm qa-status-selector"
            data-field="qa_status"
            data-id="{{ $question->id }}"
            data-current="{{ $question->qa_status }}">
            <option value="">Select status...</option>
            @foreach($qaStatuses as $status)
            <option value="{{ $status['value'] }}" {{ $status['value'] == ($question->qa_status ?? '') ? 'selected' : '' }}>
              {{ $status['label'] }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label text-muted small">DIFFICULTY</label>
          <select class="form-select form-select-sm"
          data-field="difficulty_id"
          data-id="{{ $question->id }}"
          data-current="{{ $question->difficulty_id }}">
          <option value="">Select difficulty...</option>
          @foreach($difficulties as $difficulty)
          <option value="{{ $difficulty->id }}" {{ $difficulty->id == ($question->difficulty_id ?? '') ? 'selected' : '' }}>
            {{ $difficulty->short_description }}
          </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label text-muted small">TYPE</label>
        <select class="form-select form-select-sm"
        data-field="type_id"
        data-id="{{ $question->id }}"
        data-current="{{ $question->type_id }}">
        <option value="">Select type...</option>
        @foreach($types as $type)
        <option value="{{ $type->id }}" {{ $type->id == ($question->type_id ?? '') ? 'selected' : '' }}>
          {{ $type->type }}
        </option>
        @endforeach
      </select>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label text-muted small">STATUS</label>
      <select class="form-select form-select-sm"
      data-field="status_id"
      data-id="{{ $question->id }}"
      data-current="{{ $question->status_id }}">
      @foreach($statuses as $st)
      <option value="{{ $st->id }}" {{ (int)$st->id === (int)$question->status_id ? 'selected' : '' }}>
        {{ $st->status }}
      </option>
      @endforeach
    </select>
  </div>
</div>

</div>
</div>

{{-- Explanation / Hints / Solutions --}}
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Explanation, Hints & Solutions</h5>
  </div>
  <div class="card-body">
    {{-- Explanation --}}
    <div class="mb-4">
      <div class="rich-field" data-id="{{ $question->id }}" data-field="explanation">
        <div class="rich-toolbar">
          <label class="form-label text-muted small m-0">EXPLANATION</label>
          <button class="btn btn-sm btn-outline-secondary rich-toggle" type="button"><i class="fas fa-pencil me-1"></i> Edit</button>
        </div>
        <div class="rich-view editable-field">
          <div class="rich-content math-render">{!! $question->explanation !!}</div>
        </div>
        <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $question->explanation !!}</textarea>
      </div>
    </div>

    {{-- Hints --}}
    <div class="mb-4">
      <label class="form-label text-muted small">HINTS</label>
      @if($question->hints && $question->hints->count() > 0)
      @foreach($question->hints->sortBy('hint_level') as $hint)
      <div class="mb-2 p-2 border-start border-3 border-info bg-light" data-hint-id="{{ $hint->id }}">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-info">Level</span>
            <input type="number" class="form-control form-control-sm"
            style="width: 90px"
            value="{{ $hint->hint_level }}"
            data-hint-id="{{ $hint->id }}"
            onblur="updateHintLevel(this)">
          </div>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteHint({{ $hint->id }})" title="Delete hint"><i class="fas fa-times"></i></button>
        </div>

        <div class="rich-field" data-id="{{ $question->id }}" data-field="hint_text" data-hint-id="{{ $hint->id }}">
          <div class="rich-toolbar">
            <small class="text-muted">Hint Text</small>
            <button class="btn btn-sm btn-outline-secondary rich-toggle" type="button"><i class="fas fa-pencil me-1"></i> Edit</button>
          </div>
          <div class="rich-view editable-field">
            <div class="rich-content math-render">{!! $hint->hint_text !!}</div>
          </div>
          <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $hint->hint_text !!}</textarea>
        </div>

        @if($hint->user)
        <small class="text-muted">Added by {{ $hint->user->name }}</small>
        @endif
      </div>
      @endforeach
      @else
      <p class="text-muted">No hints available</p>
      @endif

      {{-- Add hint --}}
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
            <textarea class="form-control form-control-sm" id="new-hint-text" rows="3" placeholder="Short, progressive hint (HTML/KaTeX allowed)..."></textarea>
          </div>
        </div>
        <div class="d-flex justify-content-end mt-2 inline-actions">
          <button class="btn btn-sm btn-secondary" id="cancel-add-hint">Cancel</button>
          <button class="btn btn-sm btn-primary" id="save-new-hint">Save Hint</button>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-primary mt-2" id="toggle-add-hint"><i class="fas fa-plus me-1"></i>Add Hint</button>
    </div>

    {{-- Solutions --}}
    <div class="mb-2">
      <label class="form-label text-muted small">SOLUTIONS</label>
      @if($question->solutions && $question->solutions->count() > 0)
      @foreach($question->solutions as $solution)
      <div class="solution-item mb-3 p-3 border rounded bg-light">
        <div class="rich-field" data-id="{{ $question->id }}" data-field="solution" data-solution-id="{{ $solution->id }}">
          <div class="rich-toolbar">
            <small class="text-muted">Solution</small>
            <button class="btn btn-sm btn-outline-secondary rich-toggle" type="button"><i class="fas fa-pencil me-1"></i> Source</button>
          </div>
          <div class="rich-view editable-field">
            <div class="rich-content math-render">{!! $solution->solution !!}</div>
          </div>
          <textarea class="rich-edit form-control d-none" spellcheck="false">{!! $solution->solution !!}</textarea>
        </div>

        <div class="mt-2 d-flex justify-content-between align-items-center">
          <small class="text-muted">
            <i class="fas fa-user me-1"></i>
            By {{ $solution->user->name ?? 'Unknown' }} on {{ optional($solution->created_at)->format('M d, Y') }}
          </small>
          <button class="btn btn-sm btn-outline-danger" onclick="deleteSolution({{ $solution->id }})" title="Delete solution"><i class="fas fa-trash"></i></button>
        </div>
      </div>
      @endforeach
      @else
      <p class="text-muted">No solutions available</p>
      @endif

      {{-- Add solution --}}
      <div id="add-solution-box" class="inline-add-box mt-2">
        <div class="mb-2">
          <label class="form-label small">Solution</label>
          <textarea class="form-control form-control-sm" id="new-solution-text" rows="6" placeholder="Type the solution (HTML/KaTeX allowed)..."></textarea>
        </div>
        <div class="d-flex justify-content-end inline-actions">
          <button class="btn btn-sm btn-secondary" id="cancel-add-solution">Cancel</button>
          <button class="btn btn-sm btn-primary" id="save-new-solution">Save Solution</button>
        </div>
      </div>
      <button class="btn btn-sm btn-outline-primary mt-2" id="toggle-add-solution"><i class="fas fa-plus me-1"></i>Add Solution</button>
    </div>

  </div>
</div>
</div>

{{-- SIDEBAR --}}
<div class="col-lg-4">
  <div class="sticky-top" style="top:84px;">
    {{-- Stats --}}
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-chart-bar me-2"></i>Question Statistics</h5></div>
      <div class="card-body">
        <div class="row text-center">
          <div class="col-4"><h4 class="text-primary">{{ $question->id }}</h4><small class="text-muted">Question ID</small></div>
          <div class="col-4"><h4 class="text-success">0</h4><small class="text-muted">Times Used</small></div>
          <div class="col-4"><h4 class="text-info">0%</h4><small class="text-muted">Accuracy</small></div>
        </div>
      </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card">
      <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5></div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('admin.qa.questions.review', $question) }}" class="btn btn-warning"><i class="fas fa-clipboard-check me-1"></i>QA Review</a>
          <button class="btn btn-info" onclick="duplicateQuestion({{ $question->id }})"><i class="fas fa-copy me-1"></i>Duplicate Question</button>
          <button class="btn btn-success" onclick="previewQuestion({{ $question->id }})"><i class="fas fa-eye me-1"></i>Preview Question</button>
          <button class="btn btn-danger" onclick="deleteQuestion({{ $question->id }})"><i class="fas fa-trash me-1"></i>Delete Question</button>
        </div>
        @include('admin.components.math-help')
      </div>
    </div>

    {{-- Skill --}}
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-brain me-2"></i>Associated Skill</h5></div>
      <div class="card-body">
        @if($question->skill)
        <div class="d-flex align-items-start">
          @if($question->skill->image)
          <img src="{{ asset($question->skill->image) }}" alt="{{ $question->skill->skill }}" class="rounded me-3" width="60" height="60" style="object-fit:cover;">
          @endif
          <div class="flex-grow-1">
            <h6 class="mb-1 d-flex align-items-center justify-content-between">
              <span>{{ $question->skill->skill }}</span>
              <a href="{{ route('admin.skills.show', $question->skill) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye me-1"></i>View Skill</a>
            </h6>
            <p class="text-muted small mb-2">
              {{ strlen($question->skill->description) > 100 ? substr($question->skill->description, 0, 100) . '...' : $question->skill->description }}
            </p>
            <div class="skill-change-box border border-success rounded p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div><i class="fas fa-exchange-alt text-success me-2"></i><span class="fw-semibold text-success">Change Skill</span></div>
                <div class="skill-dropdown" style="min-width:200px;">
                  <select class="form-select form-select-sm"
                  data-field="skill_id"
                  data-id="{{ $question->id }}"
                  data-current="{{ $question->skill_id ?? '' }}">
                  <option value="">Select a skill...</option>
                  @foreach($skills as $skillOption)
                  <option value="{{ $skillOption->id }}" {{ $skillOption->id == ($question->skill_id ?? '') ? 'selected' : '' }}>
                    {{ $skillOption->id . ': ' . $skillOption->skill }}
                  </option>
                  @endforeach
                </select>
              </div>
            </div>
            <small class="text-muted mt-2 d-block">Select a new skill to assign to this question</small>
          </div>
        </div>
      </div>
      @else
      <div class="text-center py-3">
        <i class="fas fa-unlink fa-2x text-muted mb-2"></i>
        <p class="text-muted">No skill associated</p>
      </div>
      @endif
    </div>
  </div>

  {{-- QA Issues --}}
  <div class="card mb-4" id="qa-issues-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0"><i class="fas fa-bug me-2"></i>QA Issues</h5>
      <span class="badge bg-secondary">{{ $question->qaIssues->count() }}</span>
    </div>
    <div class="card-body">
      @if($question->qaIssues->isEmpty())
      <p class="text-muted mb-0">No QA issues recorded for this question.</p>
      @else
      <div class="list-group small">
        @foreach($question->qaIssues as $issue)
        <div class="list-group-item d-flex justify-content-between align-items-start flex-wrap" data-issue-id="{{ $issue->id }}">
          <div class="me-2 flex-grow-1">
            <span class="badge bg-primary text-light me-2 text-capitalize">{{ $issue->status }}</span>
            <span class="badge bg-info text-dark me-2 text-capitalize">{{ str_replace('_',' ', $issue->issue_type) }}</span>
            <span class="fw-semibold">{{ $issue->description }}</span><br>
            <small class="text-muted">
              Reported by {{ optional($issue->reviewer)->name ?? 'Reviewer '.$issue->reviewer_id }}
              on {{ optional($issue->created_at)->format('M j, Y') }}
            </small>
          </div>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm {{ $issue->status === 'resolved' ? 'btn-success' : 'btn-outline-success' }}"
              title="Mark as resolved" onclick="updateQaIssueStatus({{ $issue->id }}, 'resolved', this)">
              <i class="fas fa-check"></i>
            </button>
            <button type="button" class="btn btn-sm {{ $issue->status === 'dismissed' ? 'btn-danger' : 'btn-outline-danger' }}"
              title="Dismiss issue" onclick="updateQaIssueStatus({{ $issue->id }}, 'dismissed', this)">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        @endforeach
      </div>
      @endif
    </div>
  </div>
</div>
</div>
</div>
</div>

{{-- Image Preview Modal --}}
<div id="imagePreviewModal" class="image-preview-modal">
  <div class="image-preview-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0" id="previewTitle">Image Preview</h5>
      <button type="button" class="btn-close" onclick="closeImagePreview()"></button>
    </div>
    <div class="text-center"><img id="previewImage" class="preview-image" src="" alt="Preview"></div>
    <div id="fileInfo" class="file-info"></div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-success flex-fill" onclick="confirmImageUpload()"><i class="fas fa-upload me-1"></i>Upload Image</button>
      <button type="button" class="btn btn-secondary" onclick="closeImagePreview()">Cancel</button>
    </div>
  </div>
</div>

<input type="file" id="answerImageInput" style="display:none" accept="image/*">
<input type="file" id="questionImageInput" style="display:none" accept="image/*">
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/contrib/auto-render.min.js"></script>
<script>
  // call your base layout's renderer (scans .question-field, .editable-field, .fib-content, .mcq-option, .math-render)
  function renderAllKaTeXLocally(){ if (typeof renderKaTeX === 'function') renderKaTeX(); }
</script>

<script>
  /** ROUTES **/
  const QUESTION_ID = {{ $question->id }};
  const ROUTES = {
    hints: { store: @json(route('admin.hints.store')), one: (id)=> @json(url('admin/hints')) + '/' + id },
    solutions: { store: @json(route('admin.solutions.store')), one: (id)=> @json(url('admin/solutions')) + '/' + id }
  };

  document.addEventListener('DOMContentLoaded', ()=>{
    setupImageInputs();
    initRichFields();
    initSelectors();
    initHints();
    initSolutions();
    initCorrectAnswerSelector();
    initBlankButtons();
    renderAllKaTeXLocally();
  });

  /** Toast **/
  function showToast(message, type='info'){
    const cls = type==='success'?'alert-success':type==='error'?'alert-danger':'alert-info';
    const el = document.createElement('div');
    el.className = `alert ${cls} alert-dismissible fade show position-fixed`;
    el.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
    el.innerHTML = `${message}<button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>`;
    document.body.appendChild(el);
    setTimeout(()=> el.remove(), 4000);
  }

  /** Update helper **/
  async function updateQuestionField(fieldName, value, csrf){
    const res = await fetch(`/admin/questions/${QUESTION_ID}/update-field`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
      body: JSON.stringify({ field: fieldName, value, _method: 'PATCH' })
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Update failed');
    return data;
  }

  /** Rich fields controller (textarea source editor) **/
  function debounce(fn, ms=600){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

  async function patchField({ scope, field, value, ids }){
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    if (scope==='hint'){
      const res = await fetch(ROUTES.hints.one(ids.hintId), {
        method: 'PATCH',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ [field]: value })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Update failed');
      return data;
    }
    if (scope==='solution'){
      const res = await fetch(ROUTES.solutions.one(ids.solutionId), {
        method: 'PATCH',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ [field]: value })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Update failed');
      return data;
    }
    return updateQuestionField(field, value, csrf);
  }

  function initRichFields(){
    document.querySelectorAll('.rich-field').forEach(box=>{
      const questionId = +box.dataset.id;
      const field      = box.dataset.field;
      const hintId     = box.dataset.hintId;
      const solutionId = box.dataset.solutionId;

      const scope = hintId ? 'hint' : solutionId ? 'solution' : 'question';

      const view = box.querySelector('.rich-view');
      const edit = box.querySelector('.rich-edit'); // TEXTAREA
      const contentEl = box.querySelector('.rich-content');
      const toggleBtn = box.querySelector('.rich-toggle');

      toggleBtn?.addEventListener('click', ()=>{
        const showingSource = !edit.classList.contains('d-none');
        if (showingSource){
          edit.classList.add('d-none');
          view.classList.remove('d-none');
          renderAllKaTeXLocally();
          view.focus();
        } else {
          edit.value = contentEl.innerHTML.trim();
          view.classList.add('d-none');
          edit.classList.remove('d-none');
          placeCaretEnd(edit);
        }
      });

      const push = debounce(async ()=>{
        try{
          await patchField({ scope, field, value: edit.value, ids: {questionId, hintId, solutionId} });
          contentEl.innerHTML = edit.value;
          renderAllKaTeXLocally();
        } catch(e){ console.error(e); }
      }, 700);

      edit.addEventListener('input', push);
      edit.addEventListener('keydown', e=>{
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter'){ e.preventDefault(); edit.blur(); }
        else if (e.key === 'Escape'){ edit.classList.add('d-none'); view.classList.remove('d-none'); renderAllKaTeXLocally(); view.focus(); }
      });
      edit.addEventListener('blur', async ()=>{
        try{
          await patchField({ scope, field, value: edit.value, ids: {questionId, hintId, solutionId} });
          contentEl.innerHTML = edit.value;
          renderAllKaTeXLocally();
          showToast('Saved', 'success');
        } catch(e){}
        edit.classList.add('d-none'); view.classList.remove('d-none');
      });
    });
  }

  function placeCaretEnd(textarea){
    const len = textarea.value.length;
    textarea.setSelectionRange(len, len);
    textarea.focus();
  }

  /** Selectors (qa_status, difficulty, type, status, skill) **/
  function initSelectors(){
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    document.querySelectorAll('select[data-field]').forEach(sel=>{
      sel.addEventListener('change', async e=>{
        const fieldName = e.target.dataset.field;
        const value = e.target.value;
        const prev = e.target.dataset.current;
        try{
          await updateQuestionField(fieldName, value, csrf);
          e.target.dataset.current = value;
          e.target.style.borderColor = '#198754';
          setTimeout(()=> e.target.style.borderColor = '', 1200);
          showToast('Updated', 'success');
        }catch(err){
          e.target.value = prev ?? '';
          showToast(err.message || 'Update failed', 'error');
        }
      });
    });
  }

  /** Correct answer selector **/
  function initCorrectAnswerSelector(){
    const sel = document.querySelector('.correct-answer-selector');
    if(!sel) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    sel.addEventListener('change', async e=>{
      const value = e.target.value;
      const prev = e.target.dataset.current;
      try{
        await updateQuestionField('correct_answer', value, csrf);
        e.target.dataset.current = value;

        // reset labels
        document.querySelectorAll('.mcq-option-label').forEach(lab=>{
          lab.classList.remove('bg-success','text-white');
          lab.querySelector('.fa-check')?.remove();
        });

        const idx = +value;
        if(!Number.isNaN(idx)){
          const label = document.querySelector(`.mcq-option[data-option-index="${idx}"] .mcq-option-label`);
          if(label){
            label.classList.add('bg-success','text-white');
            const i = document.createElement('i');
            i.className = 'fas fa-check position-absolute';
            i.style.cssText = 'font-size:10px;top:2px;right:2px;';
            label.appendChild(i);
          }
        }
        showToast('Correct answer set', 'success');
      }catch(err){
        e.target.value = prev ?? '';
        showToast(err.message || 'Failed to set correct answer', 'error');
      }
    });
  }

  /** Hints **/
  function initHints(){
    const toggle = document.getElementById('toggle-add-hint');
    const box = document.getElementById('add-hint-box');
    if(toggle && box){
      toggle.addEventListener('click', ()=> box.style.display = (box.style.display==='block'?'none':'block'));
      document.getElementById('cancel-add-hint').addEventListener('click', ()=>{
        box.style.display='none'; document.getElementById('new-hint-text').value=''; document.getElementById('new-hint-level').value='1';
      });
      document.getElementById('save-new-hint').addEventListener('click', saveNewHint);
    }
  }
  async function saveNewHint(){
    const text = document.getElementById('new-hint-text').value.trim();
    const level = +document.getElementById('new-hint-level').value || 1;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    if(!text){ showToast('Hint text required', 'error'); return; }
    try{
      const res = await fetch(ROUTES.hints.store, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ question_id: QUESTION_ID, hint_level: level, hint_text: text })
      });
      const data = await res.json();
      if(!res.ok) throw new Error(data.message || 'Failed to save hint');
      showToast('Hint added', 'success'); location.reload();
    }catch(e){ showToast(e.message || 'Error adding hint', 'error'); }
  }
  async function updateHintLevel(inp){
    const id = inp.dataset.hintId; const val = +inp.value || 1;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    try{
      const res = await fetch(ROUTES.hints.one(id), {
        method: 'PATCH',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ hint_level: val })
      });
      const data = await res.json();
      if(!res.ok) throw new Error(data.message || 'Update failed');
      showToast('Hint level updated', 'success');
    }catch(e){ showToast(e.message || 'Failed to update hint level', 'error'); }
  }
  async function deleteHint(id){
    if(!confirm('Delete this hint?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    try{
      const res = await fetch(ROUTES.hints.one(id), { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
      const data = await res.json();
      if(!res.ok) throw new Error(data.message || 'Failed to delete hint');
      showToast('Hint deleted', 'success'); location.reload();
    }catch(e){ showToast(e.message || 'Error deleting hint', 'error'); }
  }

  /** Solutions **/
  function initSolutions(){
    const toggle = document.getElementById('toggle-add-solution');
    const box = document.getElementById('add-solution-box');
    if(toggle && box){
      toggle.addEventListener('click', ()=> box.style.display = (box.style.display==='block'?'none':'block'));
      document.getElementById('cancel-add-solution').addEventListener('click', ()=>{ box.style.display='none'; document.getElementById('new-solution-text').value=''; });
      document.getElementById('save-new-solution').addEventListener('click', saveNewSolution);
    }
  }
  async function saveNewSolution(){
    const text = document.getElementById('new-solution-text').value.trim();
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    if(!text){ showToast('Solution text required', 'error'); return; }
    try{
      const res = await fetch(ROUTES.solutions.store, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ question_id: QUESTION_ID, solution: text })
      });
      const data = await res.json();
      if(!res.ok) throw new Error(data.message || 'Failed to save solution');
      showToast('Solution saved', 'success'); location.reload();
    }catch(e){ showToast(e.message || 'Error saving solution', 'error'); }
  }
  async function deleteSolution(id){
    if(!confirm('Delete this solution?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    try{
      const res = await fetch(ROUTES.solutions.one(id), { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
      const data = await res.json();
      if(!res.ok) throw new Error(data.message || 'Failed to delete solution');
      showToast('Solution deleted', 'success'); location.reload();
    }catch(e){ showToast(e.message || 'Error deleting solution', 'error'); }
  }

  /** QA issue status **/
  async function updateQaIssueStatus(issueId, newStatus, btnEl){
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    try{
      const res = await fetch(`/admin/qa/issues/${issueId}/status`, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json'},
        body: JSON.stringify({ status: newStatus })
      });
      const data = await res.json();
      if(!res.ok || !data.success) throw new Error(data.message || 'Update failed');
      showToast('Issue status updated', 'success');

      const row = btnEl.closest('[data-issue-id]');
      if (!row) return;
      row.querySelectorAll('button').forEach(b=>{
        const isResolve = b.title.includes('resolved');
        b.classList.remove('btn-success','btn-outline-success','btn-danger','btn-outline-danger');
        b.classList.add(isResolve ? (newStatus==='resolved'?'btn-success':'btn-outline-success')
          : (newStatus==='dismissed'?'btn-danger':'btn-outline-danger'));
      });
      row.querySelector('.badge.bg-primary').textContent = newStatus;
    }catch(err){ showToast(err.message || 'Failed to update issue', 'error'); }
  }
  window.updateQaIssueStatus = updateQaIssueStatus;

  /** Images **/
  var currentImageContext = null;
  function setupImageInputs(){
    const qIn = document.getElementById('questionImageInput');
    qIn.addEventListener('change', async function(e){
      const file = e.target.files?.[0]; if(!file) return;
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const fd = new FormData(); fd.append('image', file); fd.append('type','question_image'); fd.append('question_id', String(QUESTION_ID));
      try{
        const r = await fetch('/admin/upload/image', { method:'POST', body:fd, headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
        const data = await r.json();
        if(r.ok && data.success){ showToast('Image uploaded', 'success'); setTimeout(()=> location.reload(), 800); }
        else showToast(data.message || 'Upload failed', 'error');
      }catch{ showToast('Upload failed', 'error'); } finally { e.target.value=''; }
    });

    const aIn = document.getElementById('answerImageInput');
    aIn.addEventListener('change', async function(e){
      const file = e.target.files?.[0]; if(!file) return;
      if(!currentImageContext){ e.target.value=''; return; }
      const { questionId, optionIndex } = currentImageContext;
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const fd = new FormData();
      fd.append('image', file); fd.append('type','answer_image'); fd.append('question_id', String(questionId)); fd.append('option', String(optionIndex));
      try{
        const r = await fetch('/admin/upload/image', { method:'POST', body:fd, headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, credentials:'same-origin' });
        const data = await r.json();
        if(r.ok && data.success){ showToast(`Option ${['A','B','C','D','E'][optionIndex]} image uploaded`, 'success'); setTimeout(()=> location.reload(), 800); }
        else showToast(data.message || 'Upload failed', 'error');
      }catch{ showToast('Upload failed', 'error'); }
      finally { currentImageContext = null; e.target.value=''; }
    });
  }
  function addQuestionImage(){ document.getElementById('questionImageInput').click(); }
  function changeQuestionImage(){ document.getElementById('questionImageInput').click(); }
  function addAnswerImage(qid, idx){ currentImageContext = { questionId: qid, optionIndex: idx }; document.getElementById('answerImageInput').click(); }
  function changeAnswerImage(qid, idx){ currentImageContext = { questionId: qid, optionIndex: idx }; document.getElementById('answerImageInput').click(); }
  async function removeQuestionImage(qid){
    if(!confirm('Remove question image?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/admin/questions/${qid}/image`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json(); if(d.success){ showToast('Image removed', 'success'); setTimeout(()=> location.reload(), 800); } else showToast(d.message || 'Remove failed', 'error');
  }
  async function removeAnswerImage(qid, idx){
    if(!confirm(`Remove Option ${['A','B','C','D','E'][idx]} image?`)) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/admin/questions/${qid}/answers/${idx}/image`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json(); if(d.success){ showToast('Image removed', 'success'); setTimeout(()=> location.reload(), 800); } else showToast(d.message || 'Remove failed', 'error');
  }
  window.addQuestionImage = addQuestionImage;
  window.changeQuestionImage = changeQuestionImage;
  window.addAnswerImage = addAnswerImage;
  window.changeAnswerImage = changeAnswerImage;
  window.removeQuestionImage = removeQuestionImage;
  window.removeAnswerImage = removeAnswerImage;

  /** Question actions **/
  function previewQuestion(id){ window.open(`/admin/questions/${id}/preview`, '_blank', 'width=800,height=600'); }
  async function deleteQuestion(id){
    if(!confirm('Delete this question? This cannot be undone.')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/admin/questions/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if(d.success){ showToast('Question deleted', 'success'); setTimeout(()=> window.location.href = '/admin/questions', 1000); }
    else showToast(d.message || 'Delete failed', 'error');
  }
  async function duplicateQuestion(id){
    if(!confirm('Duplicate this question?')) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const r = await fetch(`/admin/questions/${id}/duplicate`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'} });
    const d = await r.json();
    if(d.success){ showToast('Question duplicated', 'success'); if(d.redirect_url) window.location.href = d.redirect_url; else location.reload(); }
    else showToast(d.message || 'Duplication failed', 'error');
  }
  window.previewQuestion = previewQuestion;
  window.deleteQuestion = deleteQuestion;
  window.duplicateQuestion = duplicateQuestion;

  /** FIB blank helpers operate on QUESTION source textarea **/
  function countBlanks(src){ return (src.match(/\[\?\]|_{3,}|\[blank\]/g) || []).length; }
  function getQuestionRichParts(){
    const box = document.querySelector('.rich-field[data-field="question"]'); if(!box) return {};
    return { box, view: box.querySelector('.rich-view'), edit: box.querySelector('.rich-edit'), contentEl: box.querySelector('.rich-content') };
  }
  function ensureQuestionSourceVisible(){
    const {view, edit, contentEl} = getQuestionRichParts();
    if(!edit) return null;
    if(edit.classList.contains('d-none')){
      edit.value = contentEl.innerHTML.trim();
      view.classList.add('d-none');
      edit.classList.remove('d-none');
      placeCaretEnd(edit);
    }
    return {view, edit, contentEl};
  }
  async function saveQuestionSource(src){
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    await updateQuestionField('question', src, csrf);
    const {edit, contentEl} = getQuestionRichParts();
    if(contentEl){ contentEl.innerHTML = src; renderAllKaTeXLocally(); }
    if(edit) edit.value = src;
    const label = document.getElementById('fib-blank-count'); if(label) label.textContent = String(countBlanks(src));
  }
  function initBlankButtons(){
    const add = document.getElementById('fib-add-blank');
    const rem = document.getElementById('fib-remove-blank');
    if(add) add.addEventListener('click', fibAddBlank);
    if(rem) rem.addEventListener('click', fibRemoveLastBlank);
    const parts = getQuestionRichParts();
    if(parts?.contentEl){
      const n = countBlanks(parts.contentEl.innerHTML || '');
      const label = document.getElementById('fib-blank-count'); if(label) label.textContent = String(n);
    }
  }
  async function fibAddBlank(){
    const parts = ensureQuestionSourceVisible(); if(!parts) return;
    const src = parts.edit.value || '';
    const blanks = countBlanks(src);
    if (blanks >= 4){ showToast('Maximum of 4 blanks supported', 'error'); return; }

    const next = (/\S$/.test(src) ? src + ' ' : src) + '[?]';
    await saveQuestionSource(next);
    showToast('Blank added', 'success');

  // --- highlight the new blank visually ---
  const renderArea = document.querySelector('.fib-content, .question-field');
  if (renderArea){
    // Re-render KaTeX if needed
    if (typeof renderKaTeX === 'function') renderKaTeX();

    // Find the last blank token after render
    const html = renderArea.innerHTML;
    // Wrap the last token with a temporary span
    const highlighted = html.replace(/(\[\?\]|_{3,}|\[blank\])(?!.*(\[\?\]|_{3,}|\[blank\]))/,
      '<span class="new-blank">$1</span>');
    renderArea.innerHTML = highlighted;

    // Pulse the highlight, then fade it out
    const el = renderArea.querySelector('.new-blank');
    if (el){
      el.style.transition = 'background-color 1s ease';
      el.style.backgroundColor = '#fff3cd'; // yellow-ish
      setTimeout(()=> el.style.backgroundColor = '', 800);
      setTimeout(()=> el.classList.remove('new-blank'), 1600);
    }
  }
}

async function fibRemoveLastBlank(){
  const parts = ensureQuestionSourceVisible(); if(!parts) return;
  const src = parts.edit.value || '';
  if(!countBlanks(src)){ showToast('No blanks to remove', 'info'); return; }

  let next = src;
  const rxs = [/\[\?\](?!.*\[\?\])/s, /_{3,}(?!.*_{3,})/s, /\[blank\](?!.*\[blank\])/s];
  for(const rx of rxs){ if(rx.test(next)){ next = next.replace(rx,'').replace(/\s{2,}/g,' ').trim(); break; } }

    await saveQuestionSource(next);
  showToast('Last blank removed', 'success');

  try{
    const before = countBlanks(src);
    const indexToClear = Math.min(before - 1, 3);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    await updateQuestionField(`answer${indexToClear}`, '', csrf);
  }catch(e){}

    // ✨ Refresh to hide the extra answer box
    setTimeout(() => location.reload(), 600);
  }

</script>
@endpush
