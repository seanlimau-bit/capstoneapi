
<?php
    // Top-level props with sane defaults
    $icon      = $icon      ?? 'question-circle';
    $title     = $title     ?? 'Nothing here yet';
    $message   = $message   ?? '';
    $hasAction = isset($action) && is_array($action) && !empty($action);

    // Backward-compatible action mapping
    // Accepts either:
    //   ['text'=>..., 'icon'=>..., 'style'=>..., 'onclick'=>..., 'modal'=>...]
    // or legacy:
    //   ['label'=>..., 'type'=>..., 'action'=>...]  (maps to text/style/onclick)
    $actionText    = $hasAction ? ($action['text']   ?? ($action['label']  ?? 'Action')) : null;
    $actionStyle   = $hasAction ? ($action['style']  ?? ($action['type']   ?? 'primary')) : null;
    $actionIcon    = $hasAction ? ($action['icon']   ?? 'plus') : null;
    $actionOnclick = $hasAction ? ($action['onclick']?? ($action['action'] ?? null)) : null;
    $actionModal   = $hasAction ? ($action['modal']  ?? null) : null;
?>

<div class="text-center py-5">
    <i class="fas fa-<?php echo e(e($icon)); ?> fa-3x text-muted mb-3"></i>
    <h5 class="text-muted"><?php echo e(e($title)); ?></h5>
    <?php if(!empty($message)): ?>
        <p class="text-muted"><?php echo e(e($message)); ?></p>
    <?php endif; ?>

    <?php if($hasAction): ?>
        <button
            type="button"
            class="btn btn-<?php echo e(e($actionStyle)); ?>"
            <?php if(!empty($actionModal)): ?> data-bs-toggle="modal" data-bs-target="#<?php echo e(e($actionModal)); ?>" <?php endif; ?>
            <?php if(!empty($actionOnclick)): ?> onclick="<?php echo e($actionOnclick); ?>" <?php endif; ?>
        >
            <?php if(!empty($actionIcon)): ?>
                <i class="fas fa-<?php echo e(e($actionIcon)); ?> me-2"></i>
            <?php endif; ?>
            <?php echo e(e($actionText)); ?>

        </button>
    <?php endif; ?>
</div>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\empty-state.blade.php ENDPATH**/ ?>