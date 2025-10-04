<!-- Modal -->
<div class="modal fade" id="questionGenerationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="questionGenerationForm" method="POST" action="{{ route('admin.questions.generateQuestions') }}"onsubmit="return SkillManager.generateVariations(event);">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Generate Questions</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <!-- hidden IDs -->
          <input type="hidden" name="skill_id" value="{{ $skill->id }}">
          <input type="hidden" name="question_id" id="selectedQuestionId">

          <!-- number of questions -->
          <div class="mb-3">
            <label class="form-label">Number of Questions</label>
            <select class="form-select" name="question_count">
              <option value="3">3</option>
              <option value="5" selected>5</option>
              <option value="10">10</option>
            </select>
          </div>

          <!-- source -->
          <div class="mb-3">
            <label class="form-label">Generate Based On</label><br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="source" value="skill" checked>
              <label class="form-check-label">Skill</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="source" value="question">
              <label class="form-check-label">Question</label>
            </div>
          </div>

          <!-- spinner -->
          <div id="loadingSpinner" class="text-center d-none">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Generating...</span>
            </div>
            <p class="mt-2">Generating questions…</p>
          </div>
        </div>

        <div class="modal-footer">
          <button id="generateBtn" type="submit" class="btn btn-primary">Generate</button>
        </div>
      </form>
    </div>
  </div>
</div>