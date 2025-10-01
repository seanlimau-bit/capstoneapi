@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Administration Portal')

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
  .form-label { font-weight: 600; margin-bottom: var(--spacing-sm); }
  .form-control { padding: 12px 14px; }
  .btn-auth { width: 100%; }
  .auth-help { margin-top: var(--spacing-sm); color: var(--on-surface-variant); font-size: var(--font-size-sm); text-align: center; }
  .auth-footer { text-align: center; color: var(--on-surface-variant); padding: var(--spacing-lg) var(--spacing-2xl) var(--spacing-2xl); }

  .channel-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: var(--font-size-xs); font-weight: 600; margin-top: 8px; }
  .badge-email { background: rgba(33, 150, 243, 0.1); color: var(--info-color); }
  .badge-phone { background: rgba(80, 210, 0, 0.1); color: var(--success-color); }

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
        <input type="hidden" name="channel" id="channel" value="email">

        <div class="mb-4">
          <label for="identifier" class="form-label">
            <i class="fas fa-user me-2" aria-hidden="true"></i>Email or Phone Number
          </label>
          <input type="text" 
                 class="form-control @error('identifier') is-invalid @enderror" 
                 id="identifier" 
                 name="identifier" 
                 value="{{ old('identifier') }}" 
                 placeholder="email@example.com or +61412345678" 
                 required 
                 autofocus>
          @error('identifier')
            <div class="invalid-feedback"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>{{ $message }}</div>
          @enderror
          <div id="channel-detected" style="display: none;"></div>
          <small class="text-muted d-block mt-2">
            <i class="fas fa-info-circle me-1"></i>Enter your email or phone number with country code
          </small>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-auth" id="submitBtn">
            <span class="submit-text"><i class="fas fa-paper-plane me-2"></i>Send Verification Code</span>
          </button>
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
  const identifierInput = document.getElementById('identifier');
  const channelInput = document.getElementById('channel');
  const channelDetected = document.getElementById('channel-detected');

  function detectChannel(value) {
    value = value.trim();
    
    if (!value) {
      submitBtn.disabled = true;
      channelDetected.style.display = 'none';
      return null;
    }

    // Check if it's an email
    if (value.includes('@') && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      channelInput.value = 'email';
      channelDetected.innerHTML = '<span class="channel-badge badge-email"><i class="fas fa-envelope"></i> Email</span>';
      channelDetected.style.display = 'block';
      submitBtn.disabled = false;
      return 'email';
    }

    // Check if it looks like a phone number
    if (/^\+[1-9]\d{1,14}$/.test(value)) {
      channelInput.value = 'sms';
      channelDetected.innerHTML = '<span class="channel-badge badge-phone"><i class="fas fa-sms"></i> SMS / WhatsApp</span>';
      channelDetected.style.display = 'block';
      submitBtn.disabled = false;
      return 'sms';
    }

    // Invalid format
    submitBtn.disabled = true;
    channelDetected.style.display = 'none';
    return null;
  }

  identifierInput.addEventListener('input', function() {
    detectChannel(this.value);
  });

  identifierInput.addEventListener('blur', function() {
    const channel = detectChannel(this.value);
    if (this.value.trim() && !channel) {
      const hasAt = this.value.includes('@');
      if (hasAt) {
        channelDetected.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Please enter a valid email address</small>';
      } else {
        channelDetected.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Phone must be in format: +61412345678</small>';
      }
      channelDetected.style.display = 'block';
    }
  });

  form.addEventListener('submit', function(e) {
    const val = identifierInput.value.trim();
    const channel = detectChannel(val);
    
    if (!val || !channel) {
      e.preventDefault();
      alert('Please enter a valid email address or phone number');
      identifierInput.focus();
      return;
    }

    submitBtn.disabled = true;
    const submitText = submitBtn.querySelector('.submit-text');
    if (submitText) {
      submitText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Code...';
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