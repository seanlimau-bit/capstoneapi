

<?php $__env->startSection('title', 'View Question #' . $question->id); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.css">
<link rel="stylesheet" href="<?php echo e(('/css/admin-question.css')); ?>">
<style>
  /* Small, tidy tweaks for inline add/edit rows */
  .inline-add-box { display:none; background:#f8f9fa; border:1px dashed #ced4da; border-radius:.5rem; padding:1rem; }
  .inline-actions { gap:.5rem; }
  .editable-field { cursor: pointer; position: relative; }
  .editable-field .edit-icon { opacity:.5; }
  .editable-field:hover .edit-icon { opacity:1; }
  .mcq-option { border:1px solid #dee2e6; border-radius:.5rem; padding:.75rem; display:flex; gap:1rem; align-items:flex-start; }
  .mcq-option-label { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600; background:#e9ecef; position:relative; }
  .image-preview-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1055; }
  .image-preview-content { max-width:700px; margin:4rem auto; background:#fff; padding:1rem 1.25rem; border-radius:.5rem; box-shadow:0 1rem 3rem rgba(0,0,0,.2); }
  .preview-image { max-width:100%; height:auto; border-radius:.25rem; }
  .file-info { font-size:.875rem; color:#6c757d; margin:.75rem 0; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid" data-question-id="<?php echo e($question->id); ?>">
  
  <?php echo $__env->make('admin.components.page-header', [
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
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

  <div class="row">
    
    <div class="col-lg-8">
      
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="fas fa-question-circle me-2"></i>Question Content
            <span class="badge bg-<?php echo e($question->type_id == 1 ? 'primary' : 'success'); ?> ms-2">
              <?php echo e($question->type->type ?? 'Unknown Type'); ?>

            </span>
          </h5>
        </div>
        <div class="card-body">
          
          <div class="mb-4" id="question-image-section">
            <label class="form-label text-muted small">QUESTION IMAGE</label>
            <?php if($question->question_image): ?>
            <div class="image-container">
              <div class="image-wrapper">
                <img src="<?php echo e(Storage::url($question->question_image)); ?>"
                alt="Question Image" class="question-image">
                <div class="image-overlay">
                  <button class="btn btn-light btn-sm" onclick="changeQuestionImage(<?php echo e($question->id); ?>)" title="Change Image">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-danger btn-sm" onclick="removeQuestionImage(<?php echo e($question->id); ?>)" title="Remove Image">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            <?php else: ?>
            <div class="upload-area" onclick="addQuestionImage(<?php echo e($question->id); ?>)">
              <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
              <h5 class="text-muted mb-2">Upload Question Image</h5>
              <p class="text-muted small mb-3">Click to browse or drag and drop</p>
              <p class="text-muted small">Supports: JPG, PNG, GIF, WebP (Max 6MB)</p>
            </div>
            <?php endif; ?>
          </div>

          
          <div class="mb-4">
            <label class="form-label text-muted small">QUESTION TEXT</label>
            <?php if($question->type_id == 2): ?>
            <div class="editable-field question-field html-content"
            data-field="question"
            data-id="<?php echo e($question->id); ?>"
            data-type="html"
            title="Click to edit HTML content">
            <div class="fib-content large">
              <?php echo $question->question; ?>

            </div>
            <i class="fas fa-code text-muted ms-2 edit-icon"></i>
          </div>
          <small class="text-muted">This question supports HTML and KaTeX mathematical notation</small>

          
          <?php
          $blankCount = substr_count($question->question, '___') ?:
          substr_count($question->question, '[blank]') ?:
          substr_count($question->question, '____') ?: 1;

          $answers = [];
          if (!empty($question->answer0)) $answers[] = $question->answer0;
          if (!empty($question->answer1)) $answers[] = $question->answer1;
          if (!empty($question->answer2)) $answers[] = $question->answer2;
          if (!empty($question->answer3)) $answers[] = $question->answer3;
          ?>

          <div class="mt-3">
            <label class="form-label text-muted small">EXPECTED ANSWERS (for each blank):</label>
            <?php for($i = 0; $i < min($blankCount, 4); $i++): ?>
            <div class="mb-2">
              <span class="text-muted me-2">Blank <?php echo e($i + 1); ?>:</span>
              <div class="editable-field answer-field d-inline-block"
              data-field="answer<?php echo e($i); ?>"
              data-id="<?php echo e($question->id); ?>"
              data-type="text"
              style="min-width: 200px; border-bottom: 1px solid #dee2e6; padding: 2px 5px;">
              <?php echo e($answers[$i] ?? 'Not set'); ?>

              <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <?php else: ?>
        <div class="editable-field question-field"
        data-field="question"
        data-id="<?php echo e($question->id); ?>"
        data-type="textarea"
        title="Click to edit">
        <?php echo e($question->question); ?>

        <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
      </div>
      <?php endif; ?>
    </div>

    
    <?php if($question->type_id == 1): ?>
    <?php
    $mcqOptions = [
    'A' => ['text' => $question->answer0, 'image' => $question->answer0_image, 'index' => 0],
    'B' => ['text' => $question->answer1, 'image' => $question->answer1_image, 'index' => 1],
    'C' => ['text' => $question->answer2, 'image' => $question->answer2_image, 'index' => 2],
    'D' => ['text' => $question->answer3, 'image' => $question->answer3_image, 'index' => 3]
    ];
    $validOptions = array_filter($mcqOptions, fn($o) => !empty($o['text']) || !empty($o['image']));
    ?>

    <?php if(count($validOptions) > 0): ?>
    <div class="mb-4">
      <label class="form-label text-muted small">MULTIPLE CHOICE OPTIONS</label>
      <?php $__currentLoopData = $validOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $letter => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="mcq-option <?php echo e($question->correct_answer == $option['index'] ? 'border-success' : ''); ?>" data-option-index="<?php echo e($option['index']); ?>">
        <div class="mcq-option-label <?php echo e($question->correct_answer == $option['index'] ? 'bg-success text-white' : ''); ?>">
          <?php echo e($letter); ?>

          <?php if($question->correct_answer == $option['index']): ?>
          <i class="fas fa-check position-absolute" style="font-size: 10px; top: 2px; right: 2px;"></i>
          <?php endif; ?>
        </div>
        <div class="flex-grow-1">
          <?php if($option['text']): ?>
          <div class="editable-field mb-2"
          data-field="answer<?php echo e($option['index']); ?>"
          data-id="<?php echo e($question->id); ?>"
          data-type="text"
          title="Click to edit option <?php echo e($letter); ?>">
          <?php echo e($option['text']); ?>

          <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
        </div>
        <?php endif; ?>

        
        <?php if($option['image']): ?>
        <div class="answer-image-container">
          <div class="image-wrapper-small">
            <img src="<?php echo e(Storage::url($option['image'])); ?>" alt="Option <?php echo e($letter); ?> Image" class="answer-image">
            <div class="image-overlay-small">
              <button class="btn btn-light btn-sm" onclick="changeAnswerImage(<?php echo e($question->id); ?>, <?php echo e($option['index']); ?>)" title="Change">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-danger btn-sm" onclick="removeAnswerImage(<?php echo e($question->id); ?>, <?php echo e($option['index']); ?>)" title="Remove">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="upload-area-small" onclick="addAnswerImage(<?php echo e($question->id); ?>, <?php echo e($option['index']); ?>)">
          <i class="fas fa-plus me-2"></i>Add Image for Option <?php echo e($letter); ?>

        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  
  <div class="mb-4">
    <label class="form-label text-muted small">CORRECT ANSWER</label>
    <div class="correct-answer-selector-wrapper">
      <select class="form-select form-select-sm correct-answer-selector"
      data-field="correct_answer"
      data-id="<?php echo e($question->id); ?>"
      data-current="<?php echo e($question->correct_answer); ?>">
      <option value="">Select correct answer...</option>
      <?php $__currentLoopData = $validOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $letter => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($option['index']); ?>" <?php echo e($question->correct_answer == $option['index'] ? 'selected' : ''); ?>>
        Option <?php echo e($letter); ?>

      </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </div>
</div>
<?php else: ?>
<div class="mb-4">
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    This MCQ question has no options defined.
  </div>
</div>
<?php endif; ?>
<?php else: ?>

<?php endif; ?>


<div class="row">
  <div class="col-md-6 mb-4">
    <label class="form-label text-muted small">QA STATUS</label>
    <div class="qa-status-selector-wrapper">
      <select class="form-select form-select-sm qa-status-selector"
      data-field="qa_status"
      data-id="<?php echo e($question->id); ?>"
      data-current="<?php echo e($question->qa_status); ?>">
      <option value="">Select status...</option>
      <?php $__currentLoopData = $qaStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <option value="<?php echo e($status['value']); ?>" <?php echo e($status['value'] == ($question->qa_status ?? '') ? 'selected' : ''); ?>>
        <?php echo e($status['label']); ?>

      </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </div>
</div>

<div class="col-md-6 mb-4">
  <label class="form-label text-muted small">DIFFICULTY</label>
  <div class="difficulty-selector-wrapper">
    <select class="form-select form-select-sm difficulty-selector"
    data-field="difficulty_id"
    data-id="<?php echo e($question->id); ?>"
    data-current="<?php echo e($question->difficulty_id); ?>">
    <option value="">Select difficulty...</option>
    <?php $__currentLoopData = $difficulties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $difficulty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($difficulty->id); ?>" <?php echo e($difficulty->id == ($question->difficulty_id ?? '') ? 'selected' : ''); ?>>
      <?php echo e($difficulty->short_description); ?>

    </option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
</div>

<div class="col-md-6 mb-4">
  <label class="form-label text-muted small">TYPE</label>
  <div class="type-selector-wrapper">
    <select class="form-select form-select-sm type-selector"
    data-field="type_id"
    data-id="<?php echo e($question->id); ?>"
    data-current="<?php echo e($question->type_id); ?>">
    <option value="">Select type...</option>
    <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($type->id); ?>" <?php echo e($type->id == ($question->type_id ?? '') ? 'selected' : ''); ?>>
      <?php echo e($type->type); ?>

    </option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
</div>
<div class="col-md-6 mb-4">
  <label class="form-label text-muted small">STATUS</label>
  <div class="status-selector-wrapper">
    <select class="form-select form-select-sm"
    data-field="status_id"
    data-id="<?php echo e($question->id); ?>"
    data-current="<?php echo e($question->status_id); ?>">
    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <option value="<?php echo e($st->id); ?>" <?php echo e((int)$st->id === (int)$question->status_id ? 'selected' : ''); ?>>
      <?php echo e($st->status); ?>

    </option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </select>
</div>
</div>
</div>
</div>
</div>


<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-lightbulb me-2"></i>Explanation, Hints & Solutions
    </h5>
  </div>
  <div class="card-body">
    
    <div class="mb-4">
      <label class="form-label text-muted small">EXPLANATION</label>
      <div class="editable-field"
      data-field="explanation"
      data-id="<?php echo e($question->id); ?>"
      data-type="textarea"
      title="Click to edit">
      <?php echo e($question->explanation ?: 'No explanation provided'); ?>

      <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
    </div>
  </div>

  
  <div class="mb-4">
    <label class="form-label text-muted small">HINTS</label>

    
    <?php if($question->hints && $question->hints->count() > 0): ?>
    <div class="hints-container">
      <?php $__currentLoopData = $question->hints->sortBy('hint_level'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hint): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="hint-item mb-2 p-2 border-start border-3 border-info bg-light" data-hint-id="<?php echo e($hint->id); ?>">
        <div class="d-flex justify-content-between align-items-start">
          <div class="flex-grow-1 d-flex align-items-center gap-2">
            <span class="badge bg-info me-2">Level</span>
            <span class="editable-field editable-hint-level d-inline-block"
            data-hint-id="<?php echo e($hint->id); ?>"
            data-field="hint_level"
            data-type="number"
            title="Click to edit level">
            <?php echo e($hint->hint_level); ?>

            <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
          </span>
          <span class="editable-field editable-hint-text d-inline-block ms-2"
          data-hint-id="<?php echo e($hint->id); ?>"
          data-field="hint_text"
          data-type="textarea"
          title="Click to edit hint text">
          <?php echo e($hint->hint_text); ?>

          <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
        </span>
      </div>
      <div class="d-flex inline-actions">
        <button class="btn btn-sm btn-outline-danger delete-hint"
        data-hint-id="<?php echo e($hint->id); ?>"
        title="Delete hint">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <?php if($hint->user): ?>
  <small class="text-muted">Added by <?php echo e($hint->user->name); ?></small>
  <?php endif; ?>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php else: ?>
<p class="text-muted">No hints available</p>
<?php endif; ?>


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
</div>


<div class="mb-2">
  <label class="form-label text-muted small">SOLUTIONS</label>

  
  <?php if($question->solutions && $question->solutions->count() > 0): ?>
  <div class="solutions-container">
    <?php $__currentLoopData = $question->solutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $solution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="solution-item mb-3 p-3 border rounded bg-light" data-solution-id="<?php echo e($solution->id); ?>">
      <div class="solution-content editable-field editable-solution"
      data-solution-id="<?php echo e($solution->id); ?>"
      data-field="solution"
      data-type="textarea"
      title="Click to edit solution">
      <?php echo nl2br(e($solution->solution)); ?>

      <i class="fas fa-edit text-muted ms-2 edit-icon"></i>
    </div>

    <div class="mt-2 d-flex justify-content-between align-items-center">
      <small class="text-muted">
        <i class="fas fa-user me-1"></i>
        By <?php echo e($solution->user->name ?? 'Unknown'); ?> on <?php echo e(optional($solution->created_at)->format('M d, Y')); ?>

      </small>
      <div class="d-flex align-items-center gap-2">
        
        <select class="form-select form-select-sm"
        data-field="status_id"
        data-id="<?php echo e($question->id); ?>"
        data-current="<?php echo e($question->status_id); ?>">
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($st->id); ?>" <?php echo e((int)$st->id === (int)$question->status_id ? 'selected' : ''); ?>>
          <?php echo e($st->status); ?>

        </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>

      <button class="btn btn-sm btn-outline-danger delete-solution"
      data-solution-id="<?php echo e($solution->id); ?>"
      title="Delete solution">
      <i class="fas fa-trash"></i>
    </button>
  </div>
</div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php else: ?>
<p class="text-muted">No solutions available</p>
<?php endif; ?>


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
</div>
</div>
</div>
</div>


<div class="col-lg-4">
  
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">
        <i class="fas fa-chart-bar me-2"></i>Question Statistics
      </h5>
    </div>
    <div class="card-body">
      <div class="row text-center">
        <div class="col-4">
          <h4 class="text-primary"><?php echo e($question->id); ?></h4>
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

  
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0">
        <i class="fas fa-brain me-2"></i>Associated Skill
      </h5>
    </div>
    <div class="card-body">
      <?php if($question->skill): ?>
      <div class="d-flex align-items-start">
        <?php if($question->skill->image): ?>
        <img src="<?php echo e(asset($question->skill->image)); ?>" alt="<?php echo e($question->skill->skill); ?>"
        class="rounded me-3" width="60" height="60" style="object-fit: cover;">
        <?php endif; ?>
        <div class="flex-grow-1">
          <h6 class="mb-1">
            <?php echo e($question->skill->skill); ?>

            <a href="<?php echo e(route('admin.skills.show', $question->skill)); ?>" class="btn btn-outline-primary btn-sm">
              <i class="fas fa-eye me-1"></i>View Skill
            </a>
          </h6>
          <p class="text-muted small mb-2">
            <?php echo e(strlen($question->skill->description) > 100 ? substr($question->skill->description, 0, 100) . '...' : $question->skill->description); ?>

          </p>

          
          <div class="skill-change-box border border-success rounded p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <i class="fas fa-exchange-alt text-success me-2"></i>
                <span class="fw-semibold text-success">Change Skill</span>
              </div>
              <div class="skill-dropdown" style="min-width: 200px;">
                <select class="form-select form-select-sm skill-selector"
                data-field="skill_id"
                data-id="<?php echo e($question->id); ?>"
                data-current="<?php echo e($question->skill_id ?? ''); ?>">
                <option value="">Select a skill...</option>
                <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skillOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($skillOption->id); ?>" <?php echo e($skillOption->id == ($question->skill_id ?? '') ? 'selected' : ''); ?>>
                  <?php echo e($skillOption->skill); ?>

                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </div>
          </div>
          <small class="text-muted mt-2 d-block">Select a new skill to assign to this question</small>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="text-center py-3">
      <i class="fas fa-unlink fa-2x text-muted mb-2"></i>
      <p class="text-muted">No skill associated</p>
    </div>
    <?php endif; ?>
  </div>
</div>


<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-tools me-2"></i>Quick Actions
    </h5>
  </div>
  <div class="card-body">
    <div class="d-grid gap-2">
      <a href="<?php echo e(route('admin.qa.questions.review', $question)); ?>" class="btn btn-warning">
        <i class="fas fa-clipboard-check me-1"></i>QA Review
      </a>
      <button class="btn btn-info" onclick="duplicateQuestion(<?php echo e($question->id); ?>)">
        <i class="fas fa-copy me-1"></i>Duplicate Question
      </button>
      <button class="btn btn-success" onclick="previewQuestion(<?php echo e($question->id); ?>)">
        <i class="fas fa-eye me-1"></i>Preview Question
      </button>
      <button class="btn btn-danger" onclick="deleteQuestion(<?php echo e($question->id); ?>)">
        <i class="fas fa-trash me-1"></i>Delete Question
      </button>
    </div>
    <?php echo $__env->make('admin.components.math-help', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  </div>
</div>


<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-user me-2"></i>Author Information
    </h5>
  </div>
  <div class="card-body">
    <div class="d-flex align-items-center mb-3">
      <?php if($question->author && $question->author->image): ?>
      <img src="<?php echo e($question->author->image); ?>" alt="<?php echo e($question->author->name); ?>"
      class="rounded-circle me-3" width="50" height="50"
      onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
      style="width: 50px; height: 50px; display: none;">
      <?php echo e(substr($question->author->name, 0, 1)); ?>

    </div>
    <?php else: ?>
    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
    style="width: 50px; height: 50px;">
    <?php echo e($question->author ? substr($question->author->name, 0, 1) : 'U'); ?>

  </div>
  <?php endif; ?>
  <div>
    <h6 class="mb-1"><?php echo e($question->author->name ?? 'Unknown Author'); ?></h6>
    <small class="text-muted">Created <?php echo e($question->created_at->diffForHumans()); ?></small>
  </div>
</div>
<div class="row small text-muted">
  <div class="col-12 mb-2">
    <strong>Created:</strong> <?php echo e($question->created_at->format('M j, Y \a\t g:i A')); ?>

  </div>
  <div class="col-12">
    <strong>Updated:</strong> <?php echo e($question->updated_at->format('M j, Y \a\t g:i A')); ?>

  </div>
</div>
</div>
</div>
</div>
</div>
</div>


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


<input type="file" id="answerImageInput" style="display:none" accept="image/*">
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
  /** ====== CONSTANTS / ROUTES ====== **/
  const QUESTION_ID  = <?php echo e($question->id); ?>;
  const ROUTES = {
    hints: {
      store: <?php echo json_encode(route('admin.hints.store'), 15, 512) ?>,
      one: (id) => <?php echo json_encode(url('admin/hints'), 15, 512) ?> + '/' + id
    },
    solutions: {
      store: <?php echo json_encode(route('admin.solutions.store'), 15, 512) ?>,
      one: (id) => <?php echo json_encode(url('admin/solutions'), 15, 512) ?> + '/' + id
    }
  };

  /** ====== ON LOAD ====== **/
  document.addEventListener('DOMContentLoaded', function() {
    window.QUESTION_ID = QUESTION_ID;
    renderKaTeX();
    setupInlineEditing();
    setupDropdownSelectors();
  setupImageInputs(); // NEW: Setup image upload handlers
  
  // Hint toggles
  const toggleAddHintBtn = document.getElementById('toggle-add-hint');
  const addHintBox = document.getElementById('add-hint-box');
  if (toggleAddHintBtn && addHintBox) {
    toggleAddHintBtn.addEventListener('click', () => {
      addHintBox.style.display = addHintBox.style.display === 'none' || !addHintBox.style.display ? 'block' : 'none';
    });
    document.getElementById('cancel-add-hint').addEventListener('click', () => {
      addHintBox.style.display = 'none';
      document.getElementById('new-hint-text').value = '';
      document.getElementById('new-hint-level').value = '1';
    });
    document.getElementById('save-new-hint').addEventListener('click', saveNewHint);
  }
  
  // Solution toggles
  const toggleAddSolBtn = document.getElementById('toggle-add-solution');
  const addSolBox = document.getElementById('add-solution-box');
  if (toggleAddSolBtn && addSolBox) {
    toggleAddSolBtn.addEventListener('click', () => {
      addSolBox.style.display = addSolBox.style.display === 'none' || !addSolBox.style.display ? 'block' : 'none';
    });
    document.getElementById('cancel-add-solution').addEventListener('click', () => {
      addSolBox.style.display = 'none';
      document.getElementById('new-solution-text').value = '';
    });
    document.getElementById('save-new-solution').addEventListener('click', saveNewSolution);
  }
  
  bindHintInlineEditing();
  bindSolutionInlineEditing();
});

  /** ====== IMAGE UPLOAD - NEW IMPLEMENTATION ====== **/
  var currentImageContext = null;

  function setupImageInputs() {
    const questionImageInput = document.createElement('input');
    questionImageInput.type = 'file';
    questionImageInput.id = 'questionImageInput';
    questionImageInput.accept = 'image/*';
    questionImageInput.style.display = 'none';
    document.body.appendChild(questionImageInput);

    questionImageInput.addEventListener('change', async function (e) {
      const file = e.target.files?.[0];
      if (!file) return;

      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const formData = new FormData();
      formData.append('image', file);
      formData.append('type', 'question_image');
      formData.append('question_id', String(QUESTION_ID));

      try {
        const response = await fetch('/admin/upload/image', {
          method: 'POST',
          body: formData,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });
        const data = await response.json();

        if (response.ok && data.success) {
          showToast('Image uploaded!', 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast(data.message || 'Upload failed', 'error');
        }
      } catch (error) {
        showToast('Upload failed', 'error');
      } finally {
        e.target.value = '';
      }
    });

  // ✅ Handler for answer image input
  const answerImageInput = document.getElementById('answerImageInput');
  if (answerImageInput) {
    answerImageInput.addEventListener('change', async function (e) {
      const file = e.target.files?.[0];
      if (!file) return;

      // set by addAnswerImage/changeAnswerImage before opening the picker
      if (!window.currentImageContext) {
        console.warn('[answer upload] missing context');
        e.target.value = '';
        return;
      }

      const { questionId, optionIndex } = window.currentImageContext;
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      const formData = new FormData();
      formData.append('image', file);
      formData.append('type', 'answer_image');      // controller expects this
      formData.append('question_id', String(questionId));
      formData.append('option', String(optionIndex)); // 0..3

      try {
        const response = await fetch('/admin/upload/image', {
          method: 'POST',
          body: formData,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
          credentials: 'same-origin'
        });
        const data = await response.json();

        if (response.ok && data.success) {
          showToast(`Option ${['A','B','C','D'][optionIndex]} image uploaded!`, 'success');
          setTimeout(() => location.reload(), 800);
        } else {
          showToast(data.message || 'Upload failed', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('Upload failed', 'error');
      } finally {
        window.currentImageContext = null;
        e.target.value = ''; // allow re-selecting same file
      }
    });
  } else {
    console.error('#answerImageInput not found in DOM');
  }
}

function addQuestionImage() { document.getElementById('questionImageInput').click(); }

function changeQuestionImage() { document.getElementById('questionImageInput').click(); }

function addAnswerImage(questionId, optionIndex) {
  currentImageContext = { type: 'answer', questionId, optionIndex };
  document.getElementById('answerImageInput').click();
}

function changeAnswerImage(questionId, optionIndex) {
  currentImageContext = { type: 'answer', questionId, optionIndex };
  document.getElementById('answerImageInput').click();
}

function updateImageUploadStatus(message, status) {
  console.log(`[Image Upload] ${message}`);
}


function removeQuestionImage(questionId) {
  if (!confirm('Remove question image?')) return;
  removeImage('question', questionId);
}

function removeAnswerImage(questionId, optionIndex) {
  if (!confirm(`Remove Option ${['A','B','C','D'][optionIndex]} image?`)) return;
  removeImage('answer', questionId, optionIndex);
}

function removeImage(type, questionId, optionIndex = null) {
  const url = type === 'question' 
  ? `/admin/questions/${questionId}/image`
  : `/admin/questions/${questionId}/answers/${optionIndex}/image`;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  fetch(url, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Image removed!', 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message || 'Remove failed', 'error');
    }
  })
  .catch(() => showToast('Remove failed', 'error'));
}

function closeImagePreview() {
  document.getElementById('imagePreviewModal').style.display = 'none';
  delete window.pendingQuestionImageUpload;
  delete window.pendingAnswerImageUpload;
  currentImageContext = null;
  const uploadBtn = document.querySelector('[onclick="confirmImageUpload()"]');
  uploadBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Image';
  uploadBtn.disabled = false;
}

/** ====== HINTS ====== **/
async function saveNewHint() {
  const hintText = document.getElementById('new-hint-text').value.trim();
  const hintLevel = +document.getElementById('new-hint-level').value || 1;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  if (!hintText) { showToast('Hint text required', 'error'); return; }
  
  try {
    const res = await fetch(ROUTES.hints.store, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ question_id: QUESTION_ID, hint_level: hintLevel, hint_text: hintText })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Failed to save hint');
    showToast('Hint added', 'success');
    location.reload();
  } catch (err) {
    showToast(err.message || 'Error adding hint', 'error');
  }
}

document.addEventListener('click', async (e) => {
  if (e.target.closest('.delete-hint')) {
    const btn = e.target.closest('.delete-hint');
    const id = btn.dataset.hintId;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    if (!id || !confirm('Delete this hint?')) return;
    
    try {
      const res = await fetch(ROUTES.hints.one(id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to delete hint');
      showToast('Hint deleted', 'success');
      location.reload();
    } catch (err) {
      showToast(err.message || 'Error deleting hint', 'error');
    }
  }
});

function bindHintInlineEditing() {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  document.querySelectorAll('.editable-hint-level').forEach(el => {
    el.addEventListener('click', () => startInlineEdit(el, 'number', async (newVal) => {
      const id = el.dataset.hintId;
      const res = await fetch(ROUTES.hints.one(id), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ hint_level: +newVal })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Update failed');
    }));
  });
  
  document.querySelectorAll('.editable-hint-text').forEach(el => {
    el.addEventListener('click', () => startInlineEdit(el, 'textarea', async (newVal) => {
      const id = el.dataset.hintId;
      const res = await fetch(ROUTES.hints.one(id), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ hint_text: newVal })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Update failed');
    }, { htmlSafe: false }));
  });
}

/** ====== SOLUTIONS ====== **/
async function saveNewSolution() {
  const text = document.getElementById('new-solution-text').value.trim();
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  if (!text) { showToast('Solution text required', 'error'); return; }
  
  try {
    const res = await fetch(ROUTES.solutions.store, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ question_id: QUESTION_ID, solution: text })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Failed to save solution');
    showToast('Solution saved', 'success');
    location.reload();
  } catch (err) {
    showToast(err.message || 'Error saving solution', 'error');
  }
}

document.addEventListener('click', async (e) => {
  if (e.target.closest('.delete-solution')) {
    const btn = e.target.closest('.delete-solution');
    const id = btn.dataset.solutionId;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    if (!id || !confirm('Delete this solution?')) return;
    
    try {
      const res = await fetch(ROUTES.solutions.one(id), {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Failed to delete solution');
      showToast('Solution deleted', 'success');
      location.reload();
    } catch (err) {
      showToast(err.message || 'Error deleting solution', 'error');
    }
  }
});

function bindSolutionInlineEditing() {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  document.querySelectorAll('.editable-solution').forEach(el => {
    el.addEventListener('click', () => startInlineEdit(el, 'textarea', async (newVal) => {
      const id = el.dataset.solutionId;
      const res = await fetch(ROUTES.solutions.one(id), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ solution: newVal })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'Update failed');
    }, { htmlSafe: false }));
  });
}

/** ====== INLINE EDITOR ====== **/
function startInlineEdit(containerEl, kind = 'text', onSave, opts = {}) {
  const originalHTML = containerEl.innerHTML;
  const originalText = containerEl.innerText.trim();
  const isTextarea = kind === 'textarea';
  const inputHTML = isTextarea
  ? `<textarea class="form-control" rows="4" autofocus>${originalText}</textarea>`
  : `<input class="form-control" type="${kind}" value="${originalText}" autofocus />`;
  
  containerEl.innerHTML = inputHTML;
  const inputEl = containerEl.querySelector(isTextarea ? 'textarea' : 'input');
  inputEl.focus();
  
  const finish = async (save) => {
    if (save) {
      const newVal = inputEl.value;
      try {
        await onSave(newVal);
        containerEl.innerHTML = opts.htmlSafe === false
        ? newVal.replace(/\n/g, '<br>')
        : `${newVal}<i class="fas fa-edit text-muted ms-2 edit-icon"></i>`;
        showToast('Saved', 'success');
      } catch (err) {
        containerEl.innerHTML = originalHTML;
        showToast(err.message || 'Save failed', 'error');
      }
    } else {
      containerEl.innerHTML = originalHTML;
    }
  };
  
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !isTextarea) { e.preventDefault(); finish(true); }
    if (e.key === 'Escape') { e.preventDefault(); finish(false); }
  });
  inputEl.addEventListener('blur', () => finish(true));
}

/** ====== QUESTION FIELD UPDATES ====== **/
function setupInlineEditing() {
  document.querySelectorAll('.editable-field').forEach(field => {
    if (field.classList.contains('editable-hint-level') ||
      field.classList.contains('editable-hint-text') ||
      field.classList.contains('editable-solution')) return;

      field.addEventListener('click', function() {
        const fieldName = this.dataset.field;
        const fieldType = this.dataset.type || 'text';
        const currentVal = fieldType === 'html'
        ? this.querySelector('.fib-content')?.innerHTML?.trim() ?? ''
        : this.textContent.trim();
        showInlineEditor(this, fieldName, fieldType, currentVal);
      });
  });
}

function showInlineEditor(element, fieldName, fieldType, currentValue) {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
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
        await updateQuestionField(fieldName, newValue, csrf);
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
      element.innerHTML = `<div class="fib-content">${currentValue}</div><i class="fas fa-code text-muted ms-2 edit-icon"></i>`;
    } else {
      element.innerHTML = `${currentValue}<i class="fas fa-edit text-muted ms-2 edit-icon"></i>`;
    }
  };
  
  inputEl.addEventListener('blur', saveEdit);
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); saveEdit(); }
    else if (e.key === 'Escape') { e.preventDefault(); cancelEdit(); }
  });
}

