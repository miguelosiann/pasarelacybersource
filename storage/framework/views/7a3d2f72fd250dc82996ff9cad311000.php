<?php $__env->startSection('body_class', 'payment-challenge-page'); ?>
<?php $__env->startSection('title-page'); ?> Autenticación 3D Secure <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $__env->make('modules.payment.challenge-content', ['challengeData' => $challengeData], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/challenge.blade.php ENDPATH**/ ?>