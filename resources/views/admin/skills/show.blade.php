@extends('layouts.admin')

@section('title', 'Skill: ' . $skill->skill)

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    @include('admin.components.page-header', [
    'title' => $skill->skill,
    'subtitle' => 'Manage skill details, tracks, and questions',
    'icon' => 'brain',
    'breadcrumbs' => [
    ['title' => 'Skills', 'url' => route('admin.skills.index')],
    ['title' => $skill->skill, 'url' => '']
    ],
    'actions' => [
    [
    'text' => 'Edit Details',
    'url' => route('admin.skills.edit', $skill),
    'icon' => 'edit',
    'class' => 'outline-primary'
    ],
    [
    'text' => 'Add Questions',
    'onclick' => 'SkillManager.showBulkQuestions()',
    'icon' => 'plus',
    'class' => 'success'
    ],
    [
    'text' => 'Delete Skill',
    'onclick' => 'SkillManager.deleteSkill(' . $skill->id . ')',
    'icon' => 'trash',
    'class' => 'outline-danger'
    ],
    [
    'text' => 'Actions',
    'type' => 'dropdown',
    'icon' => 'ellipsis-v',
    'class' => 'outline-secondary',
    'items' => [
    ['text' => 'Duplicate Skill', 'icon' => 'copy', 'onclick' => 'SkillManager.copySkill(' . $skill->id . ')'],
    ['text' => 'Export Questions', 'icon' => 'download', 'onclick' => 'SkillManager.exportQuestions()']
    ]
    ]
    ]
    ])

    {{-- Statistics Row --}}
    @include('admin.components.stats-row', [
    'stats' => [
    [
    'value' => $skill->questions->count(),
    'label' => 'Questions',
    'color' => 'primary',
    'icon' => 'question-circle'
    ],
    [
    'value' => $skill->tracks->count(),
    'label' => 'Tracks',
    'color' => 'info',
    'icon' => 'route'
    ],
    [
    'value' => $skill->questions->where('qa_status', 'approved')->count(),
    'label' => 'Approved',
    'color' => 'success',
    'icon' => 'check-circle'
    ],
    [
    'value' => $skill->questions->where('qa_status', 'flagged')->count(),
    'label' => 'Flagged',
    'color' => 'danger',
    'icon' => 'flag'
    ]
    ]
    ])

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Skill Details Card --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Skill Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Skill Name</label>
                            <div class="editable-field" data-field="skill" data-type="text">
                                <span class="field-display">{{ $skill->skill }}</span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <div class="editable-field" data-field="status_id" data-type="select">
                                <span class="field-display">
                                    @if($skill->status_id == 3)
                                    <span class="badge bg-success">Active</span>
                                    @elseif($skill->status_id == 4)
                                    <span class="badge bg-warning">Draft</span>
                                    @else
                                    <span class="badge bg-secondary">Unknown</span>
                                    @endif
                                </span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <div class="editable-field" data-field="description" data-type="textarea">
                                <span class="field-display">{{ $skill->description ?: 'Click to add description...' }}</span>
                                <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Created By</label>
                            <div>{{ $skill->user->name ?? 'Unknown User' }} - {{ $skill->created_at->format('M j, Y') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Verification</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="skillCheck" 
                                {{ $skill->check ? 'checked' : '' }} 
                                onchange="SkillManager.updateCheckField(this)">
                                <label class="form-check-label" for="skillCheck">Verified Skill</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Questions Card --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Questions ({{ $skill->questions->count() }})</h5>
                    <div>
                        <button class="btn btn-sm btn-primary" onclick="SkillManager.showBulkQuestions()">
                            <i class="fas fa-plus"></i> Add Questions
                        </button>
                        @if($skill->questions->count() > 0)
                        <button class="btn btn-sm btn-outline-secondary" onclick="SkillManager.exportQuestions()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($skill->questions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Question</th>
                                    <th width="100">Type</th>
                                    <th width="100">Difficulty</th>
                                    <th width="120">Status</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($skill->questions as $question)
                                <tr>
                                    <td><span class="badge bg-light text-dark">#{{ $question->id }}</span></td>
                                    <td>
                                        <div class="fw-bold">
                                            @php
                                            $plainText = strip_tags($question->question);
                                            $shouldTruncate = strlen($plainText) > 80;
                                            @endphp

                                            @if($shouldTruncate)
                                            {{ substr($plainText, 0, 80) }}...
                                            @else
                                            {!! $question->question !!}
                                            @endif
                                        </div>
                                        @if($question->question_image)
                                        <small class="text-muted"><i class="fas fa-image"></i> Has image</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $question->type->type ?? 'MCQ' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                        $difficulty = $question->difficulty->short_description ?? 'medium';
                                        $badgeClass = match($difficulty) {
                                        'easy' => 'bg-success',
                                        'medium' => 'bg-warning',
                                        'hard' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($difficulty) }}</span>
                                </td>
                                <td>
                                    @php
                                    $status = $question->qa_status ?? 'draft';
                                    $statusClass = match($status) {
                                    'approved' => 'bg-success',
                                    'flagged' => 'bg-danger',
                                    'needs_revision' => 'bg-warning',
                                    'unreviewed' => 'bg-secondary',
                                    'ai_generated' => 'bg-info'
                                };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" onclick="SkillManager.viewQuestion({{ $question->id }})" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="SkillManager.generateSimilar({{ $question->id }}, {{ $skill->id }}, '{{ addslashes(substr($plainText, 0, 50)) }}')" title="Generate Similar Questions">
                                        <i class="fas fa-wand-magic-sparkles"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="SkillManager.deleteQuestion({{ $question->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            @include('admin.components.empty-state', [
            'icon' => 'question-circle',
            'title' => 'No Questions Added',
            'message' => 'Add your first question to this skill to get started'
            ])
            @endif
        </div>
    </div>
