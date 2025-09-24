@php
  $modalId   = $modalId   ?? 'questionGenerationModal';
  $actionUrl = $actionUrl ?? url('/admin/questions/generate'); // single generator endpoint
  $skillId   = $skillId   ?? null;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="fas fa-pen me-2"></i>Generate Questions for:
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- Skill context (light) --}}
        <div class="border rounded p-3 mb-3 bg-light">
          <div class="d-flex align-items-center mb-2">
            <i class="fas fa-info-circle me-2 text-primary"></i>
            <strong>Skill Context</strong>
          </div>
          <div class="small">
            <div><strong>Skill:</strong> {{ $skill->skill ?? '' }}</div>
            @if(!empty($skill?->description))
              <div class="text-muted text-truncate"><strong>Description:</strong> {{ $skill->description }}</div>
            @endif
          </div>
        </div>

        {{-- Choose method --}}
        <div class="mb-2 d-flex align-items-center">
          <i class="fas fa-cogs me-2 text-secondary"></i>
          <strong>Choose Generation Method</strong>
        </div>

        <div class="row g-3 mb-3" id="{{ $modalId }}_picker">
          <div class="col-12 col-md-6">
            <button type="button"
                    class="w-100 btn btn-outline-primary p-3 text-start d-flex align-items-start"
                    data-method="skills">
              <i class="fas fa-robot me-3 fs-3"></i>
              <span>
                <div class="fw-bold">Generate from Skills</div>
                <div class="small text-muted">Use the skill as context and create new questions.</div>
              </span>
            </button>
          </div>

          <div class="col-12 col-md-6">
            <button type="button"
                    class="w-100 btn btn-outline-primary p-3 text-start d-flex align-items-start"
                    data-method="questions">
              <i class="fas fa-wand-magic-sparkles me-3 fs-3"></i>
              <span>
                <div class="fw-bold">Generate from Questions</div>
                <div class="small text-muted">Use an existing question to create AI variations.</div>
              </span>
            </button>
          </div>
        </div>

        {{-- FORM: Generate from Skills --}}
        <form id="{{ $modalId }}_form_skills" class="d-none">
          <input type="hidden" name="skill_id" value="{{ $skillId }}">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">How many questions?</label>
              <select class="form-select" name="question_count" required>
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
              <select class="form-select" name="difficulty_distribution" required>
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
            <textarea class="form-control" name="focus_areas" rows="3"
                      placeholder="e.g., emphasise real-world use, include edge cases"></textarea>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="{{ $modalId }}_explain_sk" name="include_explanations" checked>
            <label class="form-check-label" for="{{ $modalId }}_explain_sk">Include explanations</label>
          </div>
          <input type="hidden" name="generation_method" value="ai">      {{-- server expects this --}}
          <input type="hidden" name="question_types" value="mixed">
        </form>

        {{-- FORM: Generate from Questions (Variations) --}}
        <form id="{{ $modalId }}_form_questions" class="d-none">
          <input type="hidden" name="skill_id" value="{{ $skillId }}">
          <div class="mb-3">
            <label class="form-label">Original Question</label>
            <div class="small text-muted mb-1">Passed in automatically when you click the “wand” action, or paste an ID below.</div>
            <input type="text" class="form-control" name="question_id" id="{{ $modalId }}_questionId" placeholder="Question ID">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">How many variations?</label>
              <select class="form-select" name="question_count" required>
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
              <select class="form-select" name="difficulty_distribution" required>
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
            <textarea class="form-control" name="focus_areas" rows="3"
                      placeholder="e.g., more applied scenarios, trick options, etc."></textarea>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="{{ $modalId }}_explain_q" name="include_explanations" checked>
            <label class="form-check-label" for="{{ $modalId }}_explain_q">Include explanations</label>
          </div>
          <input type="hidden" name="generation_method" value="ai_variation">
          <input type="hidden" name="question_types" value="same">
        </form>

        {{-- Progress area --}}
        <div id="{{ $modalId }}_progress" class="d-none">
          <div class="d-flex align-items-center mb-2">
            <div class="spinner-border me-3" role="status"><span class="visually-hidden">Loading…</span></div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Generating…</span>
                <span id="{{ $modalId }}_pct">0%</span>
              </div>
              <div class="progress">
                <div class="progress-bar" id="{{ $modalId }}_bar" style="width:0%"></div>
              </div>
            </div>
          </div>
          <div class="small text-muted" id="{{ $modalId }}_msgs"><div>Starting…</div></div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="{{ $modalId }}_submit" disabled>
          <i class="fas fa-magic me-2"></i>Generate
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  const modalId = @json($modalId);
  const actionUrl = @json($actionUrl);

  const $ = (id) => document.getElementById(id);
  const sel = (q, r=document) => r.querySelector(q);
  const selAll = (q, r=document) => r.querySelectorAll(q);

  function toast(msg, type='info') {
    const t = document.createElement('div');
    t.className = `alert alert-${type==='error'?'danger':type}`;
    t.style.cssText='position:fixed;top:20px;right:20px;z-index:9999;min-width:260px';
    t.innerHTML = `<strong>${type==='error'?'Error':type==='success'?'Success':'Info'}:</strong> ${msg}
      <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
    document.body.appendChild(t); setTimeout(()=>t.remove(), 4000);
  }

  let method = null; // 'skills' or 'questions'

  const picker = $(modalId + '_picker');
  const formSkills = $(modalId + '_form_skills');
  const formQuestions = $(modalId + '_form_questions');
  const progressBox = $(modalId + '_progress');
  const pct = $(modalId + '_pct');
  const bar = $(modalId + '_bar');
  const msgs = $(modalId + '_msgs');
  const submitBtn = $(modalId + '_submit');

  function showForm(name) {
    method = name;
    formSkills.classList.toggle('d-none', name !== 'skills');
    formQuestions.classList.toggle('d-none', name !== 'questions');
    submitBtn.disabled = false;
    // highlight selected
    selAll('[data-method]', picker).forEach(b=>{
      b.classList.toggle('btn-outline-primary', b.dataset.method !== name);
      b.classList.toggle('btn-primary', b.dataset.method === name);
    });
  }

  picker?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-method]');
    if (!btn) return;
    showForm(btn.dataset.method);
  });

  function startProgress() {
    progressBox.classList.remove('d-none');
    let p=0;
    const steps = ['Analyzing context…','Creating content…','Validating answers…','Almost done…'];
    let i=0;
    window.__genInterval = setInterval(()=>{
      p = Math.min(90, p + Math.random()*18);
      pct.textContent = Math.round(p) + '%';
      bar.style.width = Math.round(p) + '%';
      if (i < steps.length && p > (i+1)*20) { msgs.insertAdjacentHTML('beforeend', `<div>${steps[i++]}</div>`); }
    }, 900);
  }
  function stopProgress(doneText='Complete!') {
    if (window.__genInterval) clearInterval(window.__genInterval);
    pct.textContent = '100%'; bar.style.width='100%';
    msgs.insertAdjacentHTML('beforeend', `<div>${doneText}</div>`);
  }
  function resetUI() {
    progressBox.classList.add('d-none');
    pct.textContent = '0%'; bar.style.width='0%'; msgs.innerHTML = '<div>Starting…</div>';
    submitBtn.disabled = (method===null);
  }

  // Submit handler
  submitBtn?.addEventListener('click', async () => {
    if (!method) return toast('Choose a generation method first.', 'error');

    const fd = new FormData(method === 'skills' ? formSkills : formQuestions);

    // enforce required backend fields
    if (method === 'skills') {
      fd.set('generation_method', 'ai');         // bulk by skill
      if (!fd.get('skill_id')) fd.set('skill_id', '{{ $skillId }}');
    } else {
      fd.set('generation_method', 'ai_variation'); // variations from question
      if (!fd.get('question_id')) {
        return toast('Please provide a Question ID (select via “wand” or paste an ID).', 'error');
      }
    }

    // Progress on
    submitBtn.disabled = true;
    startProgress();

    try {
      const res = await fetch(actionUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        body: fd
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);

      if (json.success) {
        stopProgress('Generated successfully!');
        setTimeout(() => {
          bootstrap.Modal.getInstance(document.getElementById(modalId))?.hide();
          // notify page; callers can refresh if they want
          window.dispatchEvent(new CustomEvent('question-generation:success', { detail: json }));
          toast(
            method==='skills'
              ? `${json.generated_count ?? fd.get('question_count')} questions generated!`
              : `${json.questions_created ?? fd.get('question_count')} variations generated!`,
            'success'
          );
        }, 600);
      } else {
        throw new Error(json.message || 'Generation failed');
      }
    } catch (e) {
      toast(e.message || 'Network error', 'error');
    } finally {
      submitBtn.disabled = false;
      resetUI();
    }
  });

  // Public opener (works with your SkillManager)
  window.QuestionGeneration = window.QuestionGeneration || {};
  window.QuestionGeneration[modalId] = {
    open({ mode=null, questionId=null, questionText='', skillId=null } = {}) {
      if (skillId) {
        // set into both forms for safety
        formSkills.querySelector('[name="skill_id"]')?.setAttribute('value', skillId);
        formQuestions.querySelector('[name="skill_id"]')?.setAttribute('value', skillId);
      }
      if (questionId) {
        formQuestions.querySelector('[name="question_id"]').value = questionId;
      }
      // default tab
      showForm(mode ?? (questionId ? 'questions' : 'skills'));
      resetUI();
      new bootstrap.Modal(document.getElementById(modalId)).show();
    }
  };
})();
</script>
@endpush
