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

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Payment Form -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Información de Pago
                    </h5>
                </div>
                <div class="card-body">
                    <form id="payment-form" action="{{ route('payment.process') }}" method="POST">
                        @csrf

                        <!-- Amount Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">
                                    <i class="fas fa-dollar-sign text-success"></i>
                                    Monto
                                </label>
                                <input type="number" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       id="amount" 
                                       name="amount" 
                                       value="{{ old('amount') }}" 
                                       placeholder="100.00"
                                       step="0.01" 
                                       min="0.01" 
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="currency" class="form-label">
                                    <i class="fas fa-coins text-warning"></i>
                                    Moneda
                                </label>
                                <select class="form-select @error('currency') is-invalid @enderror" 
                                        id="currency" 
                                        name="currency" 
                                        required>
                                    <option value="">Seleccione una moneda</option>
                                    <option value="USD" {{ old('currency') == 'USD' ? 'selected' : '' }}>USD - Dólar Estadounidense</option>
                                    <option value="CRC" {{ old('currency') == 'CRC' ? 'selected' : '' }}>CRC - Colones Costarricenses</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                       class="form-control @error('card_number') is-invalid @enderror" 
                                       id="card_number" 
                                       name="card_number" 
                                       value="{{ old('card_number') }}" 
                                       placeholder="1234 5678 9012 3456"
                                       maxlength="19"
                                       required>
                                @error('card_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expiration_month" class="form-label">Mes de Expiración</label>
                                <select class="form-select @error('expiration_month') is-invalid @enderror" 
                                        id="expiration_month" 
                                        name="expiration_month" 
                                        required>
                                    <option value="">MM</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" 
                                                {{ old('expiration_month') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endfor
                                </select>
                                @error('expiration_month')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="expiration_year" class="form-label">Año de Expiración</label>
                                <select class="form-select @error('expiration_year') is-invalid @enderror" 
                                        id="expiration_year" 
                                        name="expiration_year" 
                                        required>
                                    <option value="">YYYY</option>
                                    @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                                        <option value="{{ $i }}" {{ old('expiration_year') == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                                @error('expiration_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                <select class="form-select @error('card_type') is-invalid @enderror" 
                                        id="card_type" 
                                        name="card_type" 
                                        required>
                                    <option value="">Seleccione el tipo de tarjeta</option>
                                    <option value="visa" {{ old('card_type') == 'visa' ? 'selected' : '' }}>
                                        Visa
                                    </option>
                                    <option value="mastercard" {{ old('card_type') == 'mastercard' ? 'selected' : '' }}>
                                        Mastercard
                                    </option>
                                    <option value="american express" {{ old('card_type') == 'american express' ? 'selected' : '' }}>
                                        American Express
                                    </option>
                                </select>
                                @error('card_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name') }}" 
                                       placeholder="Nombre"
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Apellidos</label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name') }}" 
                                       placeholder="Apellidos"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="correo@ejemplo.com"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Teléfono (Opcional)</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone') }}" 
                                       placeholder="+506 1234-5678">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="company" class="form-label">Empresa (Opcional)</label>
                                <input type="text" 
                                       class="form-control @error('company') is-invalid @enderror" 
                                       id="company" 
                                       name="company" 
                                       value="{{ old('company') }}">
                                @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="address1" class="form-label">Dirección</label>
                                <input type="text" 
                                       class="form-control @error('address1') is-invalid @enderror" 
                                       id="address1" 
                                       name="address1" 
                                       value="{{ old('address1') }}" 
                                       required>
                                @error('address1')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="city" class="form-label">Ciudad</label>
                                <input type="text" 
                                       class="form-control @error('city') is-invalid @enderror" 
                                       id="city" 
                                       name="city" 
                                       value="{{ old('city') }}" 
                                       required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="state" class="form-label">Provincia/Estado (2 letras)</label>
                                <input type="text" 
                                       class="form-control text-uppercase @error('state') is-invalid @enderror" 
                                       id="state" 
                                       name="state" 
                                       value="{{ old('state') }}" 
                                       placeholder="SJ"
                                       maxlength="2"
                                       minlength="2"
                                       pattern="[A-Za-z]{2}"
                                       title="Debe ser exactamente 2 letras (ej: SJ, CA, NY)"
                                       style="text-transform: uppercase;"
                                       required>
                                <small class="text-muted">Ej: SJ (San José), CA (California)</small>
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="postal_code" class="form-label">Código Postal</label>
                                <input type="text" 
                                       class="form-control @error('postal_code') is-invalid @enderror" 
                                       id="postal_code" 
                                       name="postal_code" 
                                       value="{{ old('postal_code') }}" 
                                       required>
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="country" class="form-label">País</label>
                                <select class="form-select @error('country') is-invalid @enderror" 
                                        id="country" 
                                        name="country" 
                                        required>
                                    <option value="">Seleccione un país</option>
                                    <option value="CR" {{ old('country') == 'CR' ? 'selected' : '' }}>Costa Rica</option>
                                    <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>Estados Unidos</option>
                                    <option value="MX" {{ old('country') == 'MX' ? 'selected' : '' }}>México</option>
                                    <option value="GT" {{ old('country') == 'GT' ? 'selected' : '' }}>Guatemala</option>
                                    <option value="SV" {{ old('country') == 'SV' ? 'selected' : '' }}>El Salvador</option>
                                    <option value="HN" {{ old('country') == 'HN' ? 'selected' : '' }}>Honduras</option>
                                    <option value="NI" {{ old('country') == 'NI' ? 'selected' : '' }}>Nicaragua</option>
                                    <option value="PA" {{ old('country') == 'PA' ? 'selected' : '' }}>Panamá</option>
                                    <option value="CO" {{ old('country') == 'CO' ? 'selected' : '' }}>Colombia</option>
                                    <option value="PE" {{ old('country') == 'PE' ? 'selected' : '' }}>Perú</option>
                                    <option value="EC" {{ old('country') == 'EC' ? 'selected' : '' }}>Ecuador</option>
                                    <option value="VE" {{ old('country') == 'VE' ? 'selected' : '' }}>Venezuela</option>
                                    <option value="AR" {{ old('country') == 'AR' ? 'selected' : '' }}>Argentina</option>
                                    <option value="CL" {{ old('country') == 'CL' ? 'selected' : '' }}>Chile</option>
                                    <option value="BR" {{ old('country') == 'BR' ? 'selected' : '' }}>Brasil</option>
                                    <option value="ES" {{ old('country') == 'ES' ? 'selected' : '' }}>España</option>
                                    <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>Canadá</option>
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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

