

<?php $__env->startSection('title', 'User • ' . ($user->name ?? $user->email)); ?>

<?php
    use Illuminate\Support\Facades\Route as RouteFacade;
    use Carbon\Carbon;

    function formatDate($date, $format = 'M d, Y H:i') {
        if (!$date) return 'N/A';
        try { return is_string($date) ? Carbon::parse($date)->format($format) : $date->format($format); }
        catch (\Throwable $e) { return 'Invalid Date'; }
    }

    // Where inline edits post
    $inlineUrl = RouteFacade::has('admin.users.inline')
        ? route('admin.users.inline', $user)
        : url("/admin/users/{$user->id}/inline");

    // Enums from schema
    $statusEnum        = ['potential','active','suspended','inactive','pending'];
    $accessTypeEnum    = ['premium','freemium','trial','basic','free'];
    $paymentMethodEnum = ['free','credit_card','telco_billing','school_billing','tuition_billing'];

    // Status badge
    $statusLower = strtolower($user->status ?? 'unknown');
    $statusBadgeClass = match($statusLower) {
        'active'     => 'bg-success',
        'suspended'  => 'bg-danger',
        'inactive'   => 'bg-secondary',
        'pending'    => 'bg-warning',
        'potential'  => 'bg-info',
        default      => 'bg-secondary',
    };

    // helper: route if exists else fallback URL
    $routeOr = function(string $name, $param, string $fallback) {
        return RouteFacade::has($name) ? route($name, $param) : url($fallback);
    };
