<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-body text-center py-4">
                    <i class="fas fa-lock text-success fa-3x mb-3"></i>
                    <h2 class="mb-2">Pago Seguro</h2>
                    <p class="text-muted mb-0">
                        Procesado por CyberSource con autenticación 3D Secure
                    </p>
                </div>
            </div>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong>
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Información de Pago
                    </h5>
                </div>
                <div class="card-body">
                    <form id="payment-form" action="<?php echo e(route('payment.process')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <!-- Amount Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">
                                    <i class="fas fa-dollar-sign text-success"></i>
                                    Monto
                                </label>
                                <input type="number" 
                                       class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="amount" 
                                       name="amount" 
                                       value="<?php echo e(old('amount')); ?>" 
                                       placeholder="100.00"
                                       step="0.01" 
                                       min="0.01" 
                                       required>
                                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label for="currency" class="form-label">
                                    <i class="fas fa-coins text-warning"></i>
                                    Moneda
                                </label>
                                <select class="form-select <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="currency" 
                                        name="currency" 
                                        required>
                                    <option value="">Seleccione una moneda</option>
                                    <option value="USD" <?php echo e(old('currency') == 'USD' ? 'selected' : ''); ?>>USD - Dólar Estadounidense</option>
                                    <option value="CRC" <?php echo e(old('currency') == 'CRC' ? 'selected' : ''); ?>>CRC - Colones Costarricenses</option>
                                </select>
                                <?php $__errorArgs = ['currency'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Card Information -->
                        <h6 class="mb-3">
                            <i class="fas fa-credit-card text-primary"></i>
                            Información de la Tarjeta
                        </h6>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="card_number" class="form-label">Número de Tarjeta</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['card_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="card_number" 
                                       name="card_number" 
                                       value="<?php echo e(old('card_number')); ?>" 
                                       placeholder="1234 5678 9012 3456"
                                       maxlength="19"
                                       required>
                                <?php $__errorArgs = ['card_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expiration_month" class="form-label">Mes de Expiración</label>
                                <select class="form-select <?php $__errorArgs = ['expiration_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="expiration_month" 
                                        name="expiration_month" 
                                        required>
                                    <option value="">MM</option>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo e(str_pad($i, 2, '0', STR_PAD_LEFT)); ?>" 
                                                <?php echo e(old('expiration_month') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : ''); ?>>
                                            <?php echo e(str_pad($i, 2, '0', STR_PAD_LEFT)); ?>

                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <?php $__errorArgs = ['expiration_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label for="expiration_year" class="form-label">Año de Expiración</label>
                                <select class="form-select <?php $__errorArgs = ['expiration_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="expiration_year" 
                                        name="expiration_year" 
                                        required>
                                    <option value="">YYYY</option>
                                    <?php for($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                        <option value="<?php echo e($i); ?>" <?php echo e(old('expiration_year') == $i ? 'selected' : ''); ?>>
                                            <?php echo e($i); ?>

                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <?php $__errorArgs = ['expiration_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <!-- CVV Note -->
                        <div class="alert alert-info mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <small><strong>Nota:</strong> Con 3D Secure 2.2.0, el CVV no es requerido. La autenticación se realiza directamente con su banco.</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="card_type" class="form-label">Tipo de Tarjeta</label>
                                <select class="form-select <?php $__errorArgs = ['card_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="card_type" 
                                        name="card_type" 
                                        required>
                                    <option value="">Seleccione el tipo de tarjeta</option>
                                    <option value="visa" <?php echo e(old('card_type') == 'visa' ? 'selected' : ''); ?>>
                                        Visa
                                    </option>
                                    <option value="mastercard" <?php echo e(old('card_type') == 'mastercard' ? 'selected' : ''); ?>>
                                        Mastercard
                                    </option>
                                    <option value="american express" <?php echo e(old('card_type') == 'american express' ? 'selected' : ''); ?>>
                                        American Express
                                    </option>
                                </select>
                                <?php $__errorArgs = ['card_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Billing Information -->
                        <h6 class="mb-3">
                            <i class="fas fa-user text-info"></i>
                            Información de Facturación
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Nombre</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo e(old('first_name')); ?>" 
                                       placeholder="Nombre"
                                       required>
                                <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Apellidos</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo e(old('last_name')); ?>" 
                                       placeholder="Apellidos"
                                       required>
                                <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo e(old('email')); ?>" 
                                       placeholder="correo@ejemplo.com"
                                       required>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Teléfono (Opcional)</label>
                                <input type="tel" 
                                       class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo e(old('phone')); ?>" 
                                       placeholder="+506 1234-5678">
                                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="company" class="form-label">Empresa (Opcional)</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['company'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="company" 
                                       name="company" 
                                       value="<?php echo e(old('company')); ?>">
                                <?php $__errorArgs = ['company'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="address1" class="form-label">Dirección</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['address1'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="address1" 
                                       name="address1" 
                                       value="<?php echo e(old('address1')); ?>" 
                                       required>
                                <?php $__errorArgs = ['address1'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">Ciudad</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="city" 
                                       name="city" 
                                       value="<?php echo e(old('city')); ?>" 
                                       required>
                                <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">Provincia/Estado (2 letras)</label>
                                <input type="text" 
                                       class="form-control text-uppercase <?php $__errorArgs = ['state'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="state" 
                                       name="state" 
                                       value="<?php echo e(old('state')); ?>" 
                                       placeholder="SJ"
                                       maxlength="2"
                                       minlength="2"
                                       pattern="[A-Za-z]{2}"
                                       title="Debe ser exactamente 2 letras (ej: SJ, CA, NY)"
                                       style="text-transform: uppercase;"
                                       required>
                                <small class="text-muted">Ej: SJ (San José), CA (California)</small>
                                <?php $__errorArgs = ['state'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Código Postal</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['postal_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="postal_code" 
                                       name="postal_code" 
                                       value="<?php echo e(old('postal_code')); ?>" 
                                       required>
                                <?php $__errorArgs = ['postal_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="country" class="form-label">País</label>
                                <select class="form-select <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="country" 
                                        name="country" 
                                        required>
                                    <option value="">Seleccione un país</option>
                                    <option value="CR" <?php echo e(old('country') == 'CR' ? 'selected' : ''); ?>>Costa Rica</option>
                                    <option value="US" <?php echo e(old('country') == 'US' ? 'selected' : ''); ?>>Estados Unidos</option>
                                    <option value="MX" <?php echo e(old('country') == 'MX' ? 'selected' : ''); ?>>México</option>
                                    <option value="GT" <?php echo e(old('country') == 'GT' ? 'selected' : ''); ?>>Guatemala</option>
                                    <option value="SV" <?php echo e(old('country') == 'SV' ? 'selected' : ''); ?>>El Salvador</option>
                                    <option value="HN" <?php echo e(old('country') == 'HN' ? 'selected' : ''); ?>>Honduras</option>
                                    <option value="NI" <?php echo e(old('country') == 'NI' ? 'selected' : ''); ?>>Nicaragua</option>
                                    <option value="PA" <?php echo e(old('country') == 'PA' ? 'selected' : ''); ?>>Panamá</option>
                                    <option value="CO" <?php echo e(old('country') == 'CO' ? 'selected' : ''); ?>>Colombia</option>
                                    <option value="PE" <?php echo e(old('country') == 'PE' ? 'selected' : ''); ?>>Perú</option>
                                    <option value="EC" <?php echo e(old('country') == 'EC' ? 'selected' : ''); ?>>Ecuador</option>
                                    <option value="VE" <?php echo e(old('country') == 'VE' ? 'selected' : ''); ?>>Venezuela</option>
                                    <option value="AR" <?php echo e(old('country') == 'AR' ? 'selected' : ''); ?>>Argentina</option>
                                    <option value="CL" <?php echo e(old('country') == 'CL' ? 'selected' : ''); ?>>Chile</option>
                                    <option value="BR" <?php echo e(old('country') == 'BR' ? 'selected' : ''); ?>>Brasil</option>
                                    <option value="ES" <?php echo e(old('country') == 'ES' ? 'selected' : ''); ?>>España</option>
                                    <option value="CA" <?php echo e(old('country') == 'CA' ? 'selected' : ''); ?>>Canadá</option>
                                </select>
                                <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                <i class="fas fa-lock me-2"></i>
                                Pagar Ahora
                            </button>
                            <a href="/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Cancelar
                            </a>
                        </div>

                        <!-- Security Notice -->
                        <div class="alert alert-info mt-4" role="alert">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Pago Seguro:</strong> Su información está protegida con encriptación SSL de 256 bits y autenticación 3D Secure.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/modules/payment/checkout-form.blade.php ENDPATH**/ ?>