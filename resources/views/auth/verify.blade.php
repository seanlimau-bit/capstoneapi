@extends('layouts.auth')

@section('title', 'Verify Code')
@section('subtitle', 'Enter Verification Code')

@section('content')
@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

<div class="alert alert-info mb-4">
    <i class="fas fa-envelope me-2"></i>
    Please check your email at
    <strong>{{ $email ?? 'your email' }}</strong>
</div>

<form method="POST" action="{{ route('auth.verifyOtp') }}" id="verifyForm">
    @csrf
    <input type="hidden" name="email" value="{{ $email ?? '' }}">
    
    <div class="mb-4">
        <label for="otp_code" class="form-label">
            <i class="fas fa-key me-2"></i>Verification Code
        </label>
        <input type="text" 
               class="form-control text-center @error('otp_code') is-invalid @enderror" 
               id="otp_code" 
               name="otp_code" 
               placeholder="000000" 
               maxlength="6" 
               required 
               autofocus
               style="font-size: 1.5rem; letter-spacing: 0.3rem;">
        @error('otp_code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="text-muted">Enter the 6-digit code from your email</small>
    </div>
    
    <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary" id="verifyBtn">
            <i class="fas fa-check me-2"></i>Verify & Login
        </button>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Use Different Email
        </a>
    </div>
</form>

<div class="text-center mt-4">
    <small class="text-muted">
        <i class="fas fa-clock me-1"></i>
        Code expires in 10 minutes
    </small>
</div>

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
        
        // Auto-submit when 6 digits entered (with protection)
        if (e.target.value.length === 6 && !isSubmitting) {
            setTimeout(() => {
                if (!isSubmitting) {
                    form.submit();
                }
            }, 500);
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
                    form.submit();
                }
            }, 500);
        }
    });
});
</script>
@endsection