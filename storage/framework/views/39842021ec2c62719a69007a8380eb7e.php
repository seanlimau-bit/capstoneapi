
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'question',
'skillId' => null,
'showCheckbox' => false,
'showSource' => true,
'showAuthor' => true,
'actions' => ['view' => true, 'duplicate' => true, 'delete' => true, 'generate' => false],
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
'question',
'skillId' => null,
'showCheckbox' => false,
'showSource' => true,
'showAuthor' => true,
'actions' => ['view' => true, 'duplicate' => true, 'delete' => true, 'generate' => false],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<?php
use Illuminate\Support\Str;
$qid = $question->id;
?>

<tr data-id="<?php echo e($qid); ?>">
  <?php if($showCheckbox): ?>
  <td>
    <div class="form-check">
      <input type="checkbox" value="<?php echo e($qid); ?>" class="form-check-input question-checkbox">
    </div>
  </td>
  <?php endif; ?>

  
  <td>
    <div class="fw-semibold mb-1"><?php echo e(Str::limit(strip_tags($question->question ?? ''), 60)); ?></div>

    <?php if(!empty($question->correct_answer)): ?>
    <small class="text-success">Answer: <?php echo e(Str::limit($question->correct_answer, 40)); ?></small>
    <?php endif; ?>

    <div class="mt-1">
      <small class="text-muted">ID: <?php echo e($qid); ?></small>
      <?php if($question->type): ?>
      <span class="badge bg-info ms-2"><?php echo e($question->type->description ?? $question->type->type); ?></span>
      <?php endif; ?>
    </div>
  </td>

  
  <td>
    <div class="d-flex flex-column gap-1">
      <?php if($question->skill): ?>
      <div><strong>Skill:</strong> <?php echo e(Str::limit($question->skill->skill ?? 'Unknown', 25)); ?></div>

      <?php if($question->skill->tracks && $question->skill->tracks->count()): ?>
      <div class="d-flex flex-wrap gap-1">
        <?php $__currentLoopData = $question->skill->tracks->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $track): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span class="badge bg-secondary">
          <?php echo e(Str::limit($track->track, 15)); ?><?php if($track->level): ?> (L<?php echo e($track->level->level); ?>) <?php endif; ?>
        </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php if($question->skill->tracks->count() > 2): ?>
        <span class="badge bg-light text-dark">+<?php echo e($question->skill->tracks->count() - 2); ?></span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <span class="text-muted">No skill assigned</span>
      <?php endif; ?>

      <?php
      $diffLabel = optional($question->difficulty)->short_description
      ?? optional($question->difficulty)->description
      ?? null;
      $diffText = $diffLabel ?: 'No difficulty set';
      $diffClass = 'bg-secondary';
      if ($diffLabel) {
      $l = strtolower($diffLabel);
      $diffClass = str_contains($l, 'easy') ? 'bg-success'
      : (str_contains($l, 'medium') ? 'bg-warning' : 'bg-danger');
    }
    ?>
    <div><span class="badge <?php echo e($diffClass); ?>"><?php echo e($diffText); ?></span></div>
  </div>
</td>

<td>
  <?php
  $statusName = $question->status->status ?? 'Unknown';
  // simple mapping (tweak as you like)
  $map = [
  'Public'   => 'success',
  'Draft'    => 'secondary',
  'Only Me'  => 'dark',
  'Restricted' => 'warning',
  'Draft'  => 'danger',
  ];
  $cls = $map[$statusName] ?? 'info';
  ?>
  <span class="badge bg-<?php echo e($cls); ?>"><?php echo e($statusName); ?></span>
</td>


<td>
  <?php
  $qaMap = [
  'approved'       => ['success','check-circle'],
  'flagged'        => ['danger','flag'],
  'needs_revision' => ['warning','edit'],
  'unreviewed'     => ['info','clock'],
  'ai_generated'   => ['primary','robot'],
  ];
  [$c,$i] = $qaMap[$question->qa_status ?? ''] ?? ['secondary','question'];
  $qaLabel = $question->qa_status ? ucfirst(str_replace('_',' ',$question->qa_status)) : 'Unknown';
  ?>
  <span class="badge bg-<?php echo e($c); ?>"><i class="fas fa-<?php echo e($i); ?> me-1"></i><?php echo e($qaLabel); ?></span>
</td>


<?php if($showSource): ?>
<td>
  <?php
  $author = $question->author;
  $name = $author->name ?? 'Unknown';
  ?>
  <div class="d-flex flex-column">
    <div class="fw-bold small"><?php echo e($name); ?></div>
    <div class="text-muted small"><?php echo e(optional($question->created_at)->format('M j, Y')); ?></div>
  </div>

  <small class="text-muted"><?php echo e($question->source ?? 'Unknown'); ?></small></td>
  <?php endif; ?>

  
  <td class="text-center" actions-col>
    <div class="btn-group btn-group-sm" role="group">
      <?php if(!empty($actions['view'])): ?>
      <button type="button" class="btn btn-outline-info" onclick="viewQuestion(<?php echo e($qid); ?>)" title="View">
        <i class="fas fa-eye"></i>
      </button>
      <?php endif; ?>

      <?php if(!empty($actions['duplicate'])): ?>
      
      <button type="button" class="btn btn-outline-secondary btn-duplicate" title="Duplicate">
        <i class="fas fa-copy"></i>
      </button>
      <?php endif; ?>

      <?php if(!empty($actions['delete'])): ?>
      <button type="button"
      class="btn btn-outline-danger"
      data-action="delete"
      data-id="<?php echo e($qid); ?>"
      title="Delete">
      <i class="fas fa-trash"></i>
    </button>
    <?php endif; ?>

    <?php if(!empty($actions['generate']) && $skillId): ?>
    <button type="button"
    class="btn btn-outline-success"
    onclick="openGenerateModal(<?php echo e($qid); ?>)"
    title="Generate Similar">
    <i class="fas fa-wand-magic-sparkles"></i>
  </button>
  <?php endif; ?>
</div>
</td>
</tr>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views/admin/questions/row.blade.php ENDPATH**/ ?>