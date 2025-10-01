@extends('layouts.auth')

@section('title', 'Verify Code')
@section('subtitle', 'Enter Verification Code')

@push('styles')
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
@endpush

@section('content')
<div class="auth-page">
  <div class="auth-bg"></div>

  <div class="auth-card">
    <div class="auth-head">
      <div class="brand-mark">
        @if(isset($siteSettings['site_logo']) && $siteSettings['site_logo'] && file_exists(public_path($siteSettings['site_logo'])))
          <img src="{{ asset($siteSettings['site_logo']) }}" alt="Logo" style="height: 30px; width: auto; filter: brightness(0) invert(1);">
        @else
          <i class="fas fa-graduation-cap"></i>
        @endif
      </div>
      <div>
        <h1 class="auth-title">@yield('title')</h1>
        <div class="auth-sub">@yield('subtitle')</div>
      </div>
    </div>

    <div class="auth-body">
      @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i>
          <div>{{ session('error') }}</div>
        </div>
      @endif
      @if(session('success'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
          <i class="fas fa-check-circle me-2"></i>
          <div>{{ session('success') }}</div>
        </div>
      @endif

      <div class="alert alert-info mb-4">
        @if(filter_var($identifier ?? '', FILTER_VALIDATE_EMAIL))
          <i class="fas fa-envelope me-2"></i>
          Code sent to <strong>{{ $identifier }}</strong>
        @else
          <i class="fas fa-mobile-alt me-2"></i>
          Code sent to <strong>{{ $identifier }}</strong>
        @endif
      </div>

      <form method="POST" action="{{ route('auth.verifyOtp') }}" id="verifyForm">
        @csrf
        <input type="hidden" name="identifier" value="{{ $identifier ?? '' }}">

        <div class="mb-4">
          <label for="otp_code" class="form-label">
            <i class="fas fa-key me-2"></i>Verification Code
          </label>
          <input type="text" 
                 class="form-control otp-input @error('otp_code') is-invalid @enderror" 
                 id="otp_code" 
                 name="otp_code" 
                 placeholder="000000" 
                 maxlength="6" 
                 required 
                 autofocus>
          @error('otp_code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <small class="text-muted d-block mt-2">
            <i class="fas fa-info-circle me-1"></i>Enter the 6-digit code we sent you
          </small>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary" id="verifyBtn">
            <i class="fas fa-check me-2"></i>Verify & Login
          </button>
          <a href="{{ route('login') }}" class="btn btn-outline-secondary">
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
@endsection

@push('scripts')
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
@endpush