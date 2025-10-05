<?php
  // defaults for includes
  $skillId = $skillId ?? null;
  $withCheckbox = $withCheckbox ?? true;
?>

<?php $__empty_1 = true; $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
  <?php echo $__env->make('admin.questions.row', [
    'question' => $question,
    'skillId' => $skillId,
    'showCheckbox' => $withCheckbox,
    'actions' => [
      'view' => true,
      'duplicate' => true,
      'delete' => true,
      'generate' => !is_null($skillId),
    ],
  ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
  <tr>
    <td colspan="7" class="text-center py-4">
      <i class="fas fa-search me-2"></i>No questions found
    </td>
  </tr>
<?php endif; ?>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views/admin/questions/table-body.blade.php ENDPATH**/ ?>