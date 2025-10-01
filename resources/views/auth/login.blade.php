@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Administration Portal')

@push('styles')
<style>
  /* Scoped auth styles using your token system */
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
  .form-label { font-weight: 600; }
  .form-control { padding: 12px 14px; }
  .btn-auth { width: 100%; }
  .auth-help { margin-top: var(--spacing-sm); color: var(--on-surface-variant); font-size: var(--font-size-sm); text-align: center; }
  .auth-footer { text-align: center; color: var(--on-surface-variant); padding: var(--spacing-lg) var(--spacing-2xl) var(--spacing-2xl); }

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
          <i class="fas fa-graduation-cap" aria-hidden="true"></i>
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

      <form method="POST" action="{{ route('auth.sendOtp') }}" id="loginForm" novalidate>
        @csrf
        <div class="mb-4">
          <label for="email" class="form-label"><i class="fas fa-envelope me-2" aria-hidden="true"></i>Email Address</label>
          <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required autofocus autocomplete="email">
          @error('email')
            <div class="invalid-feedback"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>{{ $message }}</div>
          @enderror
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-auth" id="submitBtn">
            <span class="submit-text"><i class="fas fa-paper-plane me-2"></i>Send Verification Code</span>
          </button>
          <div class="auth-help">We will send a secure 6 digit verification code</div>
        </div>
      </form>
    </div>

    <div class="auth-footer small">
      <i class="fas fa-question-circle me-1"></i>
      Need help? Contact your system administrator
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const emailInput = document.getElementById('email');

    function setFormLoading(isLoading) {
      if (typeof setLoadingState === 'function') { setLoadingState(submitBtn, isLoading); return; }
      submitBtn.disabled = !!isLoading;
      submitBtn.classList.toggle('disabled', !!isLoading);
    }
    function toast(msg, type) { if (typeof showToast==='function') showToast(msg,type); }

    form.addEventListener('submit', function(e) {
      const val = emailInput.value.trim();
      if (!val) { e.preventDefault(); toast('Please enter your email address', 'warning'); emailInput.focus(); return; }
      const rx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!rx.test(val)) { e.preventDefault(); toast('Please enter a valid email address', 'warning'); emailInput.focus(); return; }

      setFormLoading(true);
      const t = submitBtn.querySelector('.submit-text');
      if (t) t.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Code...';
      setTimeout(function(){ if (submitBtn.disabled) { setFormLoading(false); if (t) t.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Verification Code'; } }, 10000);
    });

    emailInput.addEventListener('input', function() {
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim());
      submitBtn.disabled = !ok; submitBtn.classList.toggle('opacity-50', !ok);
      if (this.classList.contains('is-invalid')) { this.classList.remove('is-invalid'); const fb = this.parentNode.querySelector('.invalid-feedback'); if (fb) fb.style.display = 'none'; }
    });

    document.querySelectorAll('.alert').forEach(function(el){ setTimeout(function(){ el.style.opacity = '0'; setTimeout(function(){ el.remove(); }, 300); }, el.classList.contains('alert-success') ? 3000 : 5000); });

    if (!emailInput.value.trim()) { submitBtn.disabled = true; submitBtn.classList.add('opacity-50'); }
  });

  @if(session('success')) setTimeout(function(){ if (typeof showToast==='function') showToast(@json(session('success')), 'success'); }, 100); @endif
  @if(session('error'))   setTimeout(function(){ if (typeof showToast==='function') showToast(@json(session('error')), 'error'); }, 100);   @endif
</script>
@endpush