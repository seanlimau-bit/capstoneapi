<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - All Gifted Math</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Your Admin CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-styles.css') }}">
    
    <!-- Custom Auth Styles -->
    <style>
        .auth-body {
            background: linear-gradient(135deg, var(--background-color) 0%, var(--surface-container) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        
        .auth-container {
            width: 100%;
            max-width: 440px;
        }
        
        .auth-card {
            background: var(--surface-color);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: none;
        }
        
        .auth-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--on-primary);
            padding: var(--spacing-2xl) var(--spacing-xl) var(--spacing-xl);
            text-align: center;
            position: relative;
        }
        
        .auth-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: var(--surface-color);
            border-radius: 20px 20px 0 0;
        }
        
        .auth-logo {
            font-size: var(--font-size-3xl);
            font-weight: bold;
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
        }
        
        .auth-subtitle {
            opacity: 0.9;
            font-size: var(--font-size);
            margin-bottom: 0;
        }
        
        .auth-body {
            padding: var(--spacing-xl);
        }
        
        .auth-description {
            color: var(--on-surface-variant);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            font-size: var(--font-size-sm);
        }
        
        .auth-form .form-label {
            font-weight: 600;
            color: var(--on-surface);
            margin-bottom: var(--spacing-sm);
        }
        
        .auth-form .form-control {
            border: 1.5px solid var(--outline);
            border-radius: var(--border-radius-sm);
            padding: 14px 16px;
            font-size: var(--font-size);
            background-color: var(--input-background);
            transition: all var(--transition);
        }
        
        .auth-form .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(150, 0, 0, 0.1);
            background-color: var(--surface-color);
        }
        
        .auth-submit {
            width: 100%;
            padding: 16px;
            font-size: var(--font-size);
            font-weight: 600;
            border-radius: var(--border-radius-sm);
            transition: all var(--transition);
            border: none;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--on-primary);
            letter-spacing: 0.5px;
        }
        
        .auth-submit:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }
        
        .auth-submit:active {
            transform: translateY(0);
        }
        
        .auth-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-alert {
            border: none;
            border-radius: var(--border-radius-sm);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .auth-alert.alert-danger {
            background-color: rgba(216, 0, 0, 0.08);
            color: var(--error-dark);
            border-left: 4px solid var(--error-color);
        }
        
        .auth-alert.alert-success {
            background-color: rgba(80, 210, 0, 0.08);
            color: var(--success-dark);
            border-left: 4px solid var(--success-color);
        }
        
        .auth-help {
            color: var(--on-surface-variant);
            font-size: var(--font-size-xs);
            margin-top: var(--spacing-xs);
        }
        
        .auth-footer {
            background-color: var(--surface-container);
            padding: var(--spacing-lg) var(--spacing-xl);
            text-align: center;
            color: var(--on-surface-variant);
            font-size: var(--font-size-sm);
        }
        
        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: var(--spacing-sm);
        }
        
        .auth-submit.loading .loading-spinner {
            display: inline-block;
        }
        
        .auth-submit.loading .submit-text {
            opacity: 0.7;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .auth-body {
                padding: var(--spacing-md);
            }
            
            .auth-header {
                padding: var(--spacing-xl) var(--spacing-lg) var(--spacing-lg);
            }
            
            .auth-body {
                padding: var(--spacing-lg);
            }
            
            .auth-logo {
                font-size: var(--font-size-2xl);
            }
        }
        
        /* Form validation styles */
        .form-control.is-invalid {
            border-color: var(--error-color);
            box-shadow: 0 0 0 3px rgba(216, 0, 0, 0.1);
        }
        
        .invalid-feedback {
            color: var(--error-color);
            font-size: var(--font-size-xs);
            margin-top: var(--spacing-xs);
        }
        
        /* Focus trap for accessibility */
        .auth-card:focus-within {
            box-shadow: var(--shadow-lg), 0 0 0 3px rgba(150, 0, 0, 0.1);
        }
    </style>
    
    @stack('styles')
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="card auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-graduation-cap"></i>
                    All Gifted Math
                </div>
                <p class="auth-subtitle">@yield('subtitle', 'Admin Portal')</p>
            </div>
            
            <div class="auth-body">
                @yield('content')
            </div>
            
            <div class="auth-footer">
                <small>&copy; {{ date('Y') }} All Gifted Math. All rights reserved.</small>
            </div>
        </div>
    </div>

    <!-- Toast Container for notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
        <!-- Toasts will be inserted here -->
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Toast notification function -->
    <script>
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            
            const iconMap = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            const colorMap = {
                success: 'text-success',
                error: 'text-danger',
                warning: 'text-warning',
                info: 'text-info'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center border-0`;
            toast.id = toastId;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="fas ${iconMap[type]} ${colorMap[type]} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, {
                delay: type === 'error' ? 6000 : 4000
            });
            
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }
        
        // Form loading state helper
        function setFormLoading(form, loading = true) {
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) {
                if (loading) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                } else {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }
            }
        }
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('.auth-form input[type="email"], .auth-form input[type="text"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>