<?php $__env->startSection('title', 'Checkout - Pasarela de Pagos'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('modules.payment.checkout-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('js/modules/payment/checkout.js')); ?>"></script>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelalaravel\resources\views/pages/payment/checkout.blade.php ENDPATH**/ ?>