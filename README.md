# 💳 Pasarela de Pagos CyberSource

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![3D Secure](https://img.shields.io/badge/3DS-2.2.0-green.svg)](https://www.emvco.com/emv-technologies/3d-secure/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Sistema completo de pasarela de pagos con **CyberSource 3D Secure 2.2.0**, listo para integrar en cualquier proyecto Laravel.

---

## 🎯 Características

- ✅ **3D Secure 2.2.0** - Última versión del protocolo de autenticación
- ✅ **Flujo Frictionless** (Y,Y) - Autenticación sin OTP para bajo riesgo
- ✅ **Flujo Challenge** (Y,C) - Autenticación con OTP para alto riesgo
- ✅ **Device Data Collection** - Fingerprinting del dispositivo
- ✅ **Tokenización TMS** - Almacenamiento seguro de tarjetas
- ✅ **Modo Debug** - Ejecución paso a paso para desarrollo
- ✅ **Soporte Multicurrency** - USD, CRC y más
- ✅ **Mastercard UCAF** - Soporte completo para Mastercard
- ✅ **Logging Completo** - Trazabilidad de todas las transacciones
- ✅ **Sin Autenticación** - Funciona como checkout independiente

---

## 🚀 Inicio Rápido

### 1️⃣ **Clonar o Copiar el Proyecto**

```bash
# Opción A: Clonar desde repositorio
git clone https://github.com/tu-usuario/pasarelacybersource.git
cd pasarelacybersource

# Opción B: Copiar archivos a tu proyecto existente
# (Ver sección "Integración en Proyecto Existente")
```

### 2️⃣ **Instalar Dependencias**

```bash
# Backend (PHP/Laravel)
composer install

# Frontend (JavaScript/CSS)
npm install
npm run build
```

### 3️⃣ **Configurar Entorno**

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 4️⃣ **Configurar Base de Datos**

Edita `.env` y configura tu base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Ejecuta las migraciones:

```bash
php artisan migrate
```

### 5️⃣ **Configurar CyberSource**

⚠️ **CRÍTICO**: Edita `.env` con tus credenciales de CyberSource:

```env
# ===== CONFIGURACIÓN DE SESIÓN (CRÍTICO PARA 3DS CHALLENGE) =====
# Para desarrollo local (HTTP)
SESSION_SAME_SITE=null

# Para producción (HTTPS) - CAMBIAR A:
# SESSION_SAME_SITE=none
# SESSION_SECURE_COOKIE=true
# ================================================================

# Credenciales CyberSource
CYBERSOURCE_MERCHANT_ID=tu_merchant_id
CYBERSOURCE_API_KEY=tu_api_key
CYBERSOURCE_API_SECRET=tu_api_secret

# Entorno (test o producción)
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com

# URLs de Callback
CYBERSOURCE_CHALLENGE_RETURN_URL="${APP_URL}/payment/challenge/callback"
CYBERSOURCE_SUCCESS_URL="${APP_URL}/payment/success"
CYBERSOURCE_FAILURE_URL="${APP_URL}/payment/failed"

# 3D Secure
CYBERSOURCE_3DS_ENABLED=true
CYBERSOURCE_3DS_VERSION=2.2.0

# Configuración de Pagos
CYBERSOURCE_DEFAULT_CURRENCY=USD
CYBERSOURCE_CAPTURE_ON_AUTH=true
CYBERSOURCE_ALLOWED_CURRENCIES=USD,CRC
```

> 📖 **Nota Importante**: `SESSION_SAME_SITE=null` es **esencial** para que el challenge 3DS funcione correctamente. Sin esto, las cookies se bloquearán en el iframe del banco. Ver `CONFIGURACION_CHALLENGE.md` para más detalles.

### 6️⃣ **Iniciar Servidor**

```bash
# Desarrollo
php artisan serve

# Acceder a:
# http://localhost:8000/payment/checkout  (Checkout)
# http://localhost:8000/payment/debug     (Modo Debug)
```

---

## 📁 Estructura del Proyecto

```
pasarelacybersource/
├── app/
│   ├── Http/Controllers/Payment/
│   │   ├── CheckoutController.php      # Flujo de checkout principal
│   │   ├── ChallengeController.php     # Manejo de 3DS challenge (OTP)
│   │   └── PaymentController.php       # Páginas de resultado
│   ├── Models/
│   │   ├── Payment.php                 # Modelo de pagos
│   │   ├── PaymentInstrument.php       # Tokenización de tarjetas
│   │   └── PaymentTransaction.php      # Historial de transacciones
│   └── Services/Payment/
│       ├── CyberSourceService.php      # Lógica principal de integración
│       └── HMACGenerator.php           # Firmas HMAC para autenticación
├── config/
│   └── cybersource.php                 # Configuración de CyberSource
├── database/migrations/
│   ├── 2025_10_29_000155_create_payments_table.php
│   ├── 2025_10_29_141844_create_payment_instruments_table.php
│   ├── 2025_10_29_141847_create_payment_transactions_table.php
│   └── 0001_01_01_000003_create_sessions_table.php
├── resources/views/
│   ├── pages/payment/
│   │   ├── checkout.blade.php          # Formulario de pago
│   │   ├── challenge.blade.php         # Página de challenge 3DS
│   │   ├── challenge-return.blade.php  # Callback interno del challenge
│   │   ├── device-collection.blade.php # Recolección de datos del dispositivo
│   │   ├── success.blade.php           # Pago exitoso
│   │   ├── failed.blade.php            # Pago fallido
│   │   ├── history.blade.php           # Historial de pagos
│   │   └── debug.blade.php             # Modo debug paso a paso
│   └── modules/payment/
│       ├── checkout-form.blade.php     # Formulario de checkout
│       ├── challenge-content.blade.php # Contenido del iframe de challenge
│       └── debug-content.blade.php     # Interfaz de debug
└── routes/
    └── web.php                         # Rutas de la pasarela
```

---

## 🔧 Endpoints de la API

### **Públicos** (Sin autenticación):
```
POST /payment/challenge/callback    → Callback del challenge 3DS (CardinalCommerce)
```

### **Checkout** (Opcional autenticación):
```
GET  /payment/checkout              → Formulario de pago
POST /payment/process               → Iniciar proceso de pago
POST /payment/continue-after-collection → Continuar después de device collection
GET  /payment/processing            → Página de procesamiento
GET  /payment/success/{payment}     → Pago exitoso
GET  /payment/failed                → Pago fallido
GET  /payment/history               → Historial de pagos
GET  /payment/show/{payment}        → Detalle de un pago
POST /payment/challenge/authorize   → Autorizar después de challenge (JSON)
```

### **Modo Debug** (Desarrollo):
```
GET  /payment/debug                 → Interfaz de debug
POST /payment/debug/save-form       → Guardar formulario en sesión
POST /payment/debug/step1           → PASO 1: Crear Instrument Identifier
POST /payment/debug/step2           → PASO 2: Crear Payment Instrument
POST /payment/debug/step3           → PASO 3: Setup 3D Secure
POST /payment/debug/step4           → PASO 4: Check Enrollment
POST /payment/debug/step5           → PASO 5: Authorization (Frictionless)
POST /payment/debug/step5_5a        → PASO 5.5A: Validation (Challenge)
POST /payment/debug/step5_5b        → PASO 5.5B: Authorization (Challenge)
```

---

## 🔄 Flujos de Pago

### **Flujo Frictionless (Y,Y)** - Sin OTP

```
1. Cliente ingresa datos de pago
2. PASO 1: Crear Instrument Identifier
3. PASO 2: Crear Payment Instrument (tokenización)
4. PASO 3: Setup 3D Secure
5. Device Collection (iframe invisible, 1-2 segundos)
6. PASO 4: Check Enrollment → Resultado: Y,Y (inscrito, autenticado)
7. PASO 5: Authorization directa
8. Guardar pago en base de datos
9. ✅ Redirección a Success
```

**Características**:
- ⚡ Rápido (2-3 segundos total)
- 🔒 Liability Shift completo
- ✅ Sin fricción para el usuario
- 📊 Ideal para transacciones de bajo riesgo

### **Flujo Challenge (Y,C)** - Con OTP

```
1. Cliente ingresa datos de pago
2. PASO 1-4: Setup completo + Device Collection
3. Check Enrollment → Resultado: Y,C (inscrito, requiere challenge)
4. Mostrar iframe con formulario del banco
5. Cliente ingresa OTP o completa autenticación
6. Callback recibe respuesta del banco (TransactionId)
7. PASO 5.5A: Validation Service
8. PASO 5.5B: Authorization con datos validados
9. Guardar pago en base de datos
10. ✅ Redirección a Success
```

**Características**:
- 🔐 Máxima seguridad (OTP del banco emisor)
- 🔒 Liability Shift completo
- 📱 Challenge en iframe (sin redirección)
- 📊 Obligatorio para Mastercard, común en alto riesgo

---

## 🗄️ Base de Datos

### **Tabla: payments**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | ID único del pago |
| `user_id` | BIGINT | ID del usuario (opcional) |
| `amount` | DECIMAL(10,2) | Monto del pago |
| `currency` | VARCHAR(3) | Moneda (USD, CRC, etc.) |
| `status` | VARCHAR(255) | Estado del pago |
| `transaction_id` | VARCHAR(255) | ID de transacción de CyberSource |
| `authorization_code` | VARCHAR(255) | Código de autorización |
| `threeds_version` | VARCHAR(255) | Versión de 3DS (2.2.0) |
| `threeds_eci` | VARCHAR(255) | ECI (05 para Visa, 02 para Mastercard) |
| `threeds_cavv` | VARCHAR(255) | CAVV (Visa/Amex) |
| `threeds_xid` | VARCHAR(255) | XID de autenticación |
| `threeds_authentication_status` | VARCHAR(255) | Estado de autenticación 3DS |
| `flow_type` | VARCHAR(255) | Tipo de flujo (frictionless/challenge) |
| `liability_shift` | BOOLEAN | Transferencia de responsabilidad |
| `card_last_four` | VARCHAR(4) | Últimos 4 dígitos de la tarjeta |
| `card_type` | VARCHAR(255) | Tipo de tarjeta (visa/mastercard/amex) |
| `enrollment_data` | JSON | Datos completos del enrollment |
| `metadata` | JSON | Metadatos adicionales |
| `processed_at` | TIMESTAMP | Fecha de procesamiento |
| `created_at` | TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | Fecha de actualización |

---

## 💡 Recomendaciones de Uso

### **1. Vincular Pagos a Usuarios**

Si tu aplicación tiene usuarios autenticados, puedes vincular los pagos automáticamente:

```php
// En CheckoutController.php, método processPayment()

// Opción A: Vincular automáticamente si hay usuario logueado
$data['user_id'] = auth()->id(); // Agregar antes de session(['payment_data' => $data])

// Opción B: Pasar user_id desde el formulario
$validator = Validator::make($request->all(), [
    // ... campos existentes ...
    'user_id' => 'nullable|exists:users,id', // Agregar esta validación
]);
```

Luego en el modelo `Payment.php`:

```php
// Relación con Usuario
public function user()
{
    return $this->belongsTo(User::class);
}
```

### **2. Personalizar Monedas Permitidas**

Edita `config/cybersource.php`:

```php
'allowed_currencies' => [
    'USD', // Dólar estadounidense
    'CRC', // Colón costarricense
    'EUR', // Euro
    'MXN', // Peso mexicano
    // Agregar más según tu país
],
```

### **3. Cambiar Middleware de Autenticación**

Por defecto, las rutas NO requieren autenticación. Para protegerlas:

```php
// En routes/web.php
Route::prefix('payment')->middleware(['auth'])->name('payment.')->group(function () {
    // ... rutas existentes ...
});
```

### **4. Enviar Notificaciones por Email**

Crea un listener para enviar emails después de un pago exitoso:

```bash
php artisan make:listener SendPaymentConfirmation
```

```php
// App\Listeners\SendPaymentConfirmation.php
public function handle(PaymentCompleted $event)
{
    Mail::to($event->payment->email)->send(new PaymentReceipt($event->payment));
}
```

### **5. Agregar Webhooks de CyberSource**

Para recibir notificaciones de CyberSource sobre cambios de estado:

```php
// En routes/web.php
Route::post('/webhooks/cybersource', [WebhookController::class, 'handle'])
    ->name('webhooks.cybersource')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

### **6. Modo Debug vs Producción**

**Desarrollo**:
```env
APP_DEBUG=true
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com
SESSION_SAME_SITE=null
```

**Producción**:
```env
APP_DEBUG=false
CYBERSOURCE_BASE_URL=https://api.cybersource.com
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

---

## 🎯 Integración en Proyecto Existente

### **Opción 1: Proyecto Completo**

Si quieres usar este proyecto como base:

```bash
# Clonar el proyecto
git clone https://tu-repo/pasarelacybersource.git mi-tienda
cd mi-tienda

# Instalar dependencias
composer install
npm install

# Configurar
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build

# Iniciar
php artisan serve
```

### **Opción 2: Integrar en Proyecto Laravel Existente**

Si ya tienes un proyecto Laravel y solo quieres agregar la pasarela:

**Paso 1: Copiar archivos necesarios**

```bash
# Desde la raíz de tu proyecto Laravel existente
cd /ruta/a/tu/proyecto
```

**Archivos a copiar desde `pasarelacybersource/`**:

1. **Controladores**:
   ```bash
   cp -r pasarelacybersource/app/Http/Controllers/Payment/ app/Http/Controllers/
   ```

2. **Servicios**:
   ```bash
   mkdir -p app/Services
   cp -r pasarelacybersource/app/Services/Payment/ app/Services/
   ```

3. **Modelos**:
   ```bash
   cp pasarelacybersource/app/Models/Payment.php app/Models/
   cp pasarelacybersource/app/Models/PaymentInstrument.php app/Models/
   cp pasarelacybersource/app/Models/PaymentTransaction.php app/Models/
   ```

4. **Migraciones**:
   ```bash
   cp pasarelacybersource/database/migrations/*_create_payments_*.php database/migrations/
   cp pasarelacybersource/database/migrations/*_create_sessions_*.php database/migrations/
   ```

5. **Vistas**:
   ```bash
   mkdir -p resources/views/pages/payment
   mkdir -p resources/views/modules/payment
   cp -r pasarelacybersource/resources/views/pages/payment/ resources/views/pages/
   cp -r pasarelacybersource/resources/views/modules/payment/ resources/views/modules/
   ```

6. **Configuración**:
   ```bash
   cp pasarelacybersource/config/cybersource.php config/
   ```

**Paso 2: Agregar rutas**

Opción A - Archivo separado (recomendado):
```bash
# Crear archivo de rutas de pago
cp pasarelacybersource/routes/web.php routes/payment.php
```

Luego en `routes/web.php` de tu proyecto, agrega al final:
```php
// Rutas de Pasarela CyberSource
require __DIR__.'/payment.php';
```

Opción B - Mismo archivo:
```php
// Copiar las rutas de payment desde pasarelacybersource/routes/web.php
// y pegarlas al final de tu routes/web.php
```

**Paso 3: Configurar**

Agrega al final de tu `.env`:
```env
# ===== PASARELA CYBERSOURCE =====
SESSION_SAME_SITE=null  # Para desarrollo
CYBERSOURCE_MERCHANT_ID=tu_merchant_id
CYBERSOURCE_API_KEY=tu_api_key
CYBERSOURCE_API_SECRET=tu_api_secret
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com
CYBERSOURCE_CHALLENGE_RETURN_URL="${APP_URL}/payment/challenge/callback"
CYBERSOURCE_SUCCESS_URL="${APP_URL}/payment/success"
CYBERSOURCE_FAILURE_URL="${APP_URL}/payment/failed"
CYBERSOURCE_3DS_ENABLED=true
CYBERSOURCE_3DS_VERSION=2.2.0
CYBERSOURCE_DEFAULT_CURRENCY=USD
CYBERSOURCE_CAPTURE_ON_AUTH=true
CYBERSOURCE_ALLOWED_CURRENCIES=USD,CRC
```

**Paso 4: Ejecutar migraciones**

```bash
php artisan migrate
```

**Paso 5: Limpiar cache**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

**Paso 6: Verificar instalación**

```bash
# Ver rutas de pago
php artisan route:list | grep payment

# Deberías ver:
# GET /payment/checkout
# POST /payment/process
# etc.
```

**Paso 7: Probar**

Inicia el servidor y accede a:
- Checkout: `http://localhost:8000/payment/checkout`
- Debug: `http://localhost:8000/payment/debug`

---

## 🧪 Tarjetas de Prueba

### **Visa** (Frictionless - Sin OTP):
```
Número: 4000000000002701
Expiración: 01/2028
CVV: 123
Tipo: visa
Resultado: Y,Y (frictionless)
```

### **Mastercard** (Challenge - Con OTP):
```
Número: 5200000000002151
Expiración: 01/2028
CVV: 123
Tipo: mastercard
Resultado: Y,C (challenge requerido)
```

### **American Express** (Frictionless):
```
Número: 340000000002708
Expiración: 01/2028
CVV: 1234
Tipo: american express
Resultado: Y,Y (frictionless)
```

> 💡 **Nota**: En el challenge de prueba de CyberSource, usa cualquier OTP o código que te solicite el banco simulado.

---

## 🛠️ Comandos Útiles

```bash
# Limpiar cache de configuración
php artisan config:clear

# Limpiar cache de rutas
php artisan route:clear

# Limpiar cache de vistas
php artisan view:clear

# Limpiar TODO (combo completo)
php artisan optimize:clear

# Ver rutas disponibles
php artisan route:list

# Ver rutas de pago específicamente
php artisan route:list | grep payment

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Compilar assets (frontend)
npm run dev      # Desarrollo con watch
npm run build    # Producción (optimizado)
npm run watch    # Watch mode continuo

# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status
```

---

## 📚 Documentación Adicional

- **`CONFIGURACION_CHALLENGE.md`** - ⚠️ **CRÍTICO**: Configuración de sesión para 3DS Challenge
- **Logs**: `storage/logs/laravel.log` - Todos los pasos del flujo están logueados con emojis
- **CyberSource Docs**: [developer.cybersource.com](https://developer.cybersource.com)
- **3D Secure Spec**: [EMVCo 3DS 2.2.0](https://www.emvco.com/emv-technologies/3d-secure/)

---

## 🛠️ Tecnologías

- **Laravel** 11.x - Framework PHP moderno
- **PHP** 8.1+ - Lenguaje de programación
- **CyberSource REST API** - Gateway de pagos
- **3D Secure** 2.2.0 - Protocolo de autenticación
- **MySQL/MariaDB** - Base de datos relacional
- **Bootstrap 5** - Framework CSS responsivo
- **JavaScript Vanilla** - Sin dependencias frontend pesadas
- **CardinalCommerce** - Proveedor de autenticación 3DS

---

## 📝 Checklist de Producción

Antes de lanzar a producción, verifica:

- [ ] Cambiar `CYBERSOURCE_BASE_URL` a `https://api.cybersource.com`
- [ ] Configurar `SESSION_SAME_SITE=none` y `SESSION_SECURE_COOKIE=true`
- [ ] Desactivar debug: `APP_DEBUG=false`
- [ ] Actualizar credenciales a las de producción de CyberSource
- [ ] Habilitar HTTPS en el servidor (certificado SSL válido)
- [ ] Configurar emails de notificación para pagos
- [ ] Probar flujos completos (frictionless y challenge)
- [ ] Revisar logs de errores en `storage/logs/`
- [ ] Configurar backups automáticos de base de datos
- [ ] Implementar monitoreo (Sentry, NewRelic, etc.)
- [ ] Configurar rate limiting en rutas públicas
- [ ] Verificar que `.env` no esté en el repositorio
- [ ] Documentar proceso de deployment
- [ ] Crear plan de rollback

---

## 🔐 Seguridad

- ✅ **No se almacenan números de tarjeta completos** - Solo últimos 4 dígitos
- ✅ **Tokenización TMS** - Las tarjetas se almacenan encriptadas en CyberSource
- ✅ **3D Secure obligatorio** - Transferencia de responsabilidad al banco emisor
- ✅ **HMAC Signatures** - Todas las peticiones a CyberSource están firmadas
- ✅ **HTTPS requerido en producción** - Para `SESSION_SAME_SITE=none`
- ✅ **CSRF Protection** - Protección contra ataques cross-site
- ✅ **Input Validation** - Validación estricta de todos los campos
- ✅ **SQL Injection Protection** - Uso de Eloquent ORM
- ✅ **XSS Protection** - Blade escapa automáticamente el output

---

## 🤝 Soporte

Para dudas o problemas:

1. **Problemas de Challenge 3DS**: Revisa `CONFIGURACION_CHALLENGE.md`
2. **Debugging**: Consulta `storage/logs/laravel.log` (logs con emojis para fácil búsqueda)
3. **Errores de CyberSource**: Revisa la [documentación oficial](https://developer.cybersource.com)
4. **Issues del proyecto**: Abre un issue en el repositorio con logs relevantes

### **Logs Útiles para Debugging**

Busca en `storage/logs/laravel.log` por estos emojis:

- `🚀` - Inicio de operación
- `✅` - Operación exitosa
- `❌` - Error
- `🔍` - Debugging/inspección
- `📋` - Datos recibidos
- `📤` - Datos enviados
- `🔔` - Callback recibido
- `🔑` - AuthenticationTransactionId
- `🎉` - Pago completado

---

## 📄 Licencia

Este proyecto es **privado y propietario**. Todos los derechos reservados.

---

## 👨‍💻 Autor

**Miguel Segura Alvarado**

Sistema de pagos profesional desarrollado con CyberSource 3D Secure 2.2.0.

---

**Versión:** 2.0.0  
**Última Actualización:** 31 de Octubre de 2025  
**Estado:** ✅ **PRODUCCIÓN READY**
