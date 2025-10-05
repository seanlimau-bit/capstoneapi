
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
        <div>
            <span id="resultsCount" class="badge bg-primary me-2"><?php echo e(count($items ?? [])); ?> results</span>
            <?php if($showClearButton ?? true): ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">Clear All</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php echo e($slot); ?>

        </div>
    </div>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\components\filters-card.blade.php ENDPATH**/ ?>