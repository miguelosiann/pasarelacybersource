# 💳 Pasarela de Pagos CyberSource - Cascarón Reutilizable

## 🎯 Descripción

Implementación completa de pasarela de pagos con **CyberSource 3D Secure 2.2.0** lista para reutilizar en cualquier proyecto Laravel.

### ✅ Características

- ✅ **3D Secure 2.2.0** completo
- ✅ **Flujo Frictionless** (Y,Y) - Sin challenge
- ✅ **Flujo Challenge** (Y,C) - Con OTP
- ✅ **Device Data Collection**
- ✅ **Modo Debug** paso a paso
- ✅ **Historial de pagos**
- ✅ **Validación completa**
- ✅ **Logging detallado**

---

## 🚀 Inicio Rápido

### 1. **Configuración**

⚠️ **CRÍTICO**: Lee `CONFIGURACION_CHALLENGE.md` antes de empezar. El flujo de challenge 3DS requiere configuración especial de sesión.

Copia el archivo `.env.example` a `.env` y configura:

```env
# ===== IMPORTANTE: Configuración de sesión para 3DS Challenge =====
SESSION_SAME_SITE=null   # CRÍTICO para desarrollo local
# Para producción con HTTPS usar: SESSION_SAME_SITE=none
# ================================================================

# CyberSource Configuration
CYBERSOURCE_MERCHANT_ID=tu_merchant_id
CYBERSOURCE_API_KEY=tu_api_key
CYBERSOURCE_API_SECRET=tu_api_secret
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com

# URLs de callback
CYBERSOURCE_CHALLENGE_RETURN_URL="${APP_URL}/payment/challenge/callback"
CYBERSOURCE_SUCCESS_URL="${APP_URL}/payment/success"
CYBERSOURCE_FAILURE_URL="${APP_URL}/payment/failed"

# 3D Secure
CYBERSOURCE_3DS_ENABLED=true
CYBERSOURCE_3DS_VERSION=2.2.0

# Captura automática
CYBERSOURCE_CAPTURE_ON_AUTH=true

# Monedas permitidas
CYBERSOURCE_ALLOWED_CURRENCIES=USD,CRC
```

### 2. **Instalación**

```bash
# Instalar dependencias
composer install

# Generar key de Laravel
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Iniciar servidor
php artisan serve
```

### 3. **Probar**

Visita: `http://localhost:8000/payment/checkout`

---

## 📁 Estructura del Proyecto

```
pasarelalaravel/
├── app/
│   ├── Http/Controllers/Payment/
│   │   ├── CheckoutController.php      # Proceso de checkout
│   │   ├── ChallengeController.php     # Manejo de 3DS challenge
│   │   └── PaymentController.php       # Páginas de resultado
│   ├── Models/
│   │   ├── Payment.php                 # ✅ Campos 3DS corregidos
│   │   ├── PaymentInstrument.php
│   │   └── PaymentTransaction.php
│   └── Services/Payment/
│       ├── CyberSourceService.php      # ✅ Lógica principal actualizada
│       └── HMACGenerator.php           # Generación de firmas
├── database/migrations/
│   ├── 2025_10_29_000155_create_payments_table.php  # ✅ Estructura correcta
│   ├── 2025_10_29_141844_create_payment_instruments_table.php
│   └── 2025_10_29_141847_create_payment_transactions_table.php
├── resources/views/
│   ├── pages/payment/
│   │   ├── checkout.blade.php          # Formulario de pago
│   │   ├── challenge.blade.php         # Página de challenge 3DS
│   │   ├── challenge-return.blade.php  # Callback del challenge
│   │   ├── device-collection.blade.php # Recolección de datos
│   │   ├── success.blade.php           # Pago exitoso
│   │   ├── failed.blade.php            # Pago fallido
│   │   ├── history.blade.php           # Historial
│   │   └── debug.blade.php             # Modo debug
│   └── modules/payment/
│       └── challenge-content.blade.php # Contenido del challenge
└── routes/
    └── web.php                         # Rutas de la pasarela
```

---

## 🔧 Endpoints Disponibles

### **Públicos (sin autenticación):**
```
POST /payment/challenge/callback    # Callback del challenge 3DS
```

### **Con autenticación:**
```
GET  /payment/checkout              # Formulario de checkout
POST /payment/process               # Procesar pago
POST /payment/continue-after-collection  # Continuar después de device collection
GET  /payment/processing            # Página de procesamiento
GET  /payment/success/{payment}     # Pago exitoso
GET  /payment/failed                # Pago fallido
GET  /payment/history               # Historial de pagos
GET  /payment/show/{payment}        # Detalle de pago
POST /payment/challenge/authorize   # Autorizar después de challenge
```

### **Debug (paso a paso):**
```
GET  /payment/debug                 # Página de debug
POST /payment/debug/save-form       # Guardar datos en sesión
POST /payment/debug/step1           # PASO 1: Instrument Identifier
POST /payment/debug/step2           # PASO 2: Payment Instrument
POST /payment/debug/step3           # PASO 3: Setup 3D Secure
POST /payment/debug/step4           # PASO 4: Check Enrollment
POST /payment/debug/step5           # PASO 5: Authorization (Frictionless)
POST /payment/debug/step5_5a        # PASO 5.5A: Validation (Challenge)
POST /payment/debug/step5_5b        # PASO 5.5B: Authorization (Challenge)
```

---

## 🔄 Flujo de Pagos

### **Flujo Frictionless (Y,Y):**