function setupDropdownSelectors() {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  document.querySelectorAll('select[data-field]').forEach(select => {
    select.addEventListener('change', async (e) => {
      const fieldName = e.target.dataset.field;
      const newValue = e.target.value;
      if (newValue !== undefined) {
        try {
          await updateQuestionField(fieldName, newValue, csrf);
          e.target.style.borderColor = '#198754';
          setTimeout(() => e.target.style.borderColor = '', 2000);
        } catch (error) {
          // handle error
        }
      }
    });
  });
}

async function updateQuestionField(fieldName, value, csrf) {
  try {
    const response = await fetch(`/admin/questions/${QUESTION_ID}/update-field`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ field: fieldName, value, _method: 'PATCH' })
    });
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Update failed');
    showToast('Field updated', 'success');
    setTimeout(() => renderKaTeX(), 100);
    return data;
  } catch (err) {
    showToast(err.message || 'Update failed', 'error');
    throw err;
  }
}

/** ====== OTHER ACTIONS ====== **/
function deleteQuestion(questionId) {
  if (!confirm('Delete this question? This cannot be undone.')) return;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  
  fetch(`/admin/questions/${questionId}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Question deleted', 'success');
      setTimeout(() => window.location.href = '/admin/questions', 1500);
    } else {
      showToast(data.message || 'Delete failed', 'error');
    }
  })
  .catch(() => showToast('Delete failed', 'error'));
}

function duplicateQuestion(questionId) {
  if (!confirm('Duplicate this question?')) return;
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const btn = document.querySelector('[onclick*="duplicateQuestion"]');
  
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Duplicating...';
  }
  
  fetch(`/admin/questions/${questionId}/duplicate`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('Question duplicated', 'success');
      if (data.redirect_url) window.location.href = data.redirect_url;
      else location.reload();
    } else {
      showToast(data.message || 'Duplication failed', 'error');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-copy me-1"></i>Duplicate Question';
      }
    }
  })
  .catch(() => {
    showToast('Duplication failed', 'error');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-copy me-1"></i>Duplicate Question';
    }
  });
}

function previewQuestion(questionId) {
  window.open(`/admin/questions/${questionId}/preview`, '_blank', 'width=800,height=600');
}

function showToast(message, type = 'info') {
  const toastClass = type === 'success' ? 'alert-success' :
  type === 'error' ? 'alert-danger' : 'alert-info';
  const toast = document.createElement('div');
  toast.className = `alert ${toastClass} alert-dismissible fade show position-fixed`;
  toast.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:300px;';
  toast.innerHTML = `${message}<button type="button" class="btn-close" onclick="this.parentNode.remove()"></button>`;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 5000);
}

</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views/admin/questions/show.blade.php ENDPATH**/ ?>