
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e(formatDate($log->created_at)); ?></td>
                <td><?php echo e($log->action ?? 'Unknown'); ?></td>
                <td><?php echo e($log->details ?? 'No details'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="3" class="text-center text-muted">No logs found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\logs-table.blade.php ENDPATH**/ ?>