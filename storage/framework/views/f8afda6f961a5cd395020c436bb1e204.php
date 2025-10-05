<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title'); ?> - All Gifted Math</title>
    <?php echo $__env->yieldPushContent('head'); ?>    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo e(asset('css/admin-styles.css')); ?>">
    
    <style>
        body {
            background: #eeeeee;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .login-header {
            background: #960000;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 12px 12px 0 0;
        }
        .login-body {
            padding: 30px;
        }
        .btn-primary {
            background: #960000;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #7a0000;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h3><i class="fas fa-graduation-cap me-2"></i>All Gifted Math</h3>
            <p class="mb-0"><?php echo $__env->yieldContent('subtitle', 'Admin Portal'); ?></p>
        </div>
        <div class="login-body">
            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type = 'info') {
            alert(message); // Simple alert for now
        }
        function setFormLoading(form, loading = true) {
            const btn = form.querySelector('[type="submit"]');
            if (btn) btn.disabled = loading;
        }
    </script>
</body>
</html><?php /**PATH C:\allgifted\mathapi11v2\resources\views\layouts\auth.blade.php ENDPATH**/ ?>