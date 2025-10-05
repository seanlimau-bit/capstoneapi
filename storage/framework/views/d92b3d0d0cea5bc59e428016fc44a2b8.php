
<div class="row g-3">
    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-xl-<?php echo e($columns ?? 4); ?> col-lg-6 col-md-6 col-sm-12 mb-3">
        <div class="card h-100 hover-lift <?php echo e($item['disabled'] ?? false ? 'opacity-75' : ''); ?>">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="fas fa-<?php echo e($item['icon']); ?> fa-3x text-<?php echo e($item['color']); ?>"></i>
                </div>
                <h5 class="card-title"><?php echo e($item['title']); ?></h5>
                <p class="card-text text-muted"><?php echo e($item['description']); ?></p>
                
                <?php if(isset($item['stats'])): ?>
                <div class="d-flex justify-content-between mb-3">
                    <small class="text-muted"><?php echo e($item['stats']['label']); ?>: <?php echo e($item['stats']['value']); ?></small>
                    <small class="text-<?php echo e($item['status_color'] ?? 'success'); ?>"><?php echo e($item['status'] ?? 'Active'); ?></small>
                </div>
                <?php endif; ?>
                
                <?php if(isset($item['url']) && $item['url']): ?>
                    <a href="<?php echo e($item['url']); ?>" 
                       class="btn btn-outline-<?php echo e($item['disabled'] ?? false ? 'secondary' : 'primary'); ?> btn-sm <?php echo e($item['disabled'] ?? false ? 'disabled' : ''); ?>"
                       <?php if($item['disabled'] ?? false): ?> tabindex="-1" aria-disabled="true" <?php endif; ?>>
                        <i class="fas fa-<?php echo e($item['action_icon'] ?? 'arrow-right'); ?> me-1"></i> 
                        <?php echo e($item['action_text'] ?? 'Manage'); ?>

                    </a>
                <?php elseif(isset($item['onclick'])): ?>
                    <button class="btn btn-outline-primary btn-sm" onclick="<?php echo e($item['onclick']); ?>">
                        <i class="fas fa-<?php echo e($item['action_icon'] ?? 'arrow-right'); ?> me-1"></i> 
                        <?php echo e($item['action_text'] ?? 'Manage'); ?>

                    </button>
                <?php else: ?>
                    <button class="btn btn-outline-secondary btn-sm disabled" tabindex="-1" aria-disabled="true">
                        <i class="fas fa-<?php echo e($item['action_icon'] ?? 'arrow-right'); ?> me-1"></i> 
                        <?php echo e($item['action_text'] ?? 'Coming Soon'); ?>

                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php if(empty($items)): ?>
<div class="text-center py-5">
    <i class="fas fa-grid fa-3x text-muted mb-3"></i>
    <h5 class="text-muted">No management sections configured</h5>
    <p class="text-muted">Configure management sections to display here</p>
</div>
<?php endif; ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\management-grid.blade.php ENDPATH**/ ?>