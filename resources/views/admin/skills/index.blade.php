@extends('layouts.admin')

@section('title', 'Skills Management')

@push('styles')
<style>
    .filtered-out { display: none !important; }
    .search-highlight { background: yellow; font-weight: bold; }
    .option-card { cursor: pointer; border: 2px solid transparent; transition: border-color .15s, box-shadow .15s; }
    .option-card:hover { border-color: #0d6efd; box-shadow: 0 0 8px rgba(13,110,253,.15); }
    .option-card.active { border-color: #0d6efd; background-color: #f0f7ff; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    @include('admin.components.page-header', [
    'title' => 'Skills Management',
    'subtitle' => 'Manage learning skills and their content',
    'icon' => 'brain',
    'actions' => [['text' => 'Create New Skill', 'url' => route('admin.skills.create'), 'icon' => 'plus', 'class' => 'primary']]
    ])

    {{-- Statistics Row --}}
    @include('admin.components.stats-row', [
    'stats' => [
    ['value' => $skills->count(), 'label' => 'Total Skills', 'color' => 'primary', 'icon' => 'brain', 'id' => 'totalskillsCount'],
    ['value' => $skills->where('status_id', 3)->count(), 'label' => 'Active Skills', 'color' => 'success', 'icon' => 'check-circle', 'id' => 'activeskillsCount'],
    ['value' => $skills->where('status_id', 4)->count(), 'label' => 'Draft Skills', 'color' => 'warning', 'icon' => 'edit', 'id' => 'draftskillsCount'],
    ['value' => $skills->sum('questions_count'), 'label' => 'Total Questions', 'color' => 'info', 'icon' => 'question-circle', 'id' => 'totalquestionsCount']
    ]
    ])

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
            <div>
                <span id="resultsCount" class="badge bg-primary me-2">{{ $skills->count() }} results</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">Clear All</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <select id="trackFilter" class="form-select">
                        <option value="">All Tracks</option>
                        @foreach($skills->pluck('tracks')->flatten()->unique('id')->sortBy('track') as $track)
                        <option value="{{ $track->id }}">{{ $track->track }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="levelFilter" class="form-select">
                        <option value="">All Levels</option>
                        @foreach($skills->pluck('tracks')->flatten()->pluck('level')->whereNotNull()->unique('id')->sortBy('level') as $level)
                        <option value="{{ $level->id }}">{{ $level->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        @foreach($skills->pluck('status')->filter()->unique('id')->sortBy('status') as $status)
                        <option value="{{ $status->id }}">{{ $status->status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="questionsFilter" class="form-select">
                        <option value="">Any Questions</option>
                        <option value="has">Has Questions</option>
                        <option value="none">No Questions</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="search" id="searchBox" class="form-control" placeholder="Search skills...">
                </div>
            </div>
        </div>
    </div>

    {{-- Skills Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Skills Overview</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Skill</th>
                            <th>Tracks & Levels</th>
                            <th>Content</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($skills as $skill)
                        <tr class="skill-row" 
                        data-id="{{ $skill->id }}"
                        data-name="{{ strtolower($skill->skill) }}"
                        data-desc="{{ strtolower($skill->description ?? '') }}"
                        data-status="{{ $skill->status_id }}"
                        data-tracks="{{ $skill->tracks->pluck('id')->implode(',') }}"
                        data-levels="{{ $skill->tracks->pluck('level.id')->filter()->implode(',') }}"
                        data-questions="{{ $skill->questions ? $skill->questions->count() : 0 }}">
                        <td>
                            <div>
                                <h6 class="mb-0 skill-name">{{ $skill->skill }}</h6>
                                <small class="text-muted skill-desc">
                                    @if(strlen($skill->description ?? '') > 60)
                                    {{ substr($skill->description, 0, 60) }}...
                                    @else
                                    {{ $skill->description }}
                                    @endif
                                </small>
                            </div>
                        </td>
                        <td>
                            @forelse($skill->tracks->take(2) as $track)
                            <span class="badge bg-info me-1">
                                {{ $track->track }}{{ $track->level ? ' ('.$track->level->description.')' : '' }}
                            </span>
                            @empty
                            <span class="text-muted">No tracks</span>
                            @endforelse
                            @if($skill->tracks->count() > 2)
                            <span class="badge bg-secondary">+{{ $skill->tracks->count() - 2 }}</span>
                            @endif
                        </td>
                        <td>
                            @if($skill->questions && $skill->questions->count() > 0)
                            <span class="badge bg-primary">{{ $skill->questions->count() }} Questions</span>
                            @else
                            <span class="text-muted">No questions</span>
                            @endif
                        </td>
                        <td>
                            @if($skill->status)
                            <span class="badge bg-{{ $skill->status->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($skill->status->status) }}
                            </span>
                            @else
                            <span class="badge bg-secondary">Unknown</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.skills.show', $skill) }}" class="btn btn-outline-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-outline-success" onclick="showBulkQuestions({{ $skill->id }})" title="Add Questions">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteSkill({{ $skill->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            @include('admin.components.empty-state', [
                            'icon' => 'brain',
                            'title' => 'No skills found',
                            'message' => 'Create your first skill to get started'
                            ])
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div id="noResults" class="d-none text-center py-5">
            @include('admin.components.empty-state', [
            'icon' => 'search',
            'title' => 'No skills found',
            'message' => 'Try adjusting your search filters'
            ])
        </div>
    </div>
</div>
</div>

{{-- Inline Question Generation Modal (two options) --}}
<div class="modal fade" id="questionGenerationModal" tabindex="-1" aria-labelledby="questionGenerationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="questionGenerationModalLabel">
          <i class="fas fa-pen me-2"></i> Generate Questions for:
      </h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>

  <div class="modal-body">
    {{-- Skill context --}}
    <input type="hidden" id="currentSkillId">
    <div class="border rounded p-3 mb-3 bg-light">
      <div class="d-flex align-items-center mb-2">
        <i class="fas fa-info-circle me-2 text-primary"></i>
        <strong>Skill Context</strong>
    </div>
    <div class="small">
        <div><strong>Skill:</strong> <span id="skillNameDisplay"></span></div>
        <div class="text-muted"><strong>Description:</strong> <span id="skillDescriptionDisplay"></span></div>
        <div class="text-muted"><strong>Current No. of Questions:</strong> <span id="currentQuestionsDisplay"></span></div>
    </div>
</div>

{{-- Form: Generate from Skills --}}
<form id="form_generate_skills" class="d-none">
  <input type="hidden" name="skill_id" id="form_skill_id" value="">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">How many questions?</label>
      <select class="form-select" name="question_count">
        <option value="5">5</option>
        <option value="10" selected>10</option>
        <option value="15">15</option>
        <option value="20">20</option>
        <option value="25">25</option>
        <option value="30">30</option>
    </select>
</div>
<div class="col-md-6 mb-3">
  <label class="form-label">Difficulty</label>
  <select class="form-select" name="difficulty_distribution">
    <option value="auto" selected>Auto (Mixed)</option>
    <option value="easy">Easy</option>
    <option value="medium">Medium</option>
    <option value="hard">Hard</option>
    <option value="progressive">Progressive (Easy→Hard)</option>
</select>
</div>
</div>
<div class="mb-3">
    <label class="form-label">Additional instructions (optional)</label>
    <textarea class="form-control" name="focus_areas" rows="3" placeholder="e.g., emphasise real-world use, include edge cases"></textarea>
</div>
<div class="form-check form-switch mb-2">
    <input class="form-check-input" type="checkbox" id="include_explanations_sk" name="include_explanations" checked>
    <label class="form-check-label" for="include_explanations_sk">Include explanations</label>
</div>

<input type="hidden" name="generation_method" value="ai">
<input type="hidden" name="question_types" value="mixed">
</form>

{{-- Form: Generate from Questions (Variations) --}}
<form id="form_generate_questions" class="d-none">
  <input type="hidden" name="skill_id" value="">
  <div class="mb-3">
    <label class="form-label">Original Question ID</label>
    <input type="text" class="form-control" name="question_id" id="form_question_id" placeholder="Paste question ID or leave blank to fill from list">
    <div class="small text-muted mt-1">When opened from a question row the ID will be prefilled.</div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label">How many variations?</label>
      <select class="form-select" name="question_count">
        <option value="3" selected>3</option>
        <option value="5">5</option>
        <option value="8">8</option>
        <option value="10">10</option>
        <option value="15">15</option>
        <option value="20">20</option>
    </select>
</div>
<div class="col-md-6 mb-3">
  <label class="form-label">Target difficulty</label>
  <select class="form-select" name="difficulty_distribution">
    <option value="same">Same as original</option>
    <option value="mixed" selected>Mixed</option>
    <option value="easy">Easy</option>
    <option value="medium">Medium</option>
    <option value="hard">Hard</option>
    <option value="progressive">Progressive (Easy→Hard)</option>
</select>
</div>
</div>

<div class="mb-3">
    <label class="form-label">Additional instructions (optional)</label>
    <textarea class="form-control" name="focus_areas" rows="3" placeholder="e.g., more applied scenarios, trick options, etc."></textarea>
</div>
<input type="hidden" name="generation_method" value="ai_variation">
<input type="hidden" name="question_types" value="same">
</form>

{{-- Progress area --}}
<div id="generation_progress" class="d-none">
  <div class="d-flex align-items-center mb-2">
    <div class="spinner-border me-3" role="status"><span class="visually-hidden">Loading…</span></div>
    <div class="flex-grow-1">
      <div class="d-flex justify-content-between mb-1">
        <span class="fw-semibold">Generating…</span>
        <span id="generation_pct">0%</span>
    </div>
    <div class="progress">
        <div class="progress-bar" id="generation_bar" style="width:0%"></div>
    </div>
</div>
</div>
<div class="small text-muted" id="generation_msgs"><div>Starting…</div></div>
</div>

</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" id="generation_submit" disabled>
      <i class="fas fa-magic me-2"></i> Generate
  </button>
</div>
</div>
</div>
</div>
{{-- End modal --}}

@endsection

@push('scripts')
<script>
    /* ---------- existing table + filter code (unchanged) ---------- */
    const skills = Array.from(document.querySelectorAll('.skill-row')).map(row => ({
        element: row,
        id: row.dataset.id,
        name: row.dataset.name,
        desc: row.dataset.desc,
        status: row.dataset.status,
        tracks: row.dataset.tracks.split(',').filter(Boolean),
        levels: row.dataset.levels.split(',').filter(Boolean),
        questions: parseInt(row.dataset.questions)
    }));

    let filtered = [...skills];

    ['trackFilter', 'levelFilter', 'statusFilter', 'questionsFilter'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', applyFilters);
    });
    document.getElementById('searchBox')?.addEventListener('input', debounce(applyFilters, 300));

    function applyFilters() {
        const filters = {
            track: document.getElementById('trackFilter').value,
            level: document.getElementById('levelFilter').value,
            status: document.getElementById('statusFilter').value,
            questions: document.getElementById('questionsFilter').value,
            search: document.getElementById('searchBox').value.toLowerCase()
        };

        filtered = skills.filter(skill => {
            if (filters.track && !skill.tracks.includes(filters.track)) return false;
            if (filters.level && !skill.levels.includes(filters.level)) return false;
            if (filters.status && skill.status !== filters.status) return false;
            if (filters.questions === 'has' && skill.questions === 0) return false;
            if (filters.questions === 'none' && skill.questions > 0) return false;
            if (filters.search && !skill.name.includes(filters.search) && !skill.desc.includes(filters.search)) return false;
            return true;
        });

        skills.forEach(skill => skill.element.classList.toggle('filtered-out', !filtered.includes(skill)));
        document.getElementById('resultsCount').textContent = `${filtered.length} results`;
        document.getElementById('noResults').classList.toggle('d-none', filtered.length > 0);

        updateStats();
        highlightSearch(filters.search);
    }

    function updateStats() {
        document.getElementById('totalskillsCount').textContent = filtered.length;
        document.getElementById('activeskillsCount').textContent = filtered.filter(s => s.status === '3').length;
        document.getElementById('draftskillsCount').textContent = filtered.filter(s => s.status === '4').length;
        document.getElementById('totalquestionsCount').textContent = filtered.reduce((sum, s) => sum + s.questions, 0);
    }

    function highlightSearch(term) {
        document.querySelectorAll('.search-highlight').forEach(el => el.outerHTML = el.textContent);
        if (term) {
            const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            document.querySelectorAll('.skill-name, .skill-desc').forEach(el => {
                el.innerHTML = el.textContent.replace(regex, '<span class="search-highlight">$1</span>');
            });
        }
    }

    function clearFilters() {
        ['trackFilter', 'levelFilter', 'statusFilter', 'questionsFilter', 'searchBox'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        applyFilters();
    }

    function debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /* ---------- modal + generation UI logic (new) ---------- */

    function showBulkQuestions(skillId) {
        fetch(`/admin/skills/${skillId}/data`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // populate context area
                document.getElementById('currentSkillId').value = data.skill.id;
                document.getElementById('skillNameDisplay').textContent = data.skill.skill;
                document.getElementById('skillDescriptionDisplay').textContent = data.skill.description || 'No description';
                document.getElementById('currentQuestionsDisplay').textContent = data.skill.questions_count || 0;

                // set form skill ids
                document.getElementById('form_skill_id').value = data.skill.id;
                openGenerationModal({ mode: 'skills' });
            } else {
                alert('Failed to load skill data');
            }
        })
        .catch(() => alert('Error loading skill data'));
    }

    function deleteSkill(skillId) {
        if (confirm('Delete this skill?')) {
            fetch(`/admin/skills/${skillId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(r => r.json())
            .then(data => data.success ? location.reload() : alert('Error deleting skill'));
        }
    }

    /* GENERATION MODAL HANDLERS */
    (function() {
        const modalId = 'questionGenerationModal';
        const picker = document.getElementById('generationPicker');
        const cards = picker?.querySelectorAll('[data-method]') || [];
        const formSkills = document.getElementById('form_generate_skills');
        const progressBox = document.getElementById('generation_progress');
        const bar = document.getElementById('generation_bar');
        const pct = document.getElementById('generation_pct');
        const msgs = document.getElementById('generation_msgs');
        const submitBtn = document.getElementById('generation_submit');

        let selectedMethod = null;
        let genInterval = null;

        function enableSubmit(enable) {
            submitBtn.disabled = !enable;
        }

        function resetGenerationUI() {
        // hide progress, show appropriate form
        progressBox.classList.add('d-none');
        bar.style.width = '0%';
        pct.textContent = '0%';
        msgs.innerHTML = '<div>Starting…</div>';
        enableSubmit(selectedMethod !== null);
    }

    function showFormFor(method) {
        selectedMethod = method;
        formSkills.classList.toggle('d-none', method !== 'skills');
        cards.forEach(c => { c.classList.toggle('active', c.dataset.method === method); });
        enableSubmit(true);
    }

    // picker click
    picker?.addEventListener('click', (e) => {
        const tile = e.target.closest('[data-method]');
        if (!tile) return;
        showFormFor(tile.dataset.method);
        resetGenerationUI();
    });

    // open helper (used by showBulkQuestions and other callers)
    window.openGenerationModal = function({ mode = null, questionId = null, questionText = '', skillId = null } = {}) {
        // prefill skill id for forms
        if (skillId) {
            document.getElementById('form_skill_id').value = skillId;
        }
        // default selection
        const defaultMode = mode ? mode : (questionId ? 'questions' : 'skills');
        showFormFor(defaultMode);
        resetGenerationUI();

        new bootstrap.Modal(document.getElementById(modalId)).show();
    };

    // progress simulation
    function startProgress() {
        progressBox.classList.remove('d-none');
        let p = 0;
        const steps = ['Analyzing skill context…','Creating questions…','Validating answers…','Almost done…'];
        let i = 0;
        genInterval = setInterval(() => {
            p = Math.min(90, p + Math.random()*18);
            bar.style.width = Math.round(p) + '%';
            pct.textContent = Math.round(p) + '%';
            if (i < steps.length && p > (i+1)*20) {
                msgs.insertAdjacentHTML('beforeend', `<div>${steps[i++]}</div>`);
            }
        }, 800);
    }

    function stopProgress(finalMessage = 'Complete!') {
        if (genInterval) clearInterval(genInterval);
        bar.style.width = '100%';
        pct.textContent = '100%';
        msgs.insertAdjacentHTML('beforeend', `<div>${finalMessage}</div>`);
    }

    // submit action
    submitBtn?.addEventListener('click', async () => {
        if (!selectedMethod) {
            alert('Please choose a generation method.');
            return;
        }

        // choose form and build FormData
        const formEl = formSkills;

        const fd = new FormData(formEl);

        // ensure required backend keys
        if (selectedMethod === 'skills') {
            fd.set('generation_method', 'ai');
            if (!fd.get('skill_id')) fd.set('skill_id', document.getElementById('currentSkillId').value || document.getElementById('form_skill_id').value);
        }

        // UI
        submitBtn.disabled = true;
        startProgress();

        try {
            const res = await fetch(@json(url('/admin/questions/generate')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: fd
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);

            if (json.success) {
                stopProgress('Generated successfully!');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('questionGenerationModal'))?.hide();
                    // notify page to update if desired
                    window.dispatchEvent(new CustomEvent('question-generation:success', { detail: json }));
                    // show toast (simple)
                    const msg = selectedMethod === 'skills'
                    ? `${json.generated_count ?? fd.get('question_count')} questions generated!`
                    : `${json.questions_created ?? fd.get('question_count')} variations generated!`;
                    // lightweight toast using alert for now
                    // replace with your nicer toast function if available
                    alert(msg);
                    // optionally refresh or update table...
                }, 700);
            } else {
                throw new Error(json.message || 'Generation failed');
            }
        } catch (err) {
            alert(err.message || 'Error generating. Try again.');
        } finally {
            submitBtn.disabled = false;
            // reset progress simulation
            if (genInterval) clearInterval(genInterval);
            bar.style.width = '0%';
            pct.textContent = '0%';
            msgs.innerHTML = '<div>Starting…</div>';
            progressBox.classList.add('d-none');
        }
    });

})();
document.addEventListener('DOMContentLoaded', applyFilters);
</script>
@endpush
