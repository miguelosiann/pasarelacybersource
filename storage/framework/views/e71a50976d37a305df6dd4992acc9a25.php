<?php $__env->startSection('body_class', 'payment-failed-page'); ?>
<?php $__env->startSection('title-page'); ?> Pago Fallido <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('modules.payment.failed-content', ['error' => $error], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelalaravel\resources\views/pages/payment/failed.blade.php ENDPATH**/ ?>