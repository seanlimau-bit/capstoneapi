@extends('layouts.admin')

@section('title', 'View Question #' . $question->id)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css">
<style>
    .editable-field {
        cursor: pointer;
        padding: 8px 12px;
        border: 1px solid transparent;
        border-radius: 4px;
        position: relative;
        min-height: 38px;
        transition: all 0.2s;
    }
    
    .editable-field:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .edit-icon {
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .editable-field:hover .edit-icon {
        opacity: 1;
    }
    
    .mcq-option {
        display: flex;
        align-items: flex-start;
        padding: 15px;
        margin-bottom: 10px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .mcq-option.border-success {
        border-color: #198754 !important;
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .mcq-option-label {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #6c757d;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 15px;
        flex-shrink: 0;
        position: relative;
    }
    
    .mcq-option-label.bg-success {
        background-color: #198754 !important;
    }
    
    .image-container, .answer-image-container {
        position: relative;
        display: inline-block;
        margin-bottom: 15px;
    }
    
    .image-wrapper, .image-wrapper-small {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        border: 2px solid #e9ecef;
    }
    
    .question-image {
        max-width: 100%;
        max-height: 300px;
        height: auto;
        border-radius: 6px;
    }
    
    .answer-image {
        max-height: 80px;
        border-radius: 4px;
    }
    
    .image-overlay, .image-overlay-small {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .image-wrapper:hover .image-overlay,
    .image-wrapper-small:hover .image-overlay-small {
        opacity: 1;
    }
    
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    
    .upload-area:hover {
        border-color: #0d6efd;
        background: #e7f1ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .upload-area-small {
        border: 1px dashed #dee2e6;
        border-radius: 4px;
        padding: 8px 12px;
        text-align: center;
        cursor: pointer;
        font-size: 0.875rem;
        color: #6c757d;
        background: #f8f9fa;
        transition: all 0.2s;
    }
    
    .upload-area-small:hover {
        border-color: #0d6efd;
        color: #0d6efd;
        background: #e7f1ff;
    }
    
    .skill-change-box {
        border: 2px solid #198754 !important;
        background-color: rgba(25, 135, 84, 0.05);
    }

    /* Image Preview Modal Styles */
    .image-preview-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
    }
    
    .image-preview-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
    }
    
    .preview-image {
        max-width: 100%;
        max-height: 300px;
        border-radius: 4px;
        margin-bottom: 15px;
    }
    
    .file-info {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        font-size: 0.875rem;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid" data-question-id="{{ $question->id }}">
    {{-- Page Header --}}
    @include('admin.components.page-header', [
    'title' => 'View Question',
    'subtitle' => 'Question ID: ' . $question->id . ' | Type: ' . ($question->type->type ?? 'Unknown'),
    'breadcrumbs' => [
    ['title' => 'Dashboard', 'url' => url('/admin')],
    ['title' => 'Questions', 'url' => route('admin.questions.index')],
    ['title' => 'View Question']
    ],
    'actions' => [
    [
    'text' => 'QA Review',
    'url' => route('admin.qa.questions.review', $question),
    'icon' => 'clipboard-check',
    'style' => 'warning'
    ],
    [
    'text' => 'Duplicate Question',
    'onclick' => 'duplicateQuestion(' . $question->id . ')',
    'icon' => 'copy',
    'style' => 'info'
    ],
    [
    'text' => 'Delete Question',
    'onclick' => 'deleteQuestion(' . $question->id . ')',
    'icon' => 'trash',
    'style' => 'danger'
    ]
    ]
    ])

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Question Content Card --}}
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
                    {{-- Question Image Section --}}
                    <div class="mb-4" id="question-image-section">
                        <label class="form-label text-muted small">QUESTION IMAGE</label>
                        @if($question->question_image)
                        <div class="image-container">
                            <div class="image-wrapper">
                                <img src="{{ asset($question->question_image) }}" alt="Question Image" class="question-image">
                                <div class="image-overlay">
                                    <button class="btn btn-light btn-sm" onclick="changeQuestionImage({{ $question->id }})" title="Change Image">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="removeQuestionImage({{ $question->id }})" title="Remove Image">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

                    {{-- Question Text --}}
                    <div class="mb-4">
                        <label class="form-label text-muted small">QUESTION TEXT</label>
                        @if($question->type_id == 2)
                        <div class="editable-field question-field html-content" 
                        data-field="question" 
                        data-id="{{ $question->id }}" 
                        data-type="html"
                        title="Click to edit HTML content">
                        <div class="fib-content">
                            {!! $question->question !!}
                        </div>
                        <i class="fas fa-code text-muted ms-2 edit-icon"></i>
                    </div>
                    <small class="text-muted">This question supports HTML and KaTeX mathematical notation</small>

                    {{-- Count blanks and show appropriate number of answer fields --}}
                    @php
                    // Count the number of blanks in the question (assuming blanks are marked as ___ or [blank] or similar)
                    $blankCount = substr_count($question->question, '___') ?: 
                    substr_count($question->question, '[blank]') ?: 
                    substr_count($question->question, '____') ?: 1;

                    // For FIB questions, answers are typically stored in answer0, answer1, etc.
                    $answers = [];
                    if (!empty($question->answer0)) $answers[] = $question->answer0;
                    if (!empty($question->answer1)) $answers[] = $question->answer1;
                    if (!empty($question->answer2)) $answers[] = $question->answer2;
                    if (!empty($question->answer3)) $answers[] = $question->answer3;
                    @endphp

                    <div class="mt-3">
                        <label class="form-label text-muted small">EXPECTED ANSWERS (for each blank):</label>
                        @for($i = 0; $i < min($blankCount, 4); $i++)
                        <div class="mb-2">
                            <span class="text-muted me-2">Blank {{ $i + 1 }}:</span>
                            <div class="editable-field answer-field d-inline-block" 
                            data-field="answer{{ $i }}" 
                            data-id="{{ $question->id }}" 
                            data-type="text"
                            style="min-width: 200px; border-bottom: 1px solid #dee2e6; padding: 2px 5px;">
                            {{ $answers[$i] ?? 'Not set' }}
                            <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
                        </div>
                    </div>
                    @endfor
                </div>
                @else
                <div class="editable-field question-field" 
                data-field="question" 
                data-id="{{ $question->id }}" 
                data-type="textarea"
                title="Click to edit">
                {{ $question->question }}
                <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
            </div>
            @endif
        </div>

        {{-- Multiple Choice Options (Type 1) --}}
        @if($question->type_id == 1)
        @php
        $mcqOptions = [
        'A' => ['text' => $question->answer0, 'image' => $question->answer0_image, 'index' => 0],
        'B' => ['text' => $question->answer1, 'image' => $question->answer1_image, 'index' => 1],
        'C' => ['text' => $question->answer2, 'image' => $question->answer2_image, 'index' => 2],
        'D' => ['text' => $question->answer3, 'image' => $question->answer3_image, 'index' => 3]
        ];
        $validOptions = array_filter($mcqOptions, function($option) {
        return !empty($option['text']) || !empty($option['image']);
    });
    @endphp

    @if(count($validOptions) > 0)
    <div class="mb-4">
        <label class="form-label text-muted small">MULTIPLE CHOICE OPTIONS</label>
        @foreach($validOptions as $letter => $option)
        <div class="mcq-option {{ $question->correct_answer == $option['index'] ? 'border-success' : '' }}" data-option-index="{{ $option['index'] }}">
            <div class="mcq-option-label {{ $question->correct_answer == $option['index'] ? 'bg-success' : '' }}">
                {{ $letter }}
                @if($question->correct_answer == $option['index'])
                <i class="fas fa-check position-absolute" style="font-size: 10px; top: 2px; right: 2px;"></i>
                @endif
            </div>
            <div class="flex-grow-1">
                @if($option['text'])
                <div class="editable-field mb-2" 
                data-field="answer{{ $option['index'] }}" 
                data-id="{{ $question->id }}" 
                data-type="text"
                title="Click to edit option {{ $letter }}">
                {{ $option['text'] }}
                <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
            </div>
            @endif

            {{-- Answer Image Section --}}
            @if($option['image'])
            <div class="answer-image-container">
                <div class="image-wrapper-small">
                    <img src="{{ asset($option['image']) }}" alt="Option {{ $letter }} Image" class="answer-image">
                    <div class="image-overlay-small">
                        <button class="btn btn-light btn-sm" onclick="changeAnswerImage({{ $question->id }}, {{ $option['index'] }})" title="Change">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="removeAnswerImage({{ $question->id }}, {{ $option['index'] }})" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @else
            <div class="upload-area-small" onclick="addAnswerImage({{ $question->id }}, {{ $option['index'] }})">
                <i class="fas fa-plus me-2"></i>Add Image for Option {{ $letter }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Correct Answer for MCQ --}}
