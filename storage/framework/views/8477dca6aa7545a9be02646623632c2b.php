<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Test ID</th>
                <th>Completed</th>
                <th>Result</th>
                <th>Attempts</th>
                <th>Kudos</th>
                <th>Completed Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $tests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $test): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($test->id); ?></td>
                <td><span class="badge bg-<?php echo e($test->pivot->test_completed ? 'success' : 'warning'); ?>"><?php echo e($test->pivot->test_completed ? 'Yes' : 'No'); ?></span></td>
                <td><?php echo e($test->pivot->result ?? 'N/A'); ?></td>
                <td><?php echo e($test->pivot->attempts ?? 0); ?></td>
                <td><?php echo e($test->pivot->kudos ?? 0); ?></td>
                <td><?php echo e(formatDate($test->pivot->completed_date, 'M d, Y')); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="6" class="text-center text-muted">No tests found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\tests-table.blade.php ENDPATH**/ ?>