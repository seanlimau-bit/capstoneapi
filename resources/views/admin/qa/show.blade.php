@extends('layouts.admin')

@section('title', 'Review Question #' . $question->id)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Review Question #{{ $question->id }}</h2>
            <a href="{{ route('admin.qa.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to QA Dashboard
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Question Status Card --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-2">Current QA Status</h6>
                            @php
                            $qaStatus = $question->qa_status ?? 'unreviewed';
                            $statusConfig = [
                            'unreviewed' => ['color' => 'warning', 'icon' => 'clock', 'text' => 'Unreviewed'],
                            'approved' => ['color' => 'success', 'icon' => 'check-circle', 'text' => 'Approved'],
                            'flagged' => ['color' => 'danger', 'icon' => 'flag', 'text' => 'Flagged'],
                            'needs_revision' => ['color' => 'info', 'icon' => 'edit', 'text' => 'Needs Revision']
                            ];
                            $config = $statusConfig[$qaStatus] ?? $statusConfig['unreviewed'];
                            @endphp
                            <span class="badge bg-{{ $config['color'] }} fs-6 px-3 py-2">
                                <i class="fas fa-{{ $config['icon'] }} me-2"></i>{{ $config['text'] }}
                            </span>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>Created: 
                                {{ $question->created_at ? $question->created_at->format('M d, Y') : 'Unknown' }}
                                <br>
                                @if($question->skill)
                                <i class="fas fa-brain me-1"></i>Skill: {{ $question->skill->skill }}<br>
                                @endif
                                @php
                                $difficulties = ['', 'Easy', 'Medium', 'Hard'];
                                $difficulty = $difficulties[$question->difficulty_id ?? 0] ?? 'Unknown';
                                @endphp
                                <i class="fas fa-signal me-1"></i>Difficulty: {{ $difficulty }}<br>
                                {{--
                                    @if($question->skill && $question->skill->tracks->isNotEmpty() && $question->level())
                                    <i class="fas fa-layer-group me-1"></i>Level: {{ $question->level() }}
                                    @endif --}}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Question Content Card --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Question Content 
                            <span class="badge bg-{{ $question->type_id == 1 ? 'primary' : 'success' }} ms-2">
                                {{ $question->type_id == 1 ? 'Multiple Choice' : 'Fill in the Blank' }}
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Question Image --}}
                        @if($question->question_image)
                        <div class="mb-4">
                            <h6 class="text-muted small mb-2">QUESTION IMAGE</h6>
                            <div class="text-center">
                                <img src="{{ asset($question->question_image) }}" alt="Question Image" 
                                class="img-fluid" style="max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        </div>
                        @endif

                        {{-- Question Text --}}
                        <div class="mb-4">
                            <h6 class="text-muted small mb-2">QUESTION TEXT</h6>
                            <div class="border rounded p-3 bg-light">
                                {!! $question->question ?: '[No question text provided]' !!}
                            </div>
                        </div>

                        {{-- Answer Options for Multiple Choice --}}
                        @if($question->type_id == 1)
                        <div class="mb-4">
                            <h6 class="text-muted small mb-3">ANSWER OPTIONS</h6>
                            @php
                            $answers = [
                            ['text' => $question->answer0 ?? '', 'image' => $question->answer0_image ?? ''],
                            ['text' => $question->answer1 ?? '', 'image' => $question->answer1_image ?? ''],
                            ['text' => $question->answer2 ?? '', 'image' => $question->answer2_image ?? ''],
                            ['text' => $question->answer3 ?? '', 'image' => $question->answer3_image ?? '']
                            ];
                            $correctIndex = $question->correct_answer ?? 0;
                            @endphp
                            
                            @foreach($answers as $index => $answer)
                            @if($answer['text'] || $answer['image'])
                            <div class="border rounded p-3 mb-3 {{ $index === $correctIndex ? 'bg-success bg-opacity-10 border-success' : '' }}">
                                <div class="row align-items-start">
                                    <div class="col-auto">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center position-relative
                                        {{ $index === $correctIndex ? 'bg-success text-white' : 'bg-secondary text-white' }}" 
                                        style="width: 40px; height: 40px; font-weight: bold;">
                                        {{ chr(65 + $index) }}
                                        @if($index === $correctIndex)
                                        <i class="fas fa-check position-absolute" style="font-size: 0.7em; top: 2px; right: 2px;"></i>
                                        @endif
                                    </div>
                                </div>
                                <div class="col">
                                    @if($answer['text'])
                                    <div class="mb-2">{{ $answer['text'] }}</div>
                                    @endif
                                    @if($answer['image'])
                                    <div class="mt-2">
                                        <img src="{{ asset($answer['image']) }}" alt="Answer {{ chr(65 + $index) }} Image" 
                                        class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                    @endif
                                    @if($index === $correctIndex)
                                    <small class="text-success fw-bold"><i class="fas fa-check-circle me-1"></i>Correct Answer</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @else
                    {{-- Fill in the Blank Answer --}}
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

                    {{-- Question Metadata --}}
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <h6 class="text-muted small mb-2">DIFFICULTY</h6>
                            @php
                            $difficultyColors = ['', 'success', 'warning', 'danger'];
                            $difficultyColor = $difficultyColors[$question->difficulty_id ?? 0] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $difficultyColor }}">{{ $difficulty }}</span>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-muted small mb-2">SKILL</h6>
                            @if($question->skill)
                            <span class="badge bg-primary">{{ $question->skill->skill }}</span>
                            @else
                            <span class="text-muted">No skill assigned</span>
                            @endif
                        </div>
                        {{--                        <div class="col-md-3">
                            <h6 class="text-muted small mb-2">LEVEL</h6>
                            @if($question->skill && $question->skill->tracks->isNotEmpty() && $question->level())
                            <span class="badge bg-info">Level {{ $question->level() }}</span>
                            @else
                            <span class="text-muted">No level assigned</span>
                            @endif
                        </div> --}}
                        <div class="col-md-3">
                            <h6 class="text-muted small mb-2">CALCULATOR</h6>
                            <span class="badge bg-secondary">{{ $question->calculator ? ucfirst($question->calculator) : 'None' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            {{-- QA Actions Card --}}
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
                <div class="d-grid gap-2 mb-3">
                  <button class="btn btn-success" onclick="setStatus({{ $question->id }}, 'approved')">
                    <i class="fas fa-check me-1"></i> Approve
                </button>

                <button class="btn btn-info" onclick="needsRevision({{ $question->id }})">
                    <i class="fas fa-edit me-1"></i> Needs Revision…
                </button>

                <button class="btn btn-warning" onclick="flagWithReason({{ $question->id }})">
                    <i class="fas fa-flag me-1"></i> Report Issue…
                </button>

                <button class="btn btn-outline-danger" onclick="setStatus({{ $question->id }}, 'ai_generated')">
                    <i class="fas fa-robot me-1"></i> Mark as AI-generated
                </button>

                <button class="btn btn-outline-secondary" onclick="setStatus({{ $question->id }}, 'unreviewed')">
                    <i class="fas fa-undo me-1"></i> Unreview
                </button>
            </div>

            <div class="d-flex gap-2 mb-3">
              <button class="btn btn-outline-primary flex-grow-1" onclick="assignToMe({{ $question->id }})">
                <i class="fas fa-user-plus me-1"></i> Assign to me
            </button>
            <a class="btn btn-outline-dark" href="{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}">
                <i class="fas fa-forward me-1"></i> Next
            </a>
        </div>

        <label class="form-label fw-semibold">Reviewer Notes</label>
        <textarea id="qaNotes" class="form-control mb-2" rows="3"
        placeholder="Add context for the author/other reviewers…">{{ $question->qa_notes }}</textarea>
        <button class="btn btn-outline-success w-100" onclick="saveNotes({{ $question->id }})">
          <i class="fas fa-save me-1"></i> Save Notes
      </button>

      @if($question->qa_reviewed_at)
      <div class="text-muted small mt-3">
        <i class="fas fa-clock me-1"></i> Reviewed {{ $question->qa_reviewed_at->diffForHumans() }}
    </div>
    @endif
</div>
</div>

</div>
</div>
</div>

<script>
    function approveQuestion(id) {
        if (confirm('Approve this question?')) {
            fetch(`/admin/qa/questions/${id}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    }

    function flagQuestion(id) {
        const reason = prompt('What is the issue with this question?');
        if (reason) {
            fetch(`/admin/qa/questions/${id}/flag`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    issue_type: 'other',
                    description: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
        }
    }
    const QA = {
      status:  (id, status, extra = {}) => fetch(`/admin/qa/questions/${id}/status`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status, ...extra })
  }).then(r => r.json()),

      assign:  (id) => fetch(`/admin/qa/questions/${id}/assign`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(r => r.json()),

      notes:   (id, notes) => fetch(`/admin/qa/questions/${id}/notes`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Content-Type': 'application/json'
      },
      body: JSON.stringify({ notes })
  }).then(r => r.json()),
  };

  function setStatus(id, status){
      QA.status(id, status).then(d => {
        alert(d.message || (d.success ? 'Updated' : 'Failed'));
        if (d.success) location.reload();
    });
  }

  function needsRevision(id){
      const note = prompt('What needs to be changed?');
      if (!note) return;
      QA.status(id, 'needs_revision', { note }).then(d => {
        alert(d.message || (d.success ? 'Marked as needs revision' : 'Failed'));
        if (d.success) location.reload();
    });
  }

  function flagWithReason(id){
      const reason = prompt('Describe the issue');
      if (!reason) return;
  // include an optional type if you want (typo, wrong answer, poor wording, …)
  QA.status(id, 'flagged', { issue_type: 'other', note: reason }).then(d => {
    alert(d.message || (d.success ? 'Flagged' : 'Failed'));
    if (d.success) location.reload();
});
}

function assignToMe(id){
  QA.assign(id).then(d => {
    alert(d.message || (d.success ? 'Assigned' : 'Failed'));
    if (d.success) location.reload();
});
}

function saveNotes(id){
  const notes = document.getElementById('qaNotes').value;
  QA.notes(id, notes).then(d => {
    alert(d.message || (d.success ? 'Notes saved' : 'Failed'));
    if (d.success) location.reload();
});
}

// (nice to have) Keyboard shortcuts: A, R, F, U, M, S, N
document.addEventListener('keydown', (e) => {
  if (e.target && ['INPUT','TEXTAREA'].includes(e.target.tagName)) return;
  const id = {{ $question->id }};
  const k = e.key.toLowerCase();
  if (k === 'a') setStatus(id, 'approved');
  if (k === 'r') needsRevision(id);
  if (k === 'f') flagWithReason(id);
  if (k === 'u') setStatus(id, 'unreviewed');
  if (k === 'm') setStatus(id, 'ai_generated');
  if (k === 's') saveNotes(id);
  if (k === 'n') window.location.href = "{{ route('admin.qa.next', ['after' => $question->id, 'status' => 'unreviewed']) }}";
});

</script>
@endsection