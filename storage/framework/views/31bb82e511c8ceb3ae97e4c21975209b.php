<?php $__env->startSection('body_class', 'payment-success-page'); ?>
<?php $__env->startSection('title-page'); ?> Pago Exitoso <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('modules.payment.success-content', ['payment' => $payment], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
@media print {
    .btn, .alert-light, .card-actions {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>
<?php $__env->stopPush(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/success.blade.php ENDPATH**/ ?>