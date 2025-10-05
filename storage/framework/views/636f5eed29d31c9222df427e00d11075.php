
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Question ID</th>
                <th>Answered</th>
                <th>Correct</th>
                <th>Attempts</th>
                <th>Kudos</th>
                <th>Test ID</th>
                <th>Quiz ID</th>
                <th>Assessment Type</th>
                <th>Answered Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($question->id); ?></td>
                <td>
                    <span class="badge bg-<?php echo e($question->pivot->question_answered ? 'success' : 'warning'); ?>">
                        <?php echo e($question->pivot->question_answered ? 'Yes' : 'No'); ?>

                    </span>
                </td>
                <td>
                    <span class="badge bg-<?php echo e($question->pivot->correct ? 'success' : 'danger'); ?>">
                        <?php echo e($question->pivot->correct ? '✓' : '✗'); ?>

                    </span>
                </td>
                <td><?php echo e($question->pivot->attempts ?? 0); ?></td>
                <td><?php echo e($question->pivot->kudos ?? 0); ?></td>
                <td><?php echo e($question->pivot->test_id ?? 'N/A'); ?></td>
                <td><?php echo e($question->pivot->quiz_id ?? 'N/A'); ?></td>
                <td><?php echo e($question->pivot->assessment_type ?? 'N/A'); ?></td>
                <td><?php echo e(formatDate($question->pivot->answered_date)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="9" class="text-center text-muted">No questions found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if($questions->count() >= 100): ?>
        <p class="text-muted text-center">Showing first 100 questions</p>
    <?php endif; ?>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\questions-table.blade.php ENDPATH**/ ?>