<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo $__env->yieldContent('title', 'Admin'); ?></title>
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <main class="py-4">
    <?php echo $__env->yieldContent('content'); ?>
  </main>
</body>
</html>
<?php /**PATH C:\allgifted\mathapi11v2\resources\views\_base\min.blade.php ENDPATH**/ ?>