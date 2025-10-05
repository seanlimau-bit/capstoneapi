
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Track ID</th>
                <th>Track Maxile</th>
                <th>Passed</th>
                <th>Doneness</th>
                <th>Test Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $tracks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $track): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($track->id); ?></td>
                <td><?php echo e($track->pivot->track_maxile ?? 'N/A'); ?></td>
                <td>
                    <span class="badge bg-<?php echo e($track->pivot->track_passed ? 'success' : 'danger'); ?>">
                        <?php echo e($track->pivot->track_passed ? 'Passed' : 'Failed'); ?>

                    </span>
                </td>
                <td><?php echo e($track->pivot->doneNess ?? 'N/A'); ?></td>
                <td><?php echo e(formatDate($track->pivot->track_test_date)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="5" class="text-center text-muted">No tracks found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\tracks-table.blade.php ENDPATH**/ ?>