</div>

{{-- Sidebar --}}
<div class="col-lg-4">
    {{-- Tracks Card --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Assigned Tracks ({{ $skill->tracks->count() }})</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="SkillManager.showAddTrack()">
                <i class="fas fa-plus"></i> Add
            </button>
        </div>
        <div class="card-body">
            @if($skill->tracks->count() > 0)
            <div class="list-group list-group-flush">
                @foreach($skill->tracks as $track)
                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div>
                      <strong>{{ $track->track }}</strong>
                      @if($track->level)
                      @php
                      // define your badge colors
                      $statusColors = [
                      'Only Me'    => 'badge bg-secondary',   // grey
                      'Restricted' => 'badge bg-warning text-dark', // yellow
                      'Public'     => 'badge bg-success',     // green
                      'Draft'      => 'badge bg-info text-dark',    // light blue
                      ];
                      $statusClass = $statusColors[$track->status->status] ?? 'badge bg-light text-dark';
                      @endphp

                      <br>
                      <small class="text-muted">
                          Level {{ $track->level->level }} - {{ $track->level->description }}
                          <span class="{{ $statusClass }}">{{ $track->status->status }}</span>
                      </small>
                      @endif
                  </div>

                  <button class="btn btn-sm btn-outline-danger" onclick="SkillManager.removeTrack({{ $skill->id }}, {{ $track->id }})" title="Remove track">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endforeach
        </div>
        @else
        @include('admin.components.empty-state', [
        'icon' => 'route',
        'title' => 'No tracks assigned',
        'message' => ''
        ])
        @endif
    </div>
</div>

{{-- Video Links Card --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Video Resources</h5>
        <button class="btn btn-sm btn-outline-primary" onclick="SkillManager.showAddVideo()">
            <i class="fas fa-plus"></i> Add
        </button>
    </div>
    <div class="card-body">
        @if($skill->links && $skill->links->count() > 0)
        @foreach($skill->links as $link)
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="flex-grow-1">
                <div class="editable-field" data-field="video_title" data-type="text" data-link-id="{{ $link->id }}">
                    <span class="field-display">
                        {{ $link->title ?? 'Video ' . $loop->iteration }}
                    </span>
                    <i class="fas fa-edit edit-icon text-muted ms-2"></i>
                </div>
                <small class="text-muted">{{ basename($link->link) }}</small>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="{{ asset($link->link) }}" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-play"></i>
                </a>
                <button class="btn btn-outline-danger" onclick="SkillManager.deleteVideo({{ $link->id }})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        @endforeach
        @else
        @include('admin.components.empty-state', [
        'icon' => 'video',
        'title' => 'No videos uploaded',
        'message' => ''
        ])
        @endif
    </div>
</div>
</div>
</div>
</div>

{{-- Add Track Modal --}}
<div class="modal fade" id="addTrackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Track</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTrackForm">
                    <div class="mb-3">
                        <label class="form-label">Select Track</label>
                        <select name="track_id" class="form-select" required>
                            <option value="">Choose a track...</option>
                            @php
                            $availableTracks = \App\Models\Track::public()
                            ->with(['level' => fn($q) => $q->public()])
                            ->whereNotIn('tracks.id', $skill->tracks->pluck('id'))
                            ->orderBy(
                            \App\Models\Level::select('level')
                            ->whereColumn('levels.id', 'tracks.level_id')
                            ->limit(1)
                            )
                            ->orderBy('tracks.track')
                            ->get();
                            @endphp
                            @foreach($availableTracks as $track)
                            <option value="{{ $track->id }}">
                                {{ $track->track }}
                                @if($track->level) ({{ $track->level->description }}) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="SkillManager.addTrack()">Add Track</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Video Modal --}}
<div class="modal fade" id="addVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Link Video Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search Videos</label>
                    <input type="text" id="videoSearchBox" class="form-control" placeholder="Search by filename...">
                </div>
                
                <div id="videoLoadingSpinner" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Loading videos...</p>
                </div>
                
                <div id="videoList" class="d-none" style="max-height: 400px; overflow-y: auto;">
                    <div class="row g-3" id="videoGrid"></div>
                </div>
                
                <div id="noVideosFound" class="text-center py-4 d-none">
                    <i class="fas fa-video fa-3x text-muted mb-3"></i>
                    <h6>No videos found</h6>
                    <p class="text-muted">No video files were found in the asset directories.</p>
                </div>
                
                <form id="addVideoForm" class="d-none">
                    <input type="hidden" name="video_path" id="selectedVideoPath">
                    <div class="mb-3">
                        <label class="form-label">Video Title</label>
                        <input type="text" name="title" id="selectedVideoTitle" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <strong>Selected Video:</strong>
                        <div id="selectedVideoInfo" class="text-muted"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary d-none" id="linkVideoBtn" onclick="SkillManager.linkSelectedVideo()">Link Video</button>
            </div>
        </div>
    </div>
</div>


{{-- CSRF Token Meta Tag --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

@include('admin.skills.modals.question-generation-modal')

@endsection

@push('scripts')
<script>
/**
 * Complete Skill Manager - All functionality restored
 */
 class SkillManager {
    static config = {
        skillId: {{ $skill->id }},
        referenceData: {
            statuses: @json($statuses ?? []),
            difficulties: @json($difficulties ?? []),
            questionTypes: @json($questionTypes ?? [])
        },
        routes: {
            skillUpdate: '{{ route("admin.skills.update", $skill) }}',
            questionsGenerate: '/admin/questions/generate',
            questionDelete: '/admin/questions',
            skillCopy: '/admin/skills/{{ $skill->id }}/duplicate',
            skillDelete: '{{ route("admin.skills.destroy", $skill) }}',
            exportQuestions: '/admin/skills/{{ $skill->id }}/export-questions',
            addTrack: '/admin/skills/{{ $skill->id }}/add-track',
            removeTrack: '/admin/skills/{{ $skill->id }}/tracks',
            linkVideo: '/admin/skills/{{ $skill->id }}/link-video',
            deleteVideo: '/admin/skills/{{ $skill->id }}/videos',
            getVideos: '/admin/assets/videos'
        }   
    };

    static currentQuestionForVariation = null;
    static availableVideos = [];
    static filteredVideos = [];

    static init() {
        this.setupEventListeners();
        this.initializeVariationModal();
        console.log('SkillManager initialized');
    }

    static setupEventListeners() {
        this.setupInlineEditing();
        this.setupVideoSearch();
    }

    static setupVideoSearch() {
        document.getElementById('videoSearchBox')?.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            this.filterVideos(searchTerm);
        });
    }

    static filterVideos(searchTerm) {
        if (!searchTerm) {
            this.filteredVideos = [...this.availableVideos];
        } else {
            this.filteredVideos = this.availableVideos.filter(video => 
                video.name.toLowerCase().includes(searchTerm) ||
                video.path.toLowerCase().includes(searchTerm)
                );
        }
        this.renderVideoList();
    }

    // === INLINE EDITING ===
    static setupInlineEditing() {
        const editableFields = document.querySelectorAll('.editable-field');
        console.log('Found editable fields:', editableFields.length);
        
        editableFields.forEach(element => {
            element.addEventListener('click', (e) => {
                e.stopPropagation();
                
                if (element.querySelector('input, textarea, select')) {
                    return;
                }
                
                const field = element.dataset.field;
                const type = element.dataset.type;
                const linkId = element.dataset.linkId;
                const fieldDisplay = element.querySelector('.field-display');
                const currentValue = fieldDisplay.textContent.trim();
                
                this.showInlineEditor(element, field, type, currentValue, linkId);
            });
        });
    }

    static showInlineEditor(element, field, type, currentValue, linkId = null) {
        const fieldDisplay = element.querySelector('.field-display');
        if (!fieldDisplay) return;

        let input;
        const escapedValue = currentValue.replace(/"/g, '&quot;');

        switch(type) {
            case 'text':
            input = `<input type="text" class="form-control form-control-sm" value="${escapedValue}" autofocus>`;
            break;
            case 'textarea':
            input = `<textarea class="form-control form-control-sm" rows="3" autofocus>${currentValue}</textarea>`;
            break;
            case 'select':
            if (field === 'status_id') {
                const options = this.config.referenceData.statuses.map(status => 
                    `<option value="${status.id}" ${currentValue.toLowerCase().includes(status.status.toLowerCase()) ? 'selected' : ''}>${status.status}</option>`
                    ).join('');
                input = `<select class="form-select form-select-sm" autofocus>${options}</select>`;
            }
            break;
        }
        
        fieldDisplay.innerHTML = input;
        
        const inputElement = fieldDisplay.querySelector('input, textarea, select');
        if (inputElement) {
            inputElement.dataset.originalValue = currentValue;
            
            if (inputElement.tagName === 'INPUT') {
                inputElement.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.saveInlineEdit(inputElement, field, linkId);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.cancelInlineEdit(inputElement);
                    }
                });
            }
            
            inputElement.addEventListener('blur', (e) => {
                setTimeout(() => {
                    if (document.contains(inputElement) && inputElement.parentNode) {
                        this.saveInlineEdit(inputElement, field, linkId);
                    }
                }, 150);
            });
            
            if (inputElement.tagName === 'SELECT') {
                inputElement.addEventListener('change', (e) => {
                    this.saveInlineEdit(inputElement, field, linkId);
                });
            }
            
            inputElement.focus();
            if (inputElement.tagName === 'INPUT') {
                inputElement.select();
            }
        }
    }

    static cancelInlineEdit(input) {
        const container = input.closest('.editable-field');
        const fieldDisplay = container.querySelector('.field-display');
        fieldDisplay.innerHTML = input.dataset.originalValue;
    }

    static async saveInlineEdit(input, field, linkId) {
        if (input.dataset.saving === 'true') return;
        input.dataset.saving = 'true';
        
        const value = input.value;
        const container = input.closest('.editable-field');
        const fieldDisplay = container.querySelector('.field-display');
        
        if (value === input.dataset.originalValue) {
            fieldDisplay.innerHTML = value;
            delete input.dataset.saving;
            return;
        }
        
        let url, data;
        
        if (field === 'video_title' && linkId) {
            url = `${this.config.routes.skillUpdate}/videos/${linkId}`;
            data = { title: value };
        } else {
            url = this.config.routes.skillUpdate;
            data = { field: field, value: value };
        }

        fieldDisplay.innerHTML = '<i class="fas fa-spinner fa-spin text-muted"></i>';

        try {
            const response = await this.makeRequest(url, 'PATCH', data);
            
            if (response.success) {
                let displayValue = value;
                
                if (field === 'status_id') {
                    const status = this.config.referenceData.statuses.find(s => s.id == value);
                    const badgeClass = value == 3 ? 'bg-success' : (value == 4 ? 'bg-warning' : 'bg-secondary');
                    displayValue = `<span class="badge ${badgeClass}">${status ? status.status : 'Unknown'}</span>`;
                }
                
                fieldDisplay.innerHTML = displayValue;
                this.showToast('Updated successfully', 'success');
            } else {
                fieldDisplay.innerHTML = input.dataset.originalValue;
                this.showToast(response.message || 'Error updating field', 'error');
            }
        } catch (error) {
            fieldDisplay.innerHTML = input.dataset.originalValue;
            this.showToast('Network error occurred', 'error');
        } finally {
            delete input.dataset.saving;
        }
    }

    static async updateCheckField(checkbox) {
        const value = checkbox.checked;
        
        try {
            const response = await this.makeRequest(this.config.routes.skillUpdate, 'PATCH', { 
                field: 'check', 
                value: value 
            });
            
            if (response.success) {
                this.showToast('Verification status updated', 'success');
            } else {
                this.showToast(response.message || 'Error updating verification', 'error');
                checkbox.checked = !value;
            }
        } catch (error) {
            this.showToast('Network error occurred', 'error');
            checkbox.checked = !value;
        }
    }

    // === QUESTION MANAGEMENT ===
    static viewQuestion(questionId) {
        window.open(`/admin/questions/${questionId}`, '_blank');
    }

    static async deleteQuestion(questionId) {
        if (!confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
            return;
        }

        const deleteButton = document.querySelector(`button[onclick*="deleteQuestion(${questionId})"]`);
        const originalHtml = deleteButton.innerHTML;
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        deleteButton.disabled = true;

        try {
            const response = await this.makeRequest(`${this.config.routes.questionDelete}/${questionId}`, 'DELETE');
            
            deleteButton.innerHTML = originalHtml;
            deleteButton.disabled = false;
            
            if (response.success) {
                const questionRow = deleteButton.closest('tr');
                if (questionRow) {
                    questionRow.remove();
                }
                this.showToast('Question deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(response.message || 'Error deleting question', 'error');
            }
        } catch (error) {
            deleteButton.innerHTML = originalHtml;
            deleteButton.disabled = false;
            this.showToast('Error deleting question', 'error');
        }
    }

    // === QUESTION VARIATION GENERATION ===
    static initializeVariationModal() {
        const variationCountSelect = document.getElementById('variationCount');
        if (variationCountSelect) {
            variationCountSelect.addEventListener('change', () => {
                const count = parseInt(variationCountSelect.value);
                let estimatedTime;
                
                if (count <= 3) estimatedTime = '30-45 seconds';
                else if (count <= 5) estimatedTime = '45-60 seconds';
                else if (count <= 10) estimatedTime = '1-2 minutes';
                else estimatedTime = '2-3 minutes';
                
                const timeElement = document.getElementById('estimatedTime');
                if (timeElement) {
                    timeElement.textContent = estimatedTime;
                }
                
                const previewArea = document.querySelector('.variation-preview');
                if (previewArea) {
                    previewArea.classList.remove('d-none');
                }
            });
        }
    }

    static generateSimilar(questionId, skillId = (SkillManager.config?.skillId ?? null), questionText = null) {
      const modalEl = document.getElementById('questionGenerationModal');

      if (!modalEl) {
        console.error('Question generation modal not found.');
        this.showToast('Question generation modal is not available on this page.', 'error');
        return;
    }

  // Set the question ID
  const qIdEl = document.getElementById('selectedQuestionId');
  if (qIdEl) qIdEl.value = questionId;

  // Set source to "Question"
  const srcQn = modalEl.querySelector('input[name="source"][value="question"]');
  if (srcQn) srcQn.checked = true;

  // Optionally set skill ID if present
  const skillHidden = modalEl.querySelector('input[name="skill_id"]');
  if (skillHidden && skillId != null) skillHidden.value = skillId;

  // Open the modal
  new bootstrap.Modal(modalEl).show();
}


static async generateVariations(e) {
  e?.preventDefault?.(); 

  const form        = document.getElementById('questionGenerationForm');
  const spinner     = document.getElementById('loadingSpinner');
  const generateBtn = document.getElementById('generateBtn');
  const originalBtnHTML = generateBtn ? generateBtn.innerHTML : '';

      // 1) UI -> Generating…
      spinner?.classList.remove('d-none');
      if (generateBtn) {
        generateBtn.disabled = true;
        generateBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Generating…
        `;
    }

    try {
        // 2) Send to backend
        const fd = new FormData(form); // includes @csrf and all inputs
        const res = await fetch(form.action, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
          body: fd // important: no manual Content-Type for FormData
      });

        // 3) Handle response
        const ct = res.headers.get('content-type') || '';
        const data = ct.includes('application/json') ? await res.json() : { success:false, message:`Unexpected response (${res.status})` };

        if (data.success) {
          const made = data.questions_created ?? data.count_used ?? Number(fd.get('question_count') || 0);
          const ids  = Array.isArray(data.question_ids) ? ` (${data.question_ids.join(', ')})` : '';
          this.showToast(`Created ${made} questions${ids}`, 'success');
          bootstrap.Modal.getInstance(document.getElementById('questionGenerationModal'))?.hide();
          setTimeout(() => window.location.reload(), 1000);
      } else {
          this.showToast(data.message || 'Error generating questions', 'error');
      }
  } catch (err) {
    this.showToast(err?.message || 'Unknown error', 'error');
} finally {
        // 4) UI -> restore
        spinner?.classList.add('d-none');
        if (generateBtn) {
          generateBtn.disabled = false;
          generateBtn.innerHTML = originalBtnHTML;
      }
  }

      return false; // keep the browser on the page
  }


    // === TRACK MANAGEMENT ===
    static showAddTrack() {
        const modal = new bootstrap.Modal(document.getElementById('addTrackModal'));
        modal.show();
    }

    static async addTrack() {
        const form = document.getElementById('addTrackForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch(this.config.routes.addTrack, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Track added successfully', 'success');
                location.reload();
            } else {
                this.showToast(data.message || 'Error adding track', 'error');
            }
        } catch (error) {
            this.showToast('Error adding track', 'error');
        }
    }

    static async removeTrack(skillId, trackId) {
        if (!confirm('Remove this track from the skill?')) {
            return;
        }

        try {
            const response = await this.makeRequest(`${this.config.routes.removeTrack}/${trackId}`, 'DELETE');
            
            if (response.success) {
                this.showToast('Track removed successfully', 'success');
                location.reload();
            } else {
                this.showToast(response.message || 'Error removing track', 'error');
            }
        } catch (error) {
            this.showToast('Error removing track', 'error');
        }
    }

    // === VIDEO MANAGEMENT ===
    static async showAddVideo() {
        const modal = new bootstrap.Modal(document.getElementById('addVideoModal'));
        
        this.resetVideoModal();
        modal.show();
        await this.loadAvailableVideos();
    }

    static resetVideoModal() {
        document.getElementById('videoSearchBox').value = '';
        document.getElementById('selectedVideoPath').value = '';
        document.getElementById('selectedVideoTitle').value = '';
        document.getElementById('selectedVideoInfo').textContent = '';
        
        document.getElementById('videoLoadingSpinner').classList.remove('d-none');
        document.getElementById('videoList').classList.add('d-none');
        document.getElementById('noVideosFound').classList.add('d-none');
        document.getElementById('addVideoForm').classList.add('d-none');
        document.getElementById('linkVideoBtn').classList.add('d-none');
    }

    static async loadAvailableVideos() {
        try {
            const response = await this.makeRequest(this.config.routes.getVideos, 'GET');
            
            if (response.success) {
                this.availableVideos = response.videos || [];
                this.filteredVideos = [...this.availableVideos];
                
                document.getElementById('videoLoadingSpinner').classList.add('d-none');
                
                if (this.availableVideos.length === 0) {
                    document.getElementById('noVideosFound').classList.remove('d-none');
                } else {
                    document.getElementById('videoList').classList.remove('d-none');
                    this.renderVideoList();
                }
            } else {
                throw new Error(response.message || 'Failed to load videos');
            }
        } catch (error) {
            document.getElementById('videoLoadingSpinner').classList.add('d-none');
            document.getElementById('noVideosFound').classList.remove('d-none');
            this.showToast('Failed to load videos: ' + error.message, 'error');
        }
    }

    static renderVideoList() {
        const videoGrid = document.getElementById('videoGrid');
        
        if (this.filteredVideos.length === 0) {
            videoGrid.innerHTML = '<div class="col-12 text-center py-3 text-muted">No videos match your search.</div>';
            return;
        }
        
        videoGrid.innerHTML = this.filteredVideos.map(video => `
            <div class="col-md-6 col-lg-4">
            <div class="card video-card" style="cursor: pointer;" onclick="SkillManager.selectVideo('${video.path}', '${this.escapeHtml(video.name)}', '${this.escapeHtml(video.url || '')}')">
            <div class="position-relative" style="height: 120px; background: #f8f9fa;">
            ${video.url ? 
                `<video src="${video.url}#t=0.1" class="card-img-top h-100 w-100" style="object-fit: cover;" muted playsinline preload="metadata"></video>
                <div class="position-absolute top-0 end-0 m-2">
                <span class="badge bg-dark bg-opacity-75">
                <i class="fas fa-play me-1"></i>Video
                </span>
                </div>` : 
                `<div class="d-flex align-items-center justify-content-center h-100">
                <i class="fas fa-video fa-2x text-muted"></i>
                </div>`
            }
            </div>
            <div class="card-body p-2">
            <div class="small fw-bold text-truncate" title="${this.escapeHtml(video.name)}">${this.escapeHtml(video.name)}</div>
            <div class="small text-muted text-truncate" title="${this.escapeHtml(video.path)}">${this.escapeHtml(video.path)}</div>
            ${video.size ? `<div class="small text-muted">${this.formatFileSize(video.size)}</div>` : ''}
            </div>
            </div>
            </div>
            `).join('');
    }

    static selectVideo(path, name, url) {
        document.getElementById('selectedVideoPath').value = path;
        document.getElementById('selectedVideoTitle').value = this.generateVideoTitle(name);
        document.getElementById('selectedVideoInfo').textContent = `${name} (${path})`;
        
        document.getElementById('addVideoForm').classList.remove('d-none');
        document.getElementById('linkVideoBtn').classList.remove('d-none');
        
        document.querySelectorAll('.video-card').forEach(card => card.classList.remove('border-primary'));
        event.currentTarget.classList.add('border-primary');
    }

    static generateVideoTitle(filename) {
        return filename
        .replace(/\.[^/.]+$/, '')
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
    }

    static async linkSelectedVideo() {
        const path = document.getElementById('selectedVideoPath').value;
        const title = document.getElementById('selectedVideoTitle').value;
        
        if (!path || !title) {
            this.showToast('Please select a video and enter a title', 'error');
            return;
        }
        
        const linkBtn = document.getElementById('linkVideoBtn');
        const originalText = linkBtn.innerHTML;
        linkBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Linking...';
        linkBtn.disabled = true;
        
        try {
            const response = await this.makeRequest(this.config.routes.linkVideo, 'POST', {
                video_path: path,
                title: title
            });
            
            if (response.success) {
                this.showToast('Video linked successfully', 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addVideoModal'));
                modal.hide();
                location.reload();
            } else {
                this.showToast(response.message || 'Error linking video', 'error');
            }
        } catch (error) {
            this.showToast('Error linking video', 'error');
        } finally {
            linkBtn.innerHTML = originalText;
            linkBtn.disabled = false;
        }
    }

    static async deleteVideo(linkId) {
        if (!confirm('Delete this video?')) {
            return;
        }

        try {
            const response = await this.makeRequest(`${this.config.routes.deleteVideo}/${linkId}`, 'DELETE');
            
            if (response.success) {
                this.showToast('Video deleted successfully', 'success');
                location.reload();
            } else {
                this.showToast(response.message || 'Error deleting video', 'error');
            }
        } catch (error) {
            this.showToast('Error deleting video', 'error');
        }
    }

    // === SKILL MANAGEMENT ===
    static async copySkill(skillId) {
        if (!confirm('Create a copy of this skill?')) {
            return;
        }

        try {
            const response = await this.makeRequest(this.config.routes.skillCopy, 'POST');
            
            if (response.success) {
                this.showToast('Skill copied successfully', 'success');
                window.location.href = `/admin/skills/${response.skill.id}`;
            } else {
                this.showToast(response.message || 'Error copying skill', 'error');
            }
        } catch (error) {
            this.showToast('Error copying skill', 'error');
        }
    }

    static deleteSkill(skillId) {
        if (!confirm('Are you sure you want to delete this skill? This action cannot be undone.')) {
            return;
        }
        this.performSkillDelete(skillId, false);
    }

    static async performSkillDelete(skillId, delinkTracks = false) {
        const deleteButton = document.querySelector('a[onclick*="deleteSkill"], button[onclick*="deleteSkill"]');
        let originalHtml = '';
        
        if (deleteButton) {
            originalHtml = deleteButton.innerHTML;
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            deleteButton.disabled = true;
        }

        const requestBody = delinkTracks ? { delink_tracks: true } : {};

        try {
            const response = await this.makeRequest(this.config.routes.skillDelete, 'DELETE', requestBody);
            
            if (response.success || response.code === 200) {
                this.showToast('Skill deleted successfully', 'success');
                window.location.href = '/admin/skills';
            } else if (response.code === 409 && response.requires_confirmation) {
                if (deleteButton) {
                    deleteButton.innerHTML = originalHtml;
                    deleteButton.disabled = false;
                }
                
                if (confirm(response.message + '\n\nClick OK to delink tracks and delete the skill.')) {
                    this.performSkillDelete(skillId, true);
                }
            } else {
                if (deleteButton) {
                    deleteButton.innerHTML = originalHtml;
                    deleteButton.disabled = false;
                }
                this.showToast(response.message || 'An error occurred while deleting the skill.', 'error');
            }
        } catch (error) {
            if (deleteButton) {
                deleteButton.innerHTML = originalHtml;
                deleteButton.disabled = false;
            }
            
            this.showToast(`Error deleting skill: ${error.message}`, 'error');
        }
    }

    // === UTILITY FUNCTIONS ===
    static showBulkQuestions() {
        const modal = new bootstrap.Modal(document.getElementById('questionGenerationModal'));
        modal.show();
    }

    static exportQuestions() {
        window.location.href = this.config.routes.exportQuestions;
    }

    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    static formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    static async makeRequest(url, method = 'GET', data = null) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const config = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };

        if (data && (method === 'POST' || method === 'PATCH' || method === 'PUT' || method === 'DELETE')) {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const jsonResponse = await response.json();
            return jsonResponse;
        } catch (error) {
            throw error;
        }
    }

    static showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
        <strong>${type === 'error' ? 'Error' : type === 'success' ? 'Success' : 'Info'}:</strong> ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    SkillManager.init();
});
</script>
@endpush