<div class="mb-4">
    <label class="form-label text-muted small">CORRECT ANSWER</label>
    <div class="correct-answer-selector-wrapper">
        <select class="form-select form-select-sm correct-answer-selector" 
        data-field="correct_answer" 
        data-id="{{ $question->id }}" 
        data-current="{{ $question->correct_answer }}">
        <option value="">Select correct answer...</option>
        @foreach($validOptions as $letter => $option)
        <option value="{{ $option['index'] }}" 
        {{ $question->correct_answer == $option['index'] ? 'selected' : '' }}>
        Option {{ $letter }}
    </option>
    @endforeach
</select>
</div>
</div>
@else
<div class="mb-4">
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        This MCQ question has no options defined.
    </div>
</div>
@endif
@else
{{-- Fill in the Blank Answer (Type 2) --}}
</div>
@endif

{{-- Question Settings Row --}}
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label text-muted small">QA STATUS</label>
        <div class="qa-status-selector-wrapper">
            <select class="form-select form-select-sm qa-status-selector" 
            data-field="qa_status" 
            data-id="{{ $question->id }}" 
            data-current="{{ $question->qa_status }}">
            <option value="">Select status...</option>
            @foreach($qaStatuses as $status)
            <option value="{{ $status['value'] }}" 
            {{ $status['value'] == ($question->qa_status ?? '') ? 'selected' : '' }}>
            {{ $status['label'] }}
        </option>
        @endforeach
    </select>
