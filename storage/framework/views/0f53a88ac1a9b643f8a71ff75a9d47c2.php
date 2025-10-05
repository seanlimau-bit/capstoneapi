

<?php $__env->startSection('title', 'Verify Code'); ?>
<?php $__env->startSection('subtitle', 'Enter Verification Code'); ?>

<?php $__env->startPush('styles'); ?>
<style>
  .auth-page { min-height: 100vh; display: grid; place-items: center; padding: var(--spacing-2xl); background: var(--background-color); }
  .auth-bg { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
  .auth-bg::before, .auth-bg::after {
    content: ""; position: absolute; border-radius: 50%; filter: blur(60px); opacity: .25;
  }
  .auth-bg::before { width: 600px; height: 600px; left: -200px; bottom: -200px; background: var(--grad-primary); }
  .auth-bg::after  { width: 520px; height: 520px; right: -160px; top: -160px; background: linear-gradient(135deg, var(--secondary-color), var(--warning-color)); }

  .auth-card { position: relative; z-index: 1; width: 100%; max-width: 460px; background: var(--surface-color); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); overflow: hidden; }
  .auth-head { background: var(--grad-primary); color: var(--on-primary); padding: var(--spacing-xl) var(--spacing-2xl); display: flex; gap: var(--spacing-lg); align-items: center; }
  .brand-mark { display: grid; place-items: center; width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.15); box-shadow: inset 0 0 0 2px rgba(255,255,255,.22); }
  .auth-title { margin: 0; font-weight: 800; font-size: var(--font-size-2xl); letter-spacing: .2px; }
  .auth-sub { margin: 2px 0 0; opacity: .95; font-size: var(--font-size-sm); }
  .auth-body { padding: var(--spacing-2xl); }
  .otp-input { font-size: 1.5rem; letter-spacing: 0.3rem; text-align: center; font-weight: 700; }
  .alert { transition: opacity var(--t); }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-page">
  <div class="auth-bg"></div>

  <div class="auth-card">
    <div class="auth-head">
      <div>
        <h1 class="auth-title"><?php echo $__env->yieldContent('title'); ?></h1>
        <div class="auth-sub"><?php echo $__env->yieldContent('subtitle'); ?></div>
      </div>
    </div>

    <div class="auth-body">
      <?php if(session('error')): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <div><?php echo e(session('error')); ?></div>
        </div>
      <?php endif; ?>
      <form method="POST" action="<?php echo e(route('auth.verifyOtp')); ?>" id="verifyForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="identifier" value="<?php echo e($identifier ?? ''); ?>">

        <div class="mb-4">
          <label for="otp_code" class="form-label">
            <i class="fas fa-key me-2"></i>Verification Code
          </label>
          <input type="text" 
                 class="form-control otp-input <?php $__errorArgs = ['otp_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                 id="otp_code" 
                 name="otp_code" 
                 placeholder="000000" 
                 maxlength="6" 
                 required 
                 autofocus>
          <?php $__errorArgs = ['otp_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="invalid-feedback"><?php echo e($message); ?></div>
          <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
          <small class="text-muted d-block mt-2">
            <i class="fas fa-info-circle me-1"></i>Enter the 6-digit code we sent you
          </small>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary" id="verifyBtn">
            <i class="fas fa-check me-2"></i>Verify & Login
          </button>
          <a href="<?php echo e(route('login')); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Login
          </a>
        </div>
      </form>

      <div class="text-center mt-4">
        <small class="text-muted">
          <i class="fas fa-clock me-1"></i>Code expires in 10 minutes
        </small>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('verifyForm');
  const otpInput = document.getElementById('otp_code');
  const verifyBtn = document.getElementById('verifyBtn');
  let isSubmitting = false;

  // Prevent double submission
  form.addEventListener('submit', function(e) {
    if (isSubmitting) {
      e.preventDefault();
      return;
    }
    
    isSubmitting = true;
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
  });

  // Only allow numbers
  otpInput.addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
    
    // Auto-submit when 6 digits entered
    if (e.target.value.length === 6 && !isSubmitting) {
      setTimeout(() => {
        if (!isSubmitting) {
          verifyBtn.click(); // Use button click instead of form.submit()
        }
      }, 300);
    }
  });

  // Handle paste
  otpInput.addEventListener('paste', function(e) {
    e.preventDefault();
    const paste = (e.clipboardData || window.clipboardData).getData('text');
    const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
    e.target.value = numbers;
    
    if (numbers.length === 6 && !isSubmitting) {
      setTimeout(() => {
        if (!isSubmitting) {
          verifyBtn.click(); // Use button click instead of form.submit()
        }
      }, 300);
    }
  });

  // Auto-dismiss alerts
  document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
      el.style.opacity = '0';
      setTimeout(function() { el.remove(); }, 300);
    }, el.classList.contains('alert-success') ? 3000 : 5000);
  });
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\allgifted\mathapi11v2\resources\views\auth\verify.blade.php ENDPATH**/ ?>