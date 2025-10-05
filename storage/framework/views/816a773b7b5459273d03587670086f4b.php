
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Quiz ID</th>
                <th>Completed</th>
                <th>Result</th>
                <th>Attempts</th>
                <th>Completed Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($quiz->id); ?></td>
                <td>
                    <span class="badge bg-<?php echo e($quiz->pivot->quiz_completed ? 'success' : 'warning'); ?>">
                        <?php echo e($quiz->pivot->quiz_completed ? 'Yes' : 'No'); ?>

                    </span>
                </td>
                <td><?php echo e($quiz->pivot->result ?? 'N/A'); ?></td>
                <td><?php echo e($quiz->pivot->attempts ?? 0); ?></td>
                <td><?php echo e(formatDate($quiz->pivot->completed_date, 'M d, Y')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" class="text-center text-muted">No quizzes found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\quizzes-table.blade.php ENDPATH**/ ?>