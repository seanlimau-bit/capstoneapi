

<?php $__env->startSection('title', 'Test • '.$test->id.' • '.($user->name ?? $user->email)); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid" id="user-test-questions">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Test #<?php echo e($test->id); ?> — Questions</h1>
            <div class="text-muted">User: <?php echo e($user->name ?? $user->email); ?></div>
        </div>
        <a href="<?php echo e(route('admin.users.show', $user->id)); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to User
        </a>
    </div>

    
    <?php if(!empty($testPivot)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-sm-3">
                <div class="card"><div class="card-body text-center">
                    <div class="fw-bold fs-4"><?php echo e($testPivot->test_completed ? 'Yes' : 'No'); ?></div>
                    <div class="text-muted small">Completed</div>
                </div></div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card"><div class="card-body text-center">
                    <div class="fw-bold fs-4"><?php echo e(is_null($testPivot->result) ? '—' : number_format($testPivot->result, 2)); ?></div>
                    <div class="text-muted small">Result</div>
                </div></div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card"><div class="card-body text-center">
                    <div class="fw-bold fs-4"><?php echo e((int) $testPivot->attempts); ?></div>
                    <div class="text-muted small">Attempts</div>
                </div></div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card"><div class="card-body text-center">
                    <div class="fw-bold fs-4"><?php echo e((int) $testPivot->kudos); ?></div>
                    <div class="text-muted small">Kudos</div>
                </div></div>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Question Attempts</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px;">QID</th>
                            <th>Question</th>
                            <th style="width:200px;">Skill</th>
                            <th style="width:320px;">Tracks (Field)</th>
                            <th style="width:110px;">Answered</th>
                            <th style="width:100px;">Correct</th>
                            <th style="width:100px;">Attempts</th>
                            <th style="width:100px;">Kudos</th>
                            <th style="width:170px;">Answered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            use Illuminate\Support\Facades\Route as RouteFacade;
                            $dateFmt = function($d){ if(!$d) return '—'; try { return \Carbon\Carbon::parse($d)->format('M d, Y H:i'); } catch (\Throwable $e) { return '—'; } };
                            $routeOr = function(string $name, $paramId, string $fallbackBase) {
                                return RouteFacade::has($name) ? route($name, $paramId) : url($fallbackBase.'/'.$paramId);
                            };
                        ?>

                        <?php $__empty_1 = true; $__currentLoopData = $attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                // Linked skill (if present)
                                $skillCell = '—';
                                if ($q->skill?->id) {
                                    $skillUrl  = $routeOr('admin.skills.show', $q->skill->id, '/admin/skills');
                                    $skillCell = '<a class="skill-link" href="'.$skillUrl.'" target="_blank">'.e($q->skill->skill).'</a>';
                                }

                                // Build per-line linked tracks with their field links
                                $trackLines = [];
                                if ($q->relationLoaded('skill') && $q->skill && $q->skill->relationLoaded('tracks')) {
                                    foreach ($q->skill->tracks as $t) {
                                        if (!$t) continue;
                                        $trackName = $t->track ?? null;
                                        if (!$trackName) continue;

                                        $trackUrl  = $routeOr('admin.tracks.show', $t->id, '/admin/tracks');
                                        $trackLink = '<a class="track-link" href="'.$trackUrl.'" target="_blank">'.e($trackName).'</a>';

                                        $fieldChunk = '';
                                        if ($t->relationLoaded('field') && $t->field) {
                                            $fieldName = $t->field->field ?? null;
                                            if ($fieldName) {
                                                $fieldUrl = $routeOr('admin.fields.show', $t->field->id, '/admin/fields');
                                                $fieldChunk = ' <span class="text-muted">(&nbsp;</span><a class="field-link" href="'.$fieldUrl.'" target="_blank">'.e($fieldName).'</a><span class="text-muted">&nbsp;)</span>';
                                            }
                                        }

                                        $trackLines[] = '<div class="track-line">'.$trackLink.$fieldChunk.'</div>';
                                    }
                                }
                                $tracksCell = $trackLines ? implode('', $trackLines) : '—';
                            ?>

                            <tr>
                                <td>
                                    <a href="<?php echo e(route('admin.questions.show', $q->id)); ?>" target="_blank"><?php echo e($q->id); ?></a>
                                </td>
                                <td class="qa-question-text">
                                    <a href="<?php echo e(route('admin.questions.show', $q->id)); ?>" target="_blank">
                                        <?php echo e(\Illuminate\Support\Str::limit(strip_tags($q->question ?? ''), 140)); ?>

                                    </a>
                                </td>
                                <td><?php echo $skillCell; ?></td>
                                <td><?php echo $tracksCell; ?></td>
                                <td>
                                    <?php if($q->pivot->question_answered): ?>
                                        <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($q->pivot->correct): ?>
                                        <span class="badge bg-success">✓</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✗</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e((int) $q->pivot->attempts); ?></td>
                                <td><?php echo e((int) $q->pivot->kudos); ?></td>
                                <td class="text-muted"><?php echo e($dateFmt($q->pivot->answered_date ?? null)); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">No question attempts found for this test.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="small text-muted mt-2">
                Source: <code>question_user</code> where <code>user_id=<?php echo e($user->id); ?></code> and <code>test_id=<?php echo e($test->id); ?></code>.
            </div>
        </div>
    </div>

</div>


<style>
#user-test-questions .track-line { line-height: 1.4; margin-bottom: 4px; }
#user-test-questions .track-link {
    color: var(--info-color, #0d6efd);
    font-weight: 600;
    text-decoration: none;
}
#user-test-questions .track-link:hover { text-decoration: underline; }

#user-test-questions .field-link {
    color: var(--secondary-color, #ffbf66);
    font-weight: 600;
    text-decoration: none;
}
#user-test-questions .field-link:hover { text-decoration: underline; }

#user-test-questions .skill-link {
    color: var(--primary-color, #960000);
    font-weight: 600;
    text-decoration: none;
}
#user-test-questions .skill-link:hover { text-decoration: underline; }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\test_questions.blade.php ENDPATH**/ ?>