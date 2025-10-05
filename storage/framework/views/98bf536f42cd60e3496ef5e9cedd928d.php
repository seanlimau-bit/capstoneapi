
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo e($title ?? 'Recent Activity'); ?></h5>
        <?php if($viewAllRoute ?? false): ?>
            <a href="<?php echo e($viewAllRoute); ?>" class="btn btn-sm btn-outline-secondary">View All</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <?php $__currentLoopData = $columns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th><?php echo e($column); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <?php $__currentLoopData = $activity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td>
                                    <?php if($key === 'type'): ?>
                                        <span class="badge bg-<?php echo e($value === 'create' ? 'success' : ($value === 'update' ? 'warning' : 'danger')); ?>">
                                            <?php echo e(ucfirst($value)); ?>

                                        </span>
                                    <?php else: ?>
                                        <?php echo e($value); ?>

                                    <?php endif; ?>
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="<?php echo e(count($columns)); ?>" class="text-center text-muted">
                                <i class="fas fa-clock me-1"></i><?php echo e($emptyMessage ?? 'No recent activity'); ?>

                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\recent-activity.blade.php ENDPATH**/ ?>