</div>
</div>

<div class="col-md-4 mb-3">
    <label class="form-label text-muted small">DIFFICULTY</label>
    <div class="difficulty-selector-wrapper">
       <select class="form-select form-select-sm difficulty-selector" 
       data-field="difficulty_id" 
       data-id="{{ $question->id }}" 
       data-current="{{ $question->difficulty_id }}">
       <option value="">Select difficulty...</option>
       @foreach($difficulties as $difficulty)
       <option value="{{ $difficulty->id }}" 
        {{ $difficulty->id == ($question->difficulty_id ?? '') ? 'selected' : '' }}>
        {{ $difficulty->short_description }}
    </option>
    @endforeach
</select>
</div>
</div>

<div class="col-md-4 mb-3">
    <label class="form-label text-muted small">TYPE</label>
    <div class="type-selector-wrapper">
        <select class="form-select form-select-sm type-selector" 
        data-field="type_id" 
        data-id="{{ $question->id }}" 
        data-current="{{ $question->type_id }}">
        <option value="">Select type...</option>
        @foreach($types as $type)
        <option value="{{ $type->id }}" 
            {{ $type->id == ($question->type_id ?? '') ? 'selected' : '' }}>
            {{ $type->type }}
        </option>
        @endforeach
    </select>
</div>
</div>
</div>
</div>
</div>

