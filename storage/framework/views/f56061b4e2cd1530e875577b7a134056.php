<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo $__env->yieldContent('title', 'Pasarela CyberSource'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- jQuery (necesario para algunos scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Estilos personalizados -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin: 0 10px;
            transition: color 0.3s;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
        }

        .content-wrapper {
            padding: 30px 0;
            min-height: calc(100vh - 200px);
        }

        .footer {
            background: #2d3748;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Loading spinner */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner-overlay.active {
            display: flex;
        }

        .spinner-border-custom {
            width: 3rem;
            height: 3rem;
            border: 0.3rem solid #ffffff;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to { transform: rotate(360deg); }
        }
    </style>

    <!-- Estilos adicionales de la página -->
    <?php echo $__env->yieldContent('styles'); ?>

    <!-- Vite Assets (si es necesario) -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-credit-card me-2"></i>
                Pasarela CyberSource
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(route('payment.checkout')); ?>">
                            <i class="fas fa-shopping-cart me-1"></i> Checkout
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(route('payment.debug.index')); ?>">
                            <i class="fas fa-bug me-1"></i> Debug
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(route('payment.history')); ?>">
                            <i class="fas fa-history me-1"></i> Historial
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="loadingSpinner">
        <div class="spinner-border-custom"></div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Errores de validación:</strong>
                    <ul class="mb-0 mt-2">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-credit-card me-2"></i>Pasarela CyberSource</h5>
                    <p class="mb-0">Sistema completo de pagos con 3D Secure 2.2.0</p>
                    <p class="text-muted small">Replicado desde ociann-legal</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6>Enlaces Útiles</h6>
                    <p class="mb-1">
                        <a href="https://developer.cybersource.com/" target="_blank">
                            <i class="fas fa-book me-1"></i> Documentación CyberSource
                        </a>
                    </p>
                    <p class="mb-1">
                        <a href="https://laravel.com/docs" target="_blank">
                            <i class="fab fa-laravel me-1"></i> Laravel Docs
                        </a>
                    </p>
                    <p class="text-muted small mt-3">
                        © <?php echo e(date('Y')); ?> - Desarrollado con Laravel <?php echo e(app()->version()); ?>

                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts adicionales de la página -->
    <?php echo $__env->yieldContent('scripts'); ?>

    <!-- Global Scripts -->
    <script>
        // Helper para mostrar/ocultar spinner
        window.showSpinner = function() {
            document.getElementById('loadingSpinner').classList.add('active');
        };

        window.hideSpinner = function() {
            document.getElementById('loadingSpinner').classList.remove('active');
        };

        // Auto-ocultar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });

        // CSRF Token para AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</body>
</html>

<?php /**PATH C:\xampp\htdocs\pasarelalaravel\resources\views/template/app.blade.php ENDPATH**/ ?>