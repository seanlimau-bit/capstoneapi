
<div class="row mb-4">
    <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-lg-<?php echo e(12 / count($stats)); ?> col-md-6 mb-3">
            <div class="card bg-<?php echo e($stat['color'] ?? 'primary'); ?> text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h4 class="mb-0" 
                            <?php if(isset($stat['id'])): ?> id="<?php echo e($stat['id']); ?>" <?php endif; ?>
                            <?php if(isset($stat['data-stat'])): ?> data-stat="<?php echo e($stat['data-stat']); ?>" <?php endif; ?>>
                            <?php echo e($stat['value'] ?? $stat['count'] ?? 0); ?>

                        </h4>
                        <small class="opacity-90"><?php echo e($stat['label'] ?? $stat['title'] ?? 'Statistic'); ?></small>
                        <?php if(isset($stat['subtitle'])): ?>
                            <div class="small opacity-75"><?php echo e($stat['subtitle']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if(isset($stat['icon'])): ?>
                        <i class="fas fa-<?php echo e($stat['icon']); ?> fa-2x opacity-75"></i>
                    <?php endif; ?>
                </div>
                <?php if(isset($stat['progress'])): ?>
                    <div class="card-footer bg-transparent border-0 pt-0">
                        <div class="progress" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: <?php echo e($stat['progress']); ?>%"></div>
                        </div>
                        <small class="opacity-75"><?php echo e($stat['progress']); ?>% of target</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php if(empty($stats)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="fas fa-chart-bar fa-2x mb-2"></i>
            <p class="mb-0">No statistics available</p>
        </div>
    </div>
</div>
<?php endif; ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\stats-row.blade.php ENDPATH**/ ?>