
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Skill ID</th>
                <th>Skill Maxile</th>
                <th>Passed</th>
                <th>Difficulty Passed</th>
                <th>Tries</th>
                <th>Correct Streak</th>
                <th>Total Correct</th>
                <th>Total Incorrect</th>
                <th>Fail Streak</th>
                <th>Test Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td><?php echo e($skill->id); ?></td>
                <td><?php echo e($skill->pivot->skill_maxile ?? 'N/A'); ?></td>
                <td>
                    <span class="badge bg-<?php echo e($skill->pivot->skill_passed ? 'success' : 'danger'); ?>">
                        <?php echo e($skill->pivot->skill_passed ? 'Passed' : 'Failed'); ?>

                    </span>
                </td>
                <td><?php echo e($skill->pivot->difficulty_passed ?? 0); ?></td>
                <td><?php echo e($skill->pivot->noOfTries ?? 0); ?></td>
                <td><?php echo e($skill->pivot->correct_streak ?? 0); ?></td>
                <td><?php echo e($skill->pivot->total_correct_attempts ?? 0); ?></td>
                <td><?php echo e($skill->pivot->total_incorrect_attempts ?? 0); ?></td>
                <td><?php echo e($skill->pivot->fail_streak ?? 0); ?></td>
                <td><?php echo e(formatDate($skill->pivot->skill_test_date)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="10" class="text-center text-muted">No skills found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\partials\skills-table.blade.php ENDPATH**/ ?>