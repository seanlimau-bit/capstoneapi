

<?php $__env->startSection('title', 'Access Pending'); ?>
<?php $__env->startSection('subtitle', 'Your account is awaiting administrator approval'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-page">
  <div class="auth-bg"></div>

  <div class="auth-card">
    <div class="auth-head">
      <div class="brand-mark">
        <?php if(isset($siteSettings['site_logo']) && $siteSettings['site_logo'] && file_exists(public_path($siteSettings['site_logo']))): ?>
          <img src="<?php echo e(asset($siteSettings['site_logo'])); ?>" alt="Logo" style="height: 30px; width: auto; filter: brightness(0) invert(1);">
        <?php else: ?>
          <i class="fas fa-graduation-cap"></i>
        <?php endif; ?>
      </div>
      <div>
        <h1 class="auth-title">Registration Complete!</h1>
        <div class="auth-sub">Account verification successful</div>
      </div>
    </div>

    <div class="auth-body text-center">
      <div class="mb-4">
        <i class="fas fa-clock fa-4x text-warning mb-3"></i>
      </div>
      
      <h3 class="mb-3">Awaiting Administrator Approval</h3>
      
      <p class="text-muted mb-4">
        Your account has been created and verified successfully. However, you currently 
        do not have access to the system.
      </p>
      
      <div class="alert alert-info text-start">
        <i class="fas fa-info-circle me-2"></i>
        <strong>What happens next?</strong><br>
        An administrator will review your registration and assign appropriate permissions 
        to your account. You will be notified via email once access is granted.
      </div>

      <?php if(auth()->check()): ?>
        <div class="mb-3">
          <p class="small text-muted">Registered as: <strong><?php echo e(auth()->user()->email ?? auth()->user()->phone_number); ?></strong></p>
        </div>
      <?php endif; ?>

      <div class="d-grid gap-2">
        <form method="POST" action="<?php echo e(route('auth.logout')); ?>">
          <?php echo csrf_field(); ?>
          <button type="submit" class="btn btn-outline-secondary w-100">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\auth\awaiting-access.blade.php ENDPATH**/ ?>