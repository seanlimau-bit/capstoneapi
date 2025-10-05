


<?php $__env->startSection('title', 'Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-12">
            <h2>All Gifted Math - Admin Dashboard</h2>
            <p class="text-muted">Manage your math learning platform</p>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo e(number_format($stats['total_questions'])); ?></h3>
                    <small>Total Questions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo e(number_format($stats['active_users'])); ?></h3>
                    <small>Active Users</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3><?php echo e(number_format($stats['pending_qa'])); ?></h3>
                    <small>Pending QA</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo e(number_format($stats['total_skills'])); ?></h3>
                    <small>Skills</small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-clipboard-check"></i> Quality Assurance</h5>
                </div>
                <div class="card-body">
                    <p>Review and approve questions, manage QA workflow</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Review Questions</li>
                        <li><i class="fas fa-flag text-warning"></i> Flag Issues</li>
                        <li><i class="fas fa-chart-bar text-info"></i> QA Analytics</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('qa.index')); ?>" class="btn btn-primary">Go to QA System</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-question-circle"></i> Question Management</h5>
                </div>
                <div class="card-body">
                    <p>Create, edit, and organize math questions</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-plus text-success"></i> Add New Questions</li>
                        <li><i class="fas fa-edit text-primary"></i> Edit Existing</li>
                        <li><i class="fas fa-copy text-info"></i> Bulk Operations</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('admin.questions.index')); ?>" class="btn btn-success">Manage Questions</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-cogs"></i> Skills & Content</h5>
                </div>
                <div class="card-body">
                    <p>Manage skills, categories, and learning paths</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-brain text-purple"></i> Skill Management</li>
                        <li><i class="fas fa-layer-group text-primary"></i> Categories</li>
                        <li><i class="fas fa-route text-success"></i> Learning Paths</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <a href="<?php echo e(route('admin.skills.index')); ?>" class="btn btn-info">Manage Skills</a>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-users"></i> User Management</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-user"></i> All Users
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo e(route('admin.users.partners')); ?>" class="btn btn-outline-success btn-block">
                                <i class="fas fa-handshake"></i> Partners
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Analytics & Reports</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <a href="<?php echo e(route('admin.reports.usage')); ?>" class="btn btn-outline-info btn-block">
                                <i class="fas fa-chart-bar"></i> Usage Reports
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo e(route('admin.reports.performance')); ?>" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-tachometer-alt"></i> Performance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\dashboard.blade.php ENDPATH**/ ?>