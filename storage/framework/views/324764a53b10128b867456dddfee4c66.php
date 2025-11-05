<?php $__env->startSection('title', 'Debug - Pasarela de Pagos'); ?>

<?php $__env->startPush('head'); ?>
    <?php echo $__env->make('components.payment.device-fingerprinting', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('components.payment.device-fingerprinting-noscript', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('modules.payment.debug-content', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    
    <style>
        .step-result {
            display: none;
        }
        .step-result.show {
            display: block;
        }
    </style>
    
    <script src="<?php echo e(asset('js/modules/payment/debug.js')); ?>"></script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/debug.blade.php ENDPATH**/ ?>