
<?php $__env->startSection('title', 'User Management'); ?>
<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">User Management</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add User
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importUsersModal">
                <i class="fas fa-file-upload"></i> Upload Users
            </button>
        </div>
    </div>

    
    <?php if(session('status')): ?>
        <div class="alert alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <strong>There were some problems with your input.</strong>
            <ul class="mb-0">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($e); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <div class="row mb-4 g-3">
        <?php $__currentLoopData = [['Total', $totals['total'], '#960000'], ['Active', $totals['active'], '#28a745'], ['Verified', $totals['verified'], '#007bff'], ['Suspended', $totals['suspended'] ?? 0, '#fd7e14']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $count, $color]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-3">
                <div class="card text-white" style="background-color:<?php echo e($color); ?>">
                    <div class="card-body">
                        <h2 class="mb-0"><?php echo e($count); ?></h2>
                        <p class="mb-0"><?php echo e($label); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="f" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="f.submit()">
                        <option value="">All Status</option>
                        <?php $__currentLoopData = ['active'=>'Active','inactive'=>'Inactive','suspended'=>'Suspended','verified'=>'Verified','unverified'=>'Unverified']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k=>$v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($k); ?>" <?php echo e(request('status')==$k?'selected':''); ?>><?php echo e($v); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="role_id" class="form-select" onchange="f.submit()">
                        <option value="">All Roles</option>
                        <?php $__currentLoopData = $roles??[]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($r->id); ?>" <?php echo e(request('role_id')==$r->id?'selected':''); ?>><?php echo e($r->role); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo e(request('search')); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        <?php if(request()->hasAny(['search','status','role_id'])): ?>
                            <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar"><?php echo e(substr($u->name??$u->email,0,1)); ?></div>
                                    <div class="ms-2">
                                        <div class="fw-bold"><?php echo e($u->name??'No Name'); ?></div>
                                        <small class="text-muted">ID: <?php echo e($u->id); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo e($u->email); ?></td>
                            <td><span class="badge bg-<?php echo e($u->role?'primary':'secondary'); ?>"><?php echo e($u->role?->role??'No Role'); ?></span></td>
                            <td>
                                <?php
                                    $statuses = ['active'=>'success','inactive'=>'danger','suspended'=>'warning'];
                                    $sColor = $statuses[$u->status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo e($sColor); ?>"><?php echo e(ucfirst($u->status??'Unknown')); ?></span>
                                <span class="badge bg-<?php echo e($u->email_verified_at?'info':'warning'); ?>"><?php echo e($u->email_verified_at?'Verified':'Unverified'); ?></span>
                            </td>
                            <td><small><?php echo e($u->created_at?->format('M d, Y')??'Unknown'); ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if(Route::has('admin.users.show')): ?>
                                        <a href="<?php echo e(route('admin.users.show', $u)); ?>" class="btn btn-outline-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if($u->id !== auth()->id()): ?>
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="if(confirm('Delete user?')){
                                            const f = document.createElement('form');
                                            f.method = 'POST'; // <-- must be POST
                                            f.action = '<?php echo e(route('admin.users.destroy', $u)); ?>';
                                            f.style.display = 'none';

                                            const csrf = document.createElement('input');
                                            csrf.type = 'hidden';
                                            csrf.name = '_token';
                                            csrf.value = '<?php echo e(csrf_token()); ?>';
                                            f.appendChild(csrf);

                                            const method = document.createElement('input');
                                            method.type = 'hidden';
                                            method.name = '_method';
                                            method.value = 'DELETE';
                                            f.appendChild(method);

                                            document.body.appendChild(f);
                                            f.submit();
                                        }">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No users found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($users->hasPages()): ?>
            <div class="p-3 border-top"><?php echo e($users->appends(request()->query())->links()); ?></div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="<?php echo e(url('admin/users')); ?>" class="modal-content">
      <?php echo csrf_field(); ?>
      <div class="modal-header">
        <h5 class="modal-title" id="addUserLabel">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Date of Birth</label>
              <input type="Date" name="date_of_birth" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
          </div>
          <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="tel" name="contact" class="form-control"
                    placeholder="+65 8450 8153"
                    pattern="^\+\d{1,3}(\s?\d{4,})+$"
                    required>
              <small class="form-text text-muted">
                  Format: +CountryCode Number (e.g. +65 9123 4567 or +6591234567)
              </small>
          </div>

          <?php if(!empty($roles)): ?>
          <div class="mb-3">
              <label class="form-label">Role</label>
              <select name="role_id" class="form-select">
                  <option value="">Select role</option>
                  <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <option value="<?php echo e($r->id); ?>"><?php echo e($r->role); ?></option>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
          </div>
          <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Create
        </button>
      </div>
    </form>
  </div>
</div>


<div class="modal fade" id="importUsersModal" tabindex="-1" aria-labelledby="importUsersLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="<?php echo e(url('admin/users/import')); ?>" enctype="multipart/form-data" class="modal-content">
      <?php echo csrf_field(); ?>
      <div class="modal-header">
        <h5 class="modal-title" id="importUsersLabel">Upload Users (CSV / Excel)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label class="form-label">File</label>
              <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
              <small class="text-muted">Accepted: .csv, .xlsx, .xls</small>
          </div>
          <div class="form-text">
              Example headers: <code>name,email,password,role_id,status</code>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-upload"></i> Import
        </button>
      </div>
    </form>
  </div>
</div>

<style>
.avatar{width:40px;height:40px;border-radius:50%;background:#960000;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;text-transform:uppercase}
.card{border:none;box-shadow:0 .125rem .25rem rgba(0,0,0,.075);border-radius:.375rem}
.badge{font-size:.75em;margin-right:.25rem}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\admin\users\index.blade.php ENDPATH**/ ?>