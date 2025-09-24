@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Quality Assurance Portal')

@section('content')
<!-- Alert Messages -->
@if(session('error'))
    <div class="auth-alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="auth-alert alert-success">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
@endif

<!-- Login Form -->
<form method="POST" action="{{ route('auth.sendOtp') }}" class="auth-form" id="loginForm">
    @csrf
    
    <div class="mb-4">
  
        <label for="email" class="form-label">
            <i class="fas fa-envelope me-2"></i>Email Address
        </label>
        <input 
            type="email" 
            class="form-control @error('email') is-invalid @enderror" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            placeholder="Enter your email address"
            required 
            autofocus
            autocomplete="email"
        >
        @error('email')
            <div class="invalid-feedback">
                <i class="fas fa-exclamation-triangle me-1"></i>{{ $message }}
            </div>
        @enderror

    </div>
    
    <div class="d-grid">
        <button type="submit" class="auth-submit" id="submitBtn">
            <span class="loading-spinner"></span>
            <span class="submit-text">
                <i class="fas fa-paper-plane me-2"></i>Send Verification Code
            </span>
        </button>
          <div class="auth-help">
             We'll send you a secure 6-digit verification code
        </div>
    </div>

</form>

<!-- Additional Help -->
<div class="mt-4 text-center">
    <small class="text-muted">
        <i class="fas fa-question-circle me-1"></i>
        Need help? Contact your system administrator
    </small>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const emailInput = document.getElementById('email');
    
    // Enhanced form submission with loading state
    form.addEventListener('submit', function(e) {
        // Basic validation
        if (!emailInput.value.trim()) {
            e.preventDefault();
            showToast('Please enter your email address', 'warning');
            emailInput.focus();
            return;
        }
        
        // Email format validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailInput.value.trim())) {
            e.preventDefault();
            showToast('Please enter a valid email address', 'warning');
            emailInput.focus();
            return;
        }
        
        // Set loading state
        setFormLoading(form, true);
        
        // Update button text
        const submitText = submitBtn.querySelector('.submit-text');
        submitText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending Code...';
        
        // If form submission fails, reset the button after 10 seconds
        setTimeout(function() {
            if (submitBtn.classList.contains('loading')) {
                setFormLoading(form, false);
                submitText.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Send Verification Code';
            }
        }, 10000);
    });
    
    // Real-time email validation
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        
        // Remove invalid state when user starts typing
        if (this.classList.contains('is-invalid')) {
            this.classList.remove('is-invalid');
            const feedback = this.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.style.display = 'none';
            }
        }
        
        // Enable/disable submit button based on email validity
        if (email.length > 0) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email)) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50');
            }
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50');
        }
    });
    
    // Enter key handling
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !submitBtn.disabled) {
            form.submit();
        }
    });
    
    // Auto-clear old error messages after 5 seconds
    const errorAlert = document.querySelector('.auth-alert.alert-danger');
    if (errorAlert) {
        setTimeout(function() {
            errorAlert.style.opacity = '0';
            setTimeout(function() {
                errorAlert.remove();
            }, 300);
        }, 5000);
    }
    
    // Auto-clear success messages after 3 seconds
    const successAlert = document.querySelector('.auth-alert.alert-success');
    if (successAlert) {
        setTimeout(function() {
            successAlert.style.opacity = '0';
            setTimeout(function() {
                successAlert.remove();
            }, 300);
        }, 3000);
    }
    
    // Initial button state
    if (!emailInput.value.trim()) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50');
    }
});

// Show success message for successful operations
@if(session('success'))
    setTimeout(function() {
        showToast('{{ session('success') }}', 'success');
    }, 100);
@endif

// Show error message for failed operations  
@if(session('error'))
    setTimeout(function() {
        showToast('{{ session('error') }}', 'error');
    }, 100);
@endif
</script>

<style>
/* Additional login-specific styles */
.auth-submit.opacity-50 {
    opacity: 0.5;
    cursor: not-allowed;
}

.auth-submit.opacity-50:hover {
    transform: none;
    box-shadow: none;
}

.form-control:invalid {
    box-shadow: none;
}

.form-control:valid {
    border-color: var(--success-color);
}

/* Smooth transitions for alert removal */
.auth-alert {
    transition: opacity 0.3s ease;
}

/* Focus enhancement */
.form-control:focus {
    position: relative;
    z-index: 2;
}

/* Loading state refinements */
.auth-submit.loading {
    position: relative;
    overflow: hidden;
}

.auth-submit.loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}
</style>
@endpush
@endsection