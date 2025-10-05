
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><?php echo e($title ?? 'System Status'); ?></h5>
    </div>
    <div class="card-body">
        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small"><?php echo e($status['label']); ?></span>
                <?php if($status['type'] === 'badge'): ?>
                    <span class="badge bg-<?php echo e($status['color']); ?>"><?php echo e($status['value']); ?></span>
                <?php else: ?>
                    <small class="text-muted"><?php echo e($status['value']); ?></small>
                <?php endif; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\system-status.blade.php ENDPATH**/ ?>