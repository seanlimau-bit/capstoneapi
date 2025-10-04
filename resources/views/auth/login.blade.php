{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Administration Portal')

@php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

$site = $site ?? DB::table('configs')->first();

$resolve = function (?string $path) {
if (!$path) return null;
if (Str::startsWith($path, ['http://','https://','//'])) return $path;
if (Storage::disk('public')->exists($path)) return Storage::url($path);     // /storage/...
if (file_exists(public_path($path))) return asset($path);                    // /public/...
return null;
};

$logoUrl    = $resolve($site->site_logo ?? null);
$faviconUrl = $resolve($site->favicon   ?? null);
@endphp

@push('head') {{-- ensure @stack('head') exists in layouts.auth <head> --}}
  @if($faviconUrl)
  <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
  <link rel="shortcut icon" href="{{ $faviconUrl }}">
  <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
  @endif
  @endpush

  @push('styles')
  <style>
    .auth-page { min-height: 100vh; display: grid; place-items: center; padding: var(--spacing-2xl); background: var(--background-color); }
    .auth-bg { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
    .auth-bg::before, .auth-bg::after { content: ""; position: absolute; border-radius: 50%; filter: blur(60px); opacity: .18; }
    .auth-bg::before { width: 600px; height: 600px; left: -200px; bottom: -200px; background: var(--grad-primary); }
    .auth-bg::after  { width: 520px; height: 520px; right: -160px; top: -160px; background: linear-gradient(135deg, var(--secondary-color), var(--warning-color)); }

    .auth-card { position: relative; z-index: 1; width: 100%; max-width: 440px; background: var(--surface-color); border-radius: var(--radius-xl); box-shadow: var(--shadow-lg); overflow: hidden; }
    .auth-head { background: var(--grad-primary); color: var(--on-primary); padding: var(--spacing-lg) var(--spacing-2xl); display: flex; gap: var(--spacing-md); align-items: center; }
    .brand-mark { display: grid; place-items: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 10px; background: rgba(255,255,255,.12); box-shadow: inset 0 0 0 2px rgba(255,255,255,.2); }
    .brand-logo { height: 22px; width: auto; display: block; } /* if logo is dark on dark bg, you can invert: filter: brightness(0) invert(1); */

    .auth-title { margin: 0; font-weight: 800; font-size: var(--font-size-xl); letter-spacing: .2px; }
    .auth-sub   { margin: 2px 0 0; opacity: .95; font-size: var(--font-size-sm); }

    .auth-body { padding: var(--spacing-2xl); }
    .form-label { font-weight: 600; margin-bottom: var(--spacing-xs); }
    .form-control { padding: 12px 14px; border-radius: var(--radius-md); }
    .form-control:focus { box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-color) 15%, transparent); border-color: var(--primary-color); }
    .btn-auth { width: 100%; padding: 12px 14px; border-radius: var(--radius-md); }
    .auth-footer { text-align: center; color: var(--on-surface-variant); padding: var(--spacing-md) var(--spacing-2xl) var(--spacing-2xl); }

    .channel-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: var(--font-size-xs); font-weight: 600; margin-top: 8px; }
    .badge-email { background: rgba(33,150,243,.12); color: var(--info-color); }
    .badge-phone { background: rgba(80,210,0,.12); color: var(--success-color); }

    .otp-chip { font-size: 1.25rem; letter-spacing: .3rem; text-align: center; font-weight: 700; }

    [hidden] { display: none !important; }  /* bulletproof hiding */
    .alert { transition: opacity .25s ease; }
  </style>
  @endpush

  @section('content')
  <div class="auth-page">
    <div class="auth-bg" aria-hidden="true"></div>

    <div class="auth-card" role="form" aria-labelledby="authTitle">
      <h1 id="authTitle" class="auth-title">@yield('title')</h1>
    </div>
  </div>

  <div class="auth-body">
    <div id="alertContainer" aria-live="polite" aria-atomic="true">
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
    </div>

    {{-- Step 1: SEND OTP (visible first) --}}
    <form method="POST" action="{{ route('auth.sendOtp') }}" id="sendForm" novalidate>
      @csrf
      <input type="hidden" name="channel" id="channel" value="email">

      <div class="mb-4">
        <label for="identifier" class="form-label">
          <i class="fas fa-user me-2" aria-hidden="true"></i> Email or Phone Number
        </label>
        <input
        type="text"
        class="form-control @error('identifier') is-invalid @enderror"
        id="identifier"
        name="identifier"
        value="{{ old('identifier') }}"
        placeholder="email@example.com or +61412345678"
        required
        autofocus
        autocomplete="username"
        >
        @error('identifier')
        <div class="invalid-feedback"><i class="fas fa-exclamation-triangle me-1" aria-hidden="true"></i>{{ $message }}</div>
        @enderror
        <div id="channel-detected" style="display:none;"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-auth" id="sendBtn">
        <span class="send-text"><i class="fas fa-paper-plane me-2"></i>Send Verification Code</span>
      </button>
    </form>

    {{-- Step 2: VERIFY OTP (hidden until Step 1 succeeds) --}}
    <form method="POST" action="{{ route('auth.verifyOtp') }}" id="verifyForm" hidden>
      @csrf
      <input type="hidden" name="identifier" id="identifierHidden" value="">
      <div class="alert alert-info d-flex align-items-center mb-3" id="sentInfo">
        <i class="fas fa-info-circle me-2"></i>
        <div>Code sent to <strong id="sentTo"></strong></div>
      </div>

      <div class="mb-3">
        <label for="otp_code" class="form-label">
          <i class="fas fa-key me-2" aria-hidden="true"></i> Verification Code
        </label>
        <input
        type="text"
        class="form-control otp-chip @error('otp_code') is-invalid @enderror"
        id="otp_code"
        name="otp_code"
        inputmode="numeric"
        pattern="[0-9]*"
        placeholder="000000"
        maxlength="6"
        required
        >
        @error('otp_code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted d-block mt-2"><i class="fas fa-clock me-1"></i> Code expires in 10 minutes</small>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary" id="verifyBtn">
          <i class="fas fa-check me-2"></i>Verify & Login
        </button>
        <button type="button" class="btn btn-outline-secondary" id="backBtn">
          <i class="fas fa-arrow-left me-2"></i>Back
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
  document.addEventListener('DOMContentLoaded', function () {
  // ——— DOM
  const alertContainer   = document.getElementById('alertContainer');
  const sendForm         = document.getElementById('sendForm');
  const sendBtn          = document.getElementById('sendBtn');
  const sendText         = document.querySelector('.send-text');
  const identifierInput  = document.getElementById('identifier');
  const channelInput     = document.getElementById('channel');
  const channelDetected  = document.getElementById('channel-detected');

  const verifyForm       = document.getElementById('verifyForm');
  const verifyBtn        = document.getElementById('verifyBtn');
  const otpInput         = document.getElementById('otp_code');
  const backBtn          = document.getElementById('backBtn');
  const sentToEl         = document.getElementById('sentTo');
  const identifierHidden = document.getElementById('identifierHidden');

  // ——— Helpers
  function showAlert(message, type = 'danger') {
    const icon = type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle';
    const el = document.createElement('div');
    el.className = `alert alert-${type} d-flex align-items-center`;
    el.role = 'alert';
    el.innerHTML = `<i class="fas fa-${icon} me-2"></i><div>${message}</div>`;
    alertContainer.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 250);
    }, type === 'success' || type === 'info' ? 3000 : 5000);
  }

  function detectChannel(value) {
    value = (value || '').trim();
    if (!value) {
      channelDetected.style.display = 'none';
      sendBtn.disabled = true;
      return null;
    }
    const isEmail = value.includes('@') && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    const isPhone = /^\+[1-9]\d{7,14}$/.test(value);
    if (isEmail) {
      channelInput.value = 'email';
      channelDetected.innerHTML = '<span class="channel-badge badge-email"><i class="fas fa-envelope"></i> Email</span>';
      channelDetected.style.display = 'inline-flex';
      sendBtn.disabled = false;
      return 'email';
    }
    if (isPhone) {
      channelInput.value = 'sms';
      channelDetected.innerHTML = '<span class="channel-badge badge-phone"><i class="fas fa-sms"></i> SMS / WhatsApp</span>';
      channelDetected.style.display = 'inline-flex';
      sendBtn.disabled = false;
      return 'sms';
    }
    channelDetected.style.display = 'none';
    sendBtn.disabled = true;
    return null;
  }

  async function postJSON(url, payload) {
    const res = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify(payload)
    });
    let data = {};
    try { data = await res.json(); } catch {}
    return { ok: res.ok, status: res.status, data };
  }

  function revealOtp(contact) {
    // Set identifier into the hidden verify form, show OTP
    identifierHidden.value = contact;
    sentToEl.textContent = contact;
    sendForm.hidden = true;
    verifyForm.hidden = false;
    otpInput.value = '';
    otpInput.focus();
    showAlert('Verification code sent.', 'info');
  }

  function backToSend() {
    verifyForm.hidden = true;
    sendForm.hidden = false;
    otpInput.value = '';
    identifierInput.focus();
  }

  // ——— Init
  detectChannel(identifierInput.value);

  // ——— Events
  identifierInput.addEventListener('input', function () {
    detectChannel(this.value);
  });

  identifierInput.addEventListener('blur', function () {
    const ch = detectChannel(this.value);
    if (this.value.trim() && !ch) {
      const msg = this.value.includes('@')
      ? 'Please enter a valid email address'
      : 'Phone must be in international format, for example +61412345678';
      channelDetected.innerHTML = '<small class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>' + msg + '</small>';
      channelDetected.style.display = 'block';
    }
  });

  sendForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const identifier = identifierInput.value.trim();
    const ch = detectChannel(identifier);
    if (!identifier || !ch) {
      showAlert('Please enter a valid email address or phone number.');
      identifierInput.focus();
      return;
    }
    sendBtn.disabled = true;
    if (sendText) sendText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';

    const { ok, status, data } = await postJSON(@json(route('auth.sendOtp')), { identifier, channel: ch });

    sendBtn.disabled = false;
    if (sendText) sendText.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Verification Code';

    if (ok) {
      revealOtp(identifier);
    } else {
      if (status === 419) showAlert('Your session expired. Refresh and try again.');
      else showAlert((data && data.message) || 'Failed to send verification code.');
    }
  });

  // OTP behaviors: numeric only, paste, auto-submit at 6 digits
  let verifying = false;

  verifyForm.addEventListener('submit', function(e) {
    if (verifying) { e.preventDefault(); return; }
    verifying = true;
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
  });

  otpInput.addEventListener('input', function (e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
    if (e.target.value.length === 6 && !verifying) {
      // let the UI breathe for a tick, then submit
      setTimeout(() => verifyForm.requestSubmit(), 150);
    }
  });

  otpInput.addEventListener('paste', function (e) {
    e.preventDefault();
    const raw = (e.clipboardData || window.clipboardData).getData('text') || '';
    const code = raw.replace(/\D/g, '').slice(0, 6);
    otpInput.value = code;
    if (code.length === 6 && !verifying) {
      setTimeout(() => verifyForm.requestSubmit(), 100);
    }
  });

  backBtn.addEventListener('click', backToSend);

  // Auto-dismiss any server-rendered alerts present on load
  document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 250);
    }, el.classList.contains('alert-success') || el.classList.contains('alert-info') ? 3000 : 5000);
  });
});
</script>
@endpush