```
1. Checkout → Ingreso de datos
2. PASO 1 → Crear Instrument Identifier
3. PASO 2 → Crear Payment Instrument
4. PASO 3 → Setup 3D Secure
5. Device Collection → Iframe invisible (1-2 segundos)
6. PASO 4 → Check Enrollment → Resultado: Y,Y
7. PASO 5 → Authorization directa
8. ✅ GUARDADO en DB con campos correctos
9. Redirección a Success
```

### **Flujo Challenge (Y,C):**

```
1. Checkout → Ingreso de datos
2. PASO 1 → Crear Instrument Identifier
3. PASO 2 → Crear Payment Instrument
4. PASO 3 → Setup 3D Secure
5. Device Collection → Iframe invisible (1-2 segundos)
6. PASO 4 → Check Enrollment → Resultado: Y,C
7. Challenge → Iframe con formulario de banco
8. Usuario ingresa OTP
9. PASO 5.5A → Validation Service
10. PASO 5.5B → Authorization
11. ✅ GUARDADO en DB con campos correctos
12. Redirección a Success
```

---

## 🗄️ Base de Datos

### **Tabla: payments**

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED,
    amount DECIMAL(10, 2),
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(255),
    transaction_id VARCHAR(255) UNIQUE,
    authorization_code VARCHAR(255),
    
    -- 3D Secure (sin prefijo threeds_)
    cavv VARCHAR(255),          -- ✅ Correcto
    eci VARCHAR(255),           -- ✅ Correcto
    xid VARCHAR(255),           -- ✅ Correcto
    enrollment_data JSON,       -- ✅ Nuevo
    
    flow_type VARCHAR(255),     -- frictionless o challenge
    liability_shift BOOLEAN DEFAULT 0,
    
    card_last_four VARCHAR(4),
    card_type VARCHAR(255),
    metadata JSON,
    error_message TEXT,
    processed_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔍 Diferencias con ociann-legal

| Característica | pasarelalaravel | ociann-legal |
|----------------|-----------------|--------------|
| Campo customer_id | ❌ No incluido | ✅ Incluido |
| Campos 3DS | ✅ cavv, eci, xid | ✅ cavv, eci, xid |
| Autenticación | Opcional | Requerida |
| Middleware | Sin restricciones | Con subscription |
| Uso | Cascarón genérico | Sistema empresarial |

---

## ✅ ¿Qué se Corrigió?

### **Problema Original:**
El challenge 3DS se "pegaba" después de ingresar el OTP y no continuaba con la validación.

### **Causa:**
Los campos de la base de datos usaban prefijo `threeds_*` (threeds_cavv, threeds_eci, etc.) pero el código intentaba guardar sin prefijo (cavv, eci, etc.).

### **Solución:**
1. ✅ Actualizado modelo `Payment.php`
2. ✅ Actualizada migración de `payments`
3. ✅ Actualizado `CyberSourceService.php` (3 métodos)
4. ✅ Recreada base de datos

### **Resultado:**
✅ El flujo de challenge ahora funciona perfectamente
✅ El pago se guarda correctamente en la base de datos
✅ La pasarela está lista para usar como cascarón

---

## 📚 Documentación Adicional

- **`CONFIGURACION_CHALLENGE.md`** - ⚠️ **CRÍTICO**: Configuración necesaria para 3DS Challenge
- `CAMBIOS_APLICADOS.md` - Lista detallada de cambios realizados (si existe)
- `PRUEBAS.md` - Guía completa de pruebas (si existe)
- Logs: `storage/logs/laravel.log`

---

## 🛠️ Tecnologías

- **Laravel 11.x**
- **PHP 8.1+**
- **CyberSource REST API**
- **3D Secure 2.2.0**
- **MySQL/MariaDB**
- **Bootstrap 5**
- **JavaScript (Vanilla)**

---

## 🎯 Uso en Otros Proyectos

### **Opción 1: Copiar completo**
```bash
cp -r pasarelalaravel /ruta/nuevo-proyecto
cd /ruta/nuevo-proyecto
composer install
cp .env.example .env
# Configurar .env
php artisan key:generate
php artisan migrate
```

### **Opción 2: Copiar solo archivos de pago**
```bash
# Copiar desde pasarelalaravel a tu proyecto:
app/Http/Controllers/Payment/
app/Services/Payment/
app/Models/Payment*.php
database/migrations/*_create_payments_*.php
resources/views/pages/payment/
resources/views/modules/payment/
config/cybersource.php
```

---

## 📝 Notas Importantes

- ⚠️ **Producción:** Cambiar `CYBERSOURCE_BASE_URL` a producción
- ⚠️ **Seguridad:** No exponer credenciales en el código
- ⚠️ **Testing:** Siempre probar en sandbox antes de producción
- ✅ **Compatible:** Laravel 10.x y 11.x
- ✅ **3DS 2.2.0:** Última versión del protocolo

---

## 🤝 Soporte

Para problemas o dudas, revisar:
1. `PRUEBAS.md` - Guía de verificación
2. `CAMBIOS_APLICADOS.md` - Detalles técnicos
3. Logs de Laravel: `storage/logs/laravel.log`
4. Documentación de CyberSource

---

## 📄 Licencia

Este es un cascarón reutilizable basado en la implementación de `ociann-legal`.

---

**Versión:** 1.0.0  
**Fecha:** 29 de Octubre de 2025  
**Estado:** ✅ PRODUCCIÓN READY (después de testing)
