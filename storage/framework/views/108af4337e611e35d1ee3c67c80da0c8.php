
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <?php if(!empty($icon)): ?>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-<?php echo e($icon); ?> text-primary me-2"></i>
                                <h2 class="mb-0"><?php echo e($title ?? 'Page Title'); ?></h2>
                            </div>
                        <?php else: ?>
                            <h2 class="mb-1"><?php echo e($title ?? 'Page Title'); ?></h2>
                        <?php endif; ?>

                        <?php if(!empty($subtitle)): ?>
                            <p class="text-muted mb-0"><?php echo e($subtitle); ?></p>
                        <?php endif; ?>

                        <?php if(!empty($breadcrumbs) && is_iterable($breadcrumbs)): ?>
                            <nav aria-label="breadcrumb" class="mt-2">
                                <ol class="breadcrumb mb-0">
                                    <?php $__currentLoopData = $breadcrumbs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $crumb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $crumbTitle = $crumb['title'] ?? '';
                                            $crumbUrl   = $crumb['url']   ?? '';
                                        ?>
                                        <?php if($loop->last || empty($crumbUrl)): ?>
                                            <li class="breadcrumb-item active" aria-current="page"><?php echo e($crumbTitle); ?></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item">
                                                <a href="<?php echo e($crumbUrl); ?>"><?php echo e($crumbTitle); ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ol>
                            </nav>
                        <?php endif; ?>
                    </div>

                    <?php if(!empty($actions) && is_iterable($actions)): ?>
                        <div class="btn-toolbar ms-3" role="toolbar">
                            <?php $__currentLoopData = $actions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $atype  = $action['type']  ?? null;
                                    $aclass = $action['class'] ?? 'primary';
                                    $atext  = $action['text']  ?? ($action['title'] ?? 'Action');
                                    $aicon  = $action['icon']  ?? null;
                                ?>

                                <?php if($atype === 'dropdown'): ?>
                                    
                                    <div class="dropdown me-2">
                                        <button class="btn btn-<?php echo e($aclass); ?> btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <?php if($aicon): ?><i class="fas fa-<?php echo e($aicon); ?> me-1"></i><?php endif; ?>
                                            <?php echo e($atext); ?>

                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php $__currentLoopData = ($action['items'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if(is_string($item) && $item === 'divider'): ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                <?php else: ?>
                                                    <?php
                                                        $itemText   = $item['text'] ?? ($item['title'] ?? 'Item');
                                                        $itemUrl    = $item['url']  ?? '#';
                                                        $itemOnclick= $item['onclick'] ?? null;
                                                        $itemIcon   = $item['icon'] ?? null;
                                                    ?>
                                                    <li>
                                                        <a class="dropdown-item"
                                                           href="<?php echo e($itemUrl); ?>"
                                                           <?php if($itemOnclick): ?> onclick="<?php echo e($itemOnclick); ?>" <?php endif; ?>>
                                                            <?php if($itemIcon): ?><i class="fas fa-<?php echo e($itemIcon); ?> me-2"></i><?php endif; ?>
                                                            <?php echo e($itemText); ?>

                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </div>

                                <?php elseif(!empty($action['modal'])): ?>
                                    
                                    <button type="button"
                                            class="btn btn-<?php echo e($aclass); ?> btn-sm me-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?php echo e($action['modal']); ?>">
                                        <?php if($aicon): ?><i class="fas fa-<?php echo e($aicon); ?> me-1"></i><?php endif; ?>
                                        <?php echo e($atext); ?>

                                    </button>

                                <?php elseif(!empty($action['onclick'])): ?>
                                    
                                    <button type="button"
                                            class="btn btn-<?php echo e($aclass); ?> btn-sm me-2"
                                            onclick="<?php echo e($action['onclick']); ?>">
                                        <?php if($aicon): ?><i class="fas fa-<?php echo e($aicon); ?> me-1"></i><?php endif; ?>
                                        <?php echo e($atext); ?>

                                    </button>

                                <?php else: ?>
                                    
                                    <?php $aurl = $action['url'] ?? '#'; ?>
                                    <a href="<?php echo e($aurl); ?>"
                                       class="btn btn-<?php echo e($aclass); ?> btn-sm me-2"
                                       <?php if(!empty($action['target'])): ?> target="<?php echo e($action['target']); ?>" <?php endif; ?>>
                                        <?php if($aicon): ?><i class="fas fa-<?php echo e($aicon); ?> me-1"></i><?php endif; ?>
                                        <?php echo e($atext); ?>

                                    </a>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views/admin/components/page-header.blade.php ENDPATH**/ ?>