{{-- Explanation Card --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-lightbulb me-2"></i>Explanation, Hints & Solutions
        </h5>
    </div>
    <div class="card-body">
        {{-- Explanation --}}
        <div class="mb-4">
            <label class="form-label text-muted small">EXPLANATION</label>
            <div class="editable-field" 
                 data-field="explanation" 
                 data-id="{{ $question->id }}" 
                 data-type="textarea"
                 title="Click to edit">
                {{ $question->explanation ?: 'No explanation provided' }}
                <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
            </div>
        </div>

        {{-- Hints (Multiple with levels) --}}
        <div class="mb-4">
            <label class="form-label text-muted small">HINTS</label>
            @if($question->hints && $question->hints->count() > 0)
                <div class="hints-container">
                    @foreach($question->hints->sortBy('hint_level') as $hint)
                        <div class="hint-item mb-2 p-2 border-start border-3 border-info bg-light">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="badge bg-info me-2">Level {{ $hint->hint_level }}</span>
                                    <span class="hint-text">{{ $hint->hint_text }}</span>
                                </div>
                                <button class="btn btn-sm btn-outline-danger delete-hint" 
                                        data-hint-id="{{ $hint->id }}"
                                        title="Delete hint">
                                    <i class="fas fa-times"></i>
                                </button>
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
            <button class="btn btn-sm btn-outline-primary mt-2" id="add-hint-btn">
                <i class="fas fa-plus me-1"></i>Add Hint
            </button>
        </div>

        {{-- Solutions (Multiple with authors) --}}
        <div class="mb-4">
            <label class="form-label text-muted small">SOLUTIONS</label>
            @if($question->solutions && $question->solutions->count() > 0)
                <div class="solutions-container">
                    @foreach($question->solutions as $solution)
                        <div class="solution-item mb-3 p-3 border rounded bg-light">
                            <div class="solution-content">
                                {!! nl2br(e($solution->solution)) !!}
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    By {{ $solution->user->name ?? 'Unknown' }} 
                                    on {{ $solution->created_at->format('M d, Y') }}
                                </small>
                                <div>
                                    <button class="btn btn-sm btn-outline-warning edit-solution" 
                                            data-solution-id="{{ $solution->id }}"
                                            title="Edit solution">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-solution" 
                                            data-solution-id="{{ $solution->id }}"
                                            title="Delete solution">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No solutions available</p>
            @endif
            <button class="btn btn-sm btn-outline-primary mt-2"
                    id="add-solution-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#addSolutionModal">
              <i class="fas fa-plus me-1"></i>Add Solution
            </button>
        </div>
    </div>
</div>

{{-- Add Hint Modal --}}
<div class="modal fade" id="addHintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Hint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Hint Level</label>
                    <select class="form-control" id="hint-level">
                        <option value="1">Level 1 (Easy nudge)</option>
                        <option value="2">Level 2 (Medium clue)</option>
                        <option value="3">Level 3 (Almost reveals answer)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hint Text</label>
                    <textarea class="form-control" id="hint-text" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-hint">Save Hint</button>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Sidebar --}}
<div class="col-lg-4">
    {{-- Question Statistics --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-chart-bar me-2"></i>Question Statistics
            </h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-4">
                    <h4 class="text-primary">{{ $question->id }}</h4>
                    <small class="text-muted">Question ID</small>
                </div>
                <div class="col-4">
                    <h4 class="text-success">0</h4>
                    <small class="text-muted">Times Used</small>
                </div>
                <div class="col-4">
                    <h4 class="text-info">0%</h4>
                    <small class="text-muted">Accuracy</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Associated Skill --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-brain me-2"></i>Associated Skill
            </h5>
        </div>
        <div class="card-body">
            @if($question->skill)
            <div class="d-flex align-items-start">
                @if($question->skill->image)
                <img src="{{ asset($question->skill->image) }}" alt="{{ $question->skill->skill }}" 
                class="rounded me-3" width="60" height="60" style="object-fit: cover;">
                @endif
                <div class="flex-grow-1">
                    <h6 class="mb-1">{{ $question->skill->skill }}
                        <a href="{{ route('admin.skills.show', $question->skill) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View Skill
                        </a>
                    </h6>
                    <p class="text-muted small mb-2">{{ strlen($question->skill->description) > 100 ? substr($question->skill->description, 0, 100) . '...' : $question->skill->description }}</p>

                    {{-- Change Skill Form --}}
                    <div class="skill-change-box border border-success rounded p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fas fa-exchange-alt text-success me-2"></i>
                                <span class="fw-semibold text-success">Change Skill</span>
                            </div>
                            <div class="skill-dropdown" style="min-width: 200px;">
                                <select class="form-select form-select-sm skill-selector" 
                                data-field="skill_id" 
                                data-id="{{ $question->id }}" 
                                data-current="{{ $question->skill_id ?? '' }}">
                                <option value="">Select a skill...</option>
                                @foreach($skills as $skillOption)
                                <option value="{{ $skillOption->id }}" 
                                    {{ $skillOption->id == ($question->skill_id ?? '') ? 'selected' : '' }}>
                                    {{ $skillOption->skill }}
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

{{-- Quick Actions --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tools me-2"></i>Quick Actions
        </h5>
    </div>
    <div class="card-body">
        <div class="d-grid gap-2">
            <a href="{{ route('admin.qa.questions.review', $question) }}" class="btn btn-warning">
                <i class="fas fa-clipboard-check me-1"></i>QA Review
            </a>
            <button class="btn btn-info" onclick="duplicateQuestion({{ $question->id }})">
                <i class="fas fa-copy me-1"></i>Duplicate Question
            </button>
            <button class="btn btn-success" onclick="previewQuestion({{ $question->id }})">
                <i class="fas fa-eye me-1"></i>Preview Question
            </button>
            <button class="btn btn-danger" onclick="deleteQuestion({{ $question->id }})">
                <i class="fas fa-trash me-1"></i>Delete Question
            </button>
        </div>
    </div>
</div>

{{-- Author Information --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-user me-2"></i>Author Information
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            @if($question->author && $question->author->image)
            <img src="{{ $question->author->image }}" alt="{{ $question->author->name }}" 
            class="rounded-circle me-3" width="50" height="50" 
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
            style="width: 50px; height: 50px; display: none;">
            {{ substr($question->author->name, 0, 1) }}
        </div>
        @else
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
        style="width: 50px; height: 50px;">
        {{ $question->author ? substr($question->author->name, 0, 1) : 'U' }}
    </div>
    @endif
    <div>
        <h6 class="mb-1">{{ $question->author->name ?? 'Unknown Author' }}</h6>
        <small class="text-muted">Created {{ $question->created_at->diffForHumans() }}</small>
    </div>
</div>
<div class="row small text-muted">
    <div class="col-12 mb-2">
        <strong>Created:</strong> {{ $question->created_at->format('M j, Y \a\t g:i A') }}
    </div>
    <div class="col-12">
        <strong>Updated:</strong> {{ $question->updated_at->format('M j, Y \a\t g:i A') }}
    </div>
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
        <div class="text-center">
            <img id="previewImage" class="preview-image" src="" alt="Preview">
        </div>
        <div id="fileInfo" class="file-info"></div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success flex-fill" onclick="confirmImageUpload()">
                <i class="fas fa-upload me-1"></i>Upload Image
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeImagePreview()">Cancel</button>
        </div>
    </div>
</div>

{{-- Hidden File Input --}}
<input type="file" id="imageUpload" style="display: none;" accept="image/*">
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/katex@0.16.4/dist/contrib/auto-render.min.js"></script>
<script>
// Global variables for image upload
let currentImageUpload = null;
let currentImageType = null;
let currentQuestionId = {{ $question->id }};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    window.QUESTION_ID = {{ $question->id }};
    console.log('Question show page initialized for question:', {{ $question->id }});
    
    renderKaTeX();
    setupInlineEditing();
    setupDropdownSelectors();
});

// === IMAGE UPLOAD FUNCTIONS ===

function addQuestionImage(questionId) {
    openImageUploader('question', questionId, 'Question Image');
}

function changeQuestionImage(questionId) {
    openImageUploader('question', questionId, 'Question Image');
}

function removeQuestionImage(questionId) {
    if (confirm('Are you sure you want to remove the question image?')) {
        removeImage('question', questionId);
    }
}

function addAnswerImage(questionId, optionIndex) {
    const optionLetter = ['A', 'B', 'C', 'D'][optionIndex];
    openImageUploader('answer', questionId, `Option ${optionLetter} Image`, optionIndex);
}

function changeAnswerImage(questionId, optionIndex) {
    const optionLetter = ['A', 'B', 'C', 'D'][optionIndex];
    openImageUploader('answer', questionId, `Option ${optionLetter} Image`, optionIndex);
}

function removeAnswerImage(questionId, optionIndex) {
    const optionLetter = ['A', 'B', 'C', 'D'][optionIndex];
    if (confirm(`Are you sure you want to remove the image for Option ${optionLetter}?`)) {
        removeImage('answer', questionId, optionIndex);
    }
}

function openImageUploader(type, questionId, title, optionIndex = null) {
    currentImageType = type;
    currentQuestionId = questionId;
    
    // Store option index for answer images
    if (optionIndex !== null) {
        currentImageType = `answer_${optionIndex}`;
    }
    
    document.getElementById('previewTitle').textContent = title;
    
    const input = document.getElementById('imageUpload');
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            showImagePreview(file);
        }
    };
    input.click();
}

function showImagePreview(file) {
    // Validate file
    const maxSize = 6 * 1024 * 1024; // 6MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Please select a JPG, PNG, GIF, or WebP image.');
        return;
    }
    
    if (file.size > maxSize) {
        alert('File too large. Maximum size is 6MB.');
        return;
    }
    
    // Store the file for upload
    currentImageUpload = file;
    
    // Create preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImage').src = e.target.result;
        
        // Update file info
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        document.getElementById('fileInfo').innerHTML = `
        <strong>File:</strong> ${file.name}<br>
        <strong>Size:</strong> ${fileSize} MB<br>
        <strong>Type:</strong> ${file.type}
        `;
        
        // Show modal
        document.getElementById('imagePreviewModal').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function confirmImageUpload() {
    if (!currentImageUpload) return;
    
    const formData = new FormData();
    formData.append('image', currentImageUpload);
    
    let url;
    if (currentImageType === 'question') {
        url = `/admin/questions/${currentQuestionId}/image`;
    } else if (currentImageType.startsWith('answer_')) {
        const optionIndex = currentImageType.split('_')[1];
        url = `/admin/questions/${currentQuestionId}/answers/${optionIndex}/image`;
    }
    
    // Show loading state
    const uploadBtn = document.querySelector('[onclick="confirmImageUpload()"]');
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
    uploadBtn.disabled = true;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Image uploaded successfully!', 'success');
            closeImagePreview();
            // Reload page to show new image
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Upload failed', 'error');
            uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
            uploadBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showToast('Upload failed', 'error');
        uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
        uploadBtn.disabled = false;
    });
}

function removeImage(type, questionId, optionIndex = null) {
    let url;
    if (type === 'question') {
        url = `/admin/questions/${questionId}/image`;
    } else if (type === 'answer') {
        url = `/admin/questions/${questionId}/answers/${optionIndex}/image`;
    }
    
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Image removed successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Remove failed', 'error');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        showToast('Remove failed', 'error');
    });
}