?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid" id="user-dashboard"
     data-user-id="<?php echo e($user->id); ?>"
     data-csrf="<?php echo e(csrf_token()); ?>"
     data-inline-url="<?php echo e($inlineUrl); ?>">

    
    <div class="d-flex justify-content-between align-items-center mb-4 qa-header rounded-xl">
        <div>
            <h1 class="mb-1"><?php echo e($user->name ?? 'User Profile'); ?></h1>
            <p class="mb-0">User Profile & Management</p>
        </div>
        <div class="qa-header-actions">
            <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    
    <div class="row qa-stats-row">
        <div class="col-md-3"><div class="card qa-stats-approved"><div class="card-body">
            <div class="qa-stat-number"><?php echo e(number_format($user->maxile_level ?? 0, 2)); ?></div><p class="qa-stat-label">Maxile Level</p>
        </div></div></div>
        <div class="col-md-3"><div class="card qa-stats-approved"><div class="card-body">
            <div class="qa-stat-number"><?php echo e($user->game_level ?? 0); ?></div><p class="qa-stat-label">Game Level</p>
        </div></div></div>
        <div class="col-md-3"><div class="card qa-stats-info"><div class="card-body">
            <div class="qa-stat-number"><?php echo e($stats['tests_completed'] ?? 0); ?></div><p class="qa-stat-label">Tests Completed</p>
        </div></div></div>
        <div class="col-md-3"><div class="card qa-stats-info"><div class="card-body">
            <div class="qa-stat-number"><?php echo e($stats['quizzes_completed'] ?? 0); ?></div><p class="qa-stat-label">Quizzes Completed</p>
        </div></div></div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Profile</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleInline" checked>
                        <label class="form-check-label" for="toggleInline">Inline edit</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control editable-input" data-field="name" value="<?php echo e($user->name); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo e($user->email); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control editable-input" data-field="firstname" value="<?php echo e($user->firstname); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control editable-input" data-field="lastname" value="<?php echo e($user->lastname); ?>">
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control editable-input" data-field="phone_number" placeholder="+65 8123 4567" value="<?php echo e($user->phone_number); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" class="form-control editable-input" data-field="contact" value="<?php echo e($user->contact); ?>">
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control editable-input" data-field="date_of_birth"
                                   value="<?php echo e($user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Activated At</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="activated_at"
                                   value="<?php echo e($user->activated_at ? \Carbon\Carbon::parse($user->activated_at)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <select class="form-select editable-input" data-field="status">
                                <?php $__currentLoopData = $statusEnum; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                  <option value="<?php echo e($st); ?>" <?php echo e(($user->status === $st) ? 'selected' : ''); ?>><?php echo e(ucfirst($st)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="mt-2">
                                <span class="badge <?php echo e($statusBadgeClass); ?>"><?php echo e(ucfirst($user->status)); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select editable-input" data-field="role_id">
                                <option value="">No Role</option>
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($role->id); ?>" <?php echo e((int)$user->role_id === (int)$role->id ? 'selected' : ''); ?>>
                                        <?php echo e($role->role); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <div class="mt-2">
                                <?php if($user->role): ?>
                                    <span class="badge bg-warning text-dark"><?php echo e($user->role->role); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No Role</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Access Type</label>
                            <select class="form-select editable-input" data-field="access_type">
                                <?php $__currentLoopData = $accessTypeEnum; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($opt); ?>" <?php echo e($user->access_type === $opt ? 'selected' : ''); ?>><?php echo e(ucfirst($opt)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select editable-input" data-field="payment_method">
                                <?php $__currentLoopData = $paymentMethodEnum; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($opt); ?>" <?php echo e($user->payment_method === $opt ? 'selected' : ''); ?>><?php echo e(ucwords(str_replace('_',' ', $opt))); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Telco Provider</label>
                            <input type="text" class="form-control editable-input" data-field="telco_provider" value="<?php echo e($user->telco_provider); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telco Subscriber ID</label>
                            <input type="text" class="form-control editable-input" data-field="telco_subscriber_id" value="<?php echo e($user->telco_subscriber_id); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Partner ID</label>
                            <input type="number" class="form-control editable-input" data-field="partner_id" value="<?php echo e($user->partner_id); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Partner Subscriber ID</label>
                            <input type="text" class="form-control editable-input" data-field="partner_subscriber_id" value="<?php echo e($user->partner_subscriber_id); ?>">
                        </div>

                        
                        <div class="col-md-4">
                            <label class="form-label">Maxile Level</label>
                            <input type="number" step="0.01" class="form-control editable-input" data-field="maxile_level" value="<?php echo e($user->maxile_level); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Game Level</label>
                            <input type="number" class="form-control editable-input" data-field="game_level" value="<?php echo e($user->game_level); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lives</label>
                            <input type="number" class="form-control editable-input" data-field="lives" value="<?php echo e($user->lives); ?>">
                        </div>

                        
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input editable-input" type="checkbox" data-field="partner_verified" id="pv" <?php echo e($user->partner_verified ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="pv">Partner Verified</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input editable-input" type="checkbox" data-field="diagnostic" id="diag" <?php echo e($user->diagnostic ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="diag">Diagnostic</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input editable-input" type="checkbox" data-field="email_verified" id="ev" <?php echo e($user->email_verified ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="ev">Email Verified</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input editable-input" type="checkbox" data-field="is_admin" id="adm" <?php echo e($user->is_admin ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="adm">Admin</label>
                            </div>
                        </div>

                        
                        <div class="col-md-6">
                            <label class="form-label">Trial Expires At</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="trial_expires_at"
                                   value="<?php echo e($user->trial_expires_at ? \Carbon\Carbon::parse($user->trial_expires_at)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Suspended At</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="suspended_at"
                                   value="<?php echo e($user->suspended_at ? \Carbon\Carbon::parse($user->suspended_at)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cancelled At</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="cancelled_at"
                                   value="<?php echo e($user->cancelled_at ? \Carbon\Carbon::parse($user->cancelled_at)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Verified At</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="email_verified_at"
                                   value="<?php echo e($user->email_verified_at ? \Carbon\Carbon::parse($user->email_verified_at)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Test Date</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="last_test_date"
                                   value="<?php echo e($user->last_test_date ? \Carbon\Carbon::parse($user->last_test_date)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Next Test Date</label>
                            <input type="datetime-local" class="form-control editable-input" data-field="next_test_date"
                                   value="<?php echo e($user->next_test_date ? \Carbon\Carbon::parse($user->next_test_date)->format('Y-m-d\TH:i') : ''); ?>">
                        </div>
                    </div>

                    <hr>
                    <div class="small text-muted d-flex flex-wrap gap-3">
                        <span>Joined: <?php echo e(formatDate($user->created_at, 'M d, Y')); ?></span>
                        <span>Updated: <span id="last-updated"><?php echo e(formatDate($user->updated_at, 'M d, Y')); ?></span></span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Snapshot</h5></div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6"><div class="stat-item"><div class="qa-stat-number"><?php echo e($stats['total_questions_answered'] ?? 0); ?></div><div class="qa-stat-label">Questions</div></div></div>
                        <div class="col-6"><div class="stat-item"><div class="qa-stat-number"><?php echo e($stats['correct_answers'] ?? 0); ?></div><div class="qa-stat-label">Correct</div></div></div>
                        <div class="col-6"><div class="stat-item"><div class="qa-stat-number"><?php echo e($stats['tracks_passed'] ?? 0); ?></div><div class="qa-stat-label">Tracks Passed</div></div></div>
                        <div class="col-6"><div class="stat-item"><div class="qa-stat-number"><?php echo e(number_format($stats['accuracy_percentage'] ?? 0, 1)); ?>%</div><div class="qa-stat-label">Accuracy</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-pills content-pills" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tests" type="button" role="tab">Tests (<?php echo e($user->tests->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#quizzes" type="button" role="tab">Quizzes (<?php echo e($user->quizzes->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#questions" type="button" role="tab">Questions (<?php echo e($user->myQuestions->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tracks" type="button" role="tab">Tracks (<?php echo e($user->testedTracks->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#skills" type="button" role="tab">Skills (<?php echo e($user->skill_user->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#fields" type="button" role="tab">Fields (<?php echo e($user->fields->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#enrolments" type="button" role="tab">Enrolments (<?php echo e($user->enrolledClasses->count()); ?>)</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#logs" type="button" role="tab">Logs (<?php echo e($user->logs->count()); ?>)</button></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                
                <div class="tab-pane fade show active" id="tests" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead><tr><th>ID</th><th>Completed</th><th>Result</th><th>Attempts</th><th>Kudos</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->tests->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $test): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                          <a href="<?php echo e(route('admin.users.tests.questions', [$user->id, $test->id])); ?>">
                                            <?php echo e($test->id); ?>

                                          </a>
                                        </td>
                                        <td><span class="badge <?php echo e($test->pivot->test_completed ? 'bg-success' : 'bg-warning'); ?>"><?php echo e($test->pivot->test_completed ? 'Yes' : 'No'); ?></span></td>
                                        <td><?php echo e($test->pivot->result ?? 'N/A'); ?></td>
                                        <td><?php echo e($test->pivot->attempts ?? 0); ?></td>
                                        <td><?php echo e($test->pivot->kudos ?? 0); ?></td>
                                        <td class="qa-timestamp"><?php echo e(formatDate($test->pivot->completed_date, 'M d, Y')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="6" class="text-center text-muted">No tests found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="quizzes" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead><tr><th>ID</th><th>Completed</th><th>Result</th><th>Attempts</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->quizzes->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e($routeOr('admin.quizzes.show', $quiz->id, "/admin/quizzes/{$quiz->id}")); ?>"><?php echo e($quiz->id); ?></a>
                                        </td>
                                        <td><span class="badge <?php echo e($quiz->pivot->quiz_completed ? 'bg-success' : 'bg-warning'); ?>"><?php echo e($quiz->pivot->quiz_completed ? 'Yes' : 'No'); ?></span></td>
                                        <td><?php echo e($quiz->pivot->result ?? 'N/A'); ?></td>
                                        <td><?php echo e($quiz->pivot->attempts ?? 0); ?></td>
                                        <td class="qa-timestamp"><?php echo e(formatDate($quiz->pivot->completed_date, 'M d, Y')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No quizzes found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="questions" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>ID</th><th>Answered</th><th>Correct</th><th>Attempts</th><th>Difficulty</th><th>Kudos</th><th>Type</th><th>Test</th><th>Quiz</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->myQuestions->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><a href="<?php echo e($routeOr('admin.questions.show', $q->id, "/admin/questions/{$q->id}")); ?>"><?php echo e($q->id); ?></a></td>
                                        <td><span class="badge <?php echo e($q->pivot->question_answered ? 'bg-success' : 'bg-warning'); ?>"><?php echo e($q->pivot->question_answered ? 'Yes' : 'No'); ?></span></td>
                                        <td><span class="badge <?php echo e($q->pivot->correct ? 'bg-success' : 'bg-danger'); ?>"><?php echo e($q->pivot->correct ? '✓' : '✗'); ?></span></td>
                                        <td><?php echo e($q->pivot->attempts); ?></td>
                                        <td><?php echo e($q->difficulty->difficulty); ?></td>
                                        <td><?php echo e($q->pivot->kudos); ?></td>
                                        <td><?php echo e($q->pivot->assessment_type ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if($q->pivot->test_id): ?>
                                                <a href="<?php echo e(route('admin.users.tests.questions', [$user->id, $q->pivot->test_id])); ?>"><?php echo e($q->pivot->test_id); ?></a>
                                            <?php else: ?> N/A <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($q->pivot->quiz_id): ?>
                                                <a href="<?php echo e($routeOr('admin.quizzes.show', $q->pivot->quiz_id, "/admin/quizzes/{$q->pivot->quiz_id}")); ?>"><?php echo e($q->pivot->quiz_id); ?></a>
                                            <?php else: ?> N/A <?php endif; ?>
                                        </td>
                                        <td class="qa-timestamp"><?php echo e(formatDate($q->pivot->answered_date, 'M d, Y')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="9" class="text-center text-muted">No questions found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="tracks" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead><tr><th>Track</th><th>Maxile</th><th>Passed</th><th>Doneness</th><th>Test Date</th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->testedTracks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $track): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e($routeOr('admin.tracks.show', $track->id, "/admin/tracks/{$track->id}")); ?>">
                                                <?php echo e($track->track); ?>

                                            </a>
                                        </td>
                                        <td><?php echo e(number_format($track->pivot->track_maxile, 2)); ?></td>
                                        <td><?php echo $track->pivot->track_passed ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                        <td><?php echo e(number_format($track->pivot->doneNess, 2)); ?>%</td>
                                        <td class="qa-timestamp"><?php echo e(formatDate($track->pivot->track_test_date)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="5" class="text-center text-muted">No track attempts</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="skills" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>Skill</th><th>Maxile</th><th>Passed</th><th>Difficulty</th><th>Tries</th><th>Correct Streak</th><th>Total Correct</th><th>Total Incorrect</th><th>Fail Streak</th><th>Test Date</th></tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->skill_user->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e($routeOr('admin.skills.show', $skill->id, "/admin/skills/{$skill->id}")); ?>"><?php echo e($skill->skill); ?></a>
                                        </td>
                                        <td><?php echo e(number_format($skill->pivot->skill_maxile, 2)); ?></td>
                                        <td><?php echo $skill->pivot->skill_passed ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                        <td><?php echo e($skill->pivot->difficulty_passed); ?></td>
                                        <td><?php echo e($skill->pivot->noOfTries); ?></td>
                                        <td><?php echo e($skill->pivot->correct_streak); ?></td>
                                        <td><?php echo e($skill->pivot->total_correct_attempts); ?></td>
                                        <td><?php echo e($skill->pivot->total_incorrect_attempts); ?></td>
                                        <td><?php echo e($skill->pivot->fail_streak); ?></td>
                                        <td class="qa-timestamp"><?php echo e(formatDate($skill->pivot->skill_test_date)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="10" class="text-center text-muted">No skill attempts</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if($user->skill_user->count() > 50): ?>
                            <p class="text-muted text-center small mt-2 mb-0">Showing first 50 of <?php echo e($user->skill_user->count()); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="fields" role="tabpanel">
                    <?php if($user->fields->count() > 0): ?>
                        <div class="table-responsive qa-table">
                            <table class="table table-hover">
                                <thead><tr><th>Field</th><th>Field Maxile</th><th>Month Achieved</th><th>Test Date</th></tr></thead>
                                <tbody>
                                    <?php $__currentLoopData = $user->fields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><a href="<?php echo e($routeOr('admin.fields.show', $field->id, "/admin/fields/{$field->id}")); ?>"><?php echo e($field->field); ?></a></td>
                                            <td><?php echo e(number_format($field->pivot->field_maxile, 2)); ?></td>
                                            <td><?php echo e($field->pivot->month_achieved); ?></td>
                                            <td class="qa-timestamp"><?php echo e(formatDate($field->pivot->field_test_date)); ?></td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No field attempts found</div>
                    <?php endif; ?>
                </div>

                
                <div class="tab-pane fade" id="enrolments" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>House</th><th>Role</th><th>Plan</th><th>Progress</th><th>Start</th><th>Expiry</th><th>Payment</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->enrolledClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $en): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <?php
                                                $houseId   = $en->house->id ?? $en->pivot->house_id;
                                                $houseName = $en->house->name ?? ('House #'.$houseId);
                                            ?>
                                            <a href="<?php echo e($routeOr('admin.houses.show', $houseId, "/admin/houses/{$houseId}")); ?>"><?php echo e($houseName); ?></a>
                                        </td>
                                        <td>
                                            <?php
                                                $roleId   = $en->role->id ?? $en->pivot->role_id;
                                                $roleName = $en->role->role ?? ('Role #'.$roleId);
                                            ?>
                                            <a href="<?php echo e($routeOr('admin.roles.show', $roleId, "/admin/roles/{$roleId}")); ?>"><?php echo e($roleName); ?></a>
                                        </td>
                                        <td><?php echo e($en->pivot->plan_id ?: '—'); ?></td>
                                        <td><?php echo e((int)$en->pivot->progress); ?>%</td>
                                        <td><?php echo e(formatDate($en->pivot->start_date, 'M d, Y')); ?></td>
                                        <td><?php echo e(formatDate($en->pivot->expiry_date, 'M d, Y')); ?></td>
                                        <td><?php echo e($en->pivot->payment_status ?: '—'); ?></td>
                                        <td>
                                            <?php if(!is_null($en->pivot->amount_paid)): ?>
                                                <?php echo e(number_format($en->pivot->amount_paid, 2)); ?> <?php echo e($en->pivot->currency_code ?: ''); ?>

                                            <?php else: ?> — <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="8" class="text-center text-muted">No enrolments found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                
                <div class="tab-pane fade" id="logs" role="tabpanel">
                    <div class="table-responsive qa-table">
                        <table class="table table-hover">
                            <thead><tr><th>Date</th><th>Action</th><th>Details</th></tr></thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $user->logs->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="qa-timestamp"><?php echo e(formatDate($log->created_at, 'M d H:i')); ?></td>
                                        <td><?php echo e($log->action ?? 'Unknown'); ?></td>
                                        <td><?php echo e($log->details ?? 'No details'); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No logs found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
/* Only this page */
#user-dashboard .nav.nav-pills .nav-link {
  color: var(--on-surface-variant);
  background: var(--surface-container);
  border-radius: 20px;
  padding: 8px 14px;
  border: 1px solid var(--surface-container-low);
  font-weight: 600;
}
#user-dashboard .nav.nav-pills .nav-link:hover { background: #f1f3f5; }
#user-dashboard .nav.nav-pills .nav-link.active {
  color: var(--on-primary);
  background: var(--info-color);
  border-color: var(--info-color);
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function() {
  const root = document.getElementById('user-dashboard');
  if (!root) return;

  const csrf = root.getAttribute('data-csrf');
  const inlineUrl = root.getAttribute('data-inline-url');
  const toggle = document.getElementById('toggleInline');

  function setEditable(on) {
    document.querySelectorAll('.editable-input').forEach(el => {
      if (el.type === 'checkbox') { el.disabled = !on; return; }
      const isSelect = el.tagName === 'SELECT';
      if (isSelect) el.disabled = !on; else el.readOnly = !on;
      el.classList.add('form-control');
    });
  }

  function toast(msg, error=false) {
    let t = document.getElementById('toaster');
    if (!t) {
      t = document.createElement('div'); t.id = 'toaster';
      t.style.position = 'fixed'; t.style.right = '16px'; t.style.bottom = '16px'; t.style.zIndex = '9999';
      document.body.appendChild(t);
    }
    const el = document.createElement('div');
    el.textContent = msg;
    el.className = 'btn ' + (error ? 'btn-danger' : 'btn-success');
    el.style.pointerEvents = 'none'; el.style.minWidth = '200px';
    t.appendChild(el);
    setTimeout(() => el.remove(), 1800);
  }

  function saveField(field, value) {
    if (!inlineUrl) return;

    // Cast types before sending
    const intFields   = new Set(['role_id','lives','game_level','partner_id']);
    const floatFields = new Set(['maxile_level']);
    const boolFields  = new Set(['partner_verified','diagnostic','email_verified','is_admin']);
    const dateFields  = new Set(['date_of_birth','last_test_date','next_test_date','trial_expires_at','suspended_at','cancelled_at','activated_at','email_verified_at']);

    let payloadValue = value;

    if (boolFields.has(field)) {
      payloadValue = !!value;
    } else if (intFields.has(field)) {
      payloadValue = (value === '' || value === null) ? null : parseInt(value, 10);
      if (Number.isNaN(payloadValue)) payloadValue = null;
    } else if (floatFields.has(field)) {
      payloadValue = (value === '' || value === null) ? null : parseFloat(value);
      if (Number.isNaN(payloadValue)) payloadValue = null;
    } else if (dateFields.has(field)) {
      payloadValue = (value === '' ? null : value);
    } else {
      payloadValue = (typeof value === 'string') ? value.trim() : value;
      if (payloadValue === '') payloadValue = null;
    }

    if (field === 'phone_number' && payloadValue && !/^\+\d{1,4}\s?\d+/.test(payloadValue)) {
      toast('Use a valid phone number (+65 8123 4567)', true); return;
    }

    fetch(inlineUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ field, value: payloadValue })
    })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) throw new Error(d.message || 'Save failed');
      const lu = document.getElementById('last-updated');
      if (lu && d.updated_at_human) lu.textContent = d.updated_at_human;
      toast('Saved');
    })
    .catch(err => { console.error(err); toast(err.message || 'Network error', true); });
  }

  // init
  setEditable(true);
  if (toggle) toggle.addEventListener('change', e => setEditable(e.target.checked));

  // bind
  document.querySelectorAll('.editable-input').forEach(el => {
    const field = el.getAttribute('data-field'); if (!field) return;

    if (el.type === 'checkbox') {
      el.addEventListener('change', () => saveField(field, el.checked));
      return;
    }

    const isSelect = el.tagName === 'SELECT';
    const handler = () => saveField(field, el.value);

    el.addEventListener(isSelect ? 'change' : 'blur', handler);
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' && el.type !== 'date' && el.type !== 'datetime-local') {
        e.preventDefault(); el.blur();
      }
    });
  });

  console.info('User show inline-edit ready:', inlineUrl);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\show.blade.php ENDPATH**/ ?>