function closeImagePreview() {
    document.getElementById('imagePreviewModal').style.display = 'none';
    currentImageUpload = null;
    currentImageType = null;
    
    // Reset upload button
    const uploadBtn = document.querySelector('[onclick="confirmImageUpload()"]');
    uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
    uploadBtn.disabled = false;
}

// === INLINE EDITING FUNCTIONS ===

function setupInlineEditing() {
    document.querySelectorAll('.editable-field').forEach(field => {
        field.addEventListener('click', function() {
            const fieldName = this.dataset.field;
            const fieldType = this.dataset.type || 'text';
            const currentValue = fieldType === 'html' 
            ? this.querySelector('.fib-content').innerHTML.trim()
            : this.textContent.trim();
            
            showInlineEditor(this, fieldName, fieldType, currentValue);
        });
    });
}

function showInlineEditor(element, fieldName, fieldType, currentValue) {
    const isHtml = fieldType === 'html';
    const isTextarea = fieldType === 'textarea' || isHtml;
    
    const input = isTextarea 
    ? `<textarea class="form-control" rows="3" autofocus>${currentValue}</textarea>`
    : `<input type="text" class="form-control" value="${currentValue}" autofocus>`;
    
    element.innerHTML = input;
    
    const inputEl = element.querySelector(isTextarea ? 'textarea' : 'input');
    inputEl.focus();
    
    const saveEdit = async () => {
        const newValue = inputEl.value;
        if (newValue !== currentValue) {
            try {
                await updateQuestionField(fieldName, newValue);
                location.reload();
            } catch (error) {
                cancelEdit();
            }
        } else {
            cancelEdit();
        }
    };

    const cancelEdit = () => {
        if (fieldType === 'html') {
            element.innerHTML = `
            <div class="fib-content">${currentValue}</div>
            <i class="fas fa-code text-muted ms-2 edit-icon"></i>
            `;
        } else {
            element.innerHTML = `${currentValue}<i class="fas fa-edit text-muted ms-2 edit-icon"></i>`;
        }
    };

    inputEl.addEventListener('blur', saveEdit);
    inputEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            saveEdit();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEdit();
        }
    });
}

function setupDropdownSelectors() {
    document.querySelectorAll('select[data-field]').forEach(select => {
        select.addEventListener('change', async (e) => {
            const fieldName = e.target.dataset.field;
            const newValue = e.target.value;
            
            if (newValue) {
                try {
                    await updateQuestionField(fieldName, newValue);
                    // Visual feedback
                    e.target.style.borderColor = '#198754';
                    setTimeout(() => {
                        e.target.style.borderColor = '';
                    }, 2000);
                } catch (error) {
                    // Reset to original value
                    e.target.selectedIndex = 0;
                }
            }
        });
    });
}

async function updateQuestionField(fieldName, value) {
  try {
    const response = await fetch(`/admin/questions/${currentQuestionId}/update-field`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ field: fieldName, value, _method: 'PATCH' })
    });

    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Update failed');

    showToast('Field updated successfully', 'success');
    // either re-render:
    setTimeout(() => renderKaTeX(), 100);
    // or simply:
    // location.reload();
    return data;
  } catch (err) {
    console.error('Update error:', err);
    showToast(err.message || 'Update failed', 'error');
    throw err;
  }
}


// === OTHER FUNCTIONS ===

function renderKaTeX() {
    // Render all elements that might contain math
    const elements = document.querySelectorAll('.question-field, .editable-field, .fib-content, .mcq-option');
    
    elements.forEach(element => {
        renderMathInElement(element, {
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


function deleteQuestion(questionId) {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        fetch(`/admin/questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Question deleted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/admin/questions';
                }, 1500);
            } else {
                showToast(data.message || 'Error deleting question', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error deleting question', 'error');
        });
    }
}

function duplicateQuestion(questionId) {
    if (confirm('Are you sure you want to duplicate this question? A copy will be created.')) {
        const duplicateBtn = document.querySelector('[onclick*="duplicateQuestion"]');
        if (duplicateBtn) {
            duplicateBtn.disabled = true;
            duplicateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Duplicating...';
        }
        
        fetch(`/admin/questions/${questionId}/duplicate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Question duplicated successfully', 'success');
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    location.reload();
                }
            } else {
                showToast(data.message || 'Failed to duplicate question', 'error');
                if (duplicateBtn) {
                    duplicateBtn.disabled = false;
                    duplicateBtn.innerHTML = '<i class="fas fa-copy me-1"></i>Duplicate Question';
                }
            }
        })
        .catch(error => {
            console.error('Duplication error:', error);
            showToast('Error duplicating question', 'error');
            if (duplicateBtn) {
                duplicateBtn.disabled = false;
                duplicateBtn.innerHTML = '<i class="fas fa-copy me-1"></i>Duplicate Question';
            }
        });
    }
}

function previewQuestion(questionId) {
    window.open(`/admin/questions/${questionId}/preview`, '_blank', 'width=800,height=600');
}

function showToast(message, type = 'info') {
    const toastClass = type === 'success' ? 'alert-success' : 
    type === 'error' ? 'alert-danger' : 'alert-info';
    
    const toast = document.createElement('div');
    toast.className = `alert ${toastClass} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
    ${message}
    <button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}
document.addEventListener('DOMContentLoaded', function () {
  renderKaTeX();

  const addHintBtn = document.getElementById('add-hint-btn');
  if (addHintBtn) {
    addHintBtn.addEventListener('click', () => {
      const m = new bootstrap.Modal(document.getElementById('addHintModal'));
      m.show();
    });
  }

  const addSolBtn = document.getElementById('add-solution-btn');
  if (addSolBtn) {
    addSolBtn.addEventListener('click', () => {
      const m = new bootstrap.Modal(document.getElementById('addSolutionModal'));
      m.show();
    });
  }

  const saveSolutionBtn = document.getElementById('save-solution');
  if (saveSolutionBtn) {
    saveSolutionBtn.addEventListener('click', function () {
      const text = document.getElementById('solution-text').value;
      fetch(`/api/questions/{{ $question->id }}/solutions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ solution: text })
      })
      .then(r => r.json())
      .then(data => { if (data.success) location.reload(); });
    });
  }
});

</script>
@endpush