# 🔧 Configuración Crítica para 3DS Challenge

## ⚠️ PROBLEMA ACTUAL

El flujo de **3DS Challenge** muestra la página de error dentro del iframe con estos mensajes en consola:

```
Cookie "XSRF-TOKEN" has been rejected because it is in a cross-site context and its "SameSite" is "Lax" or "Strict".
Cookie "pasarela-cybersource-session" has been rejected because it is in a cross-site context and its "SameSite" is "Lax" or "Strict".
```

## 🎯 CAUSA

El **iframe del challenge 3DS** proviene de un dominio externo (CardinalCommerce: `centinelapistag.cardinalcommerce.com`). Cuando el challenge se completa y hace el callback a tu aplicación en `/payment/challenge/callback`, el navegador **rechaza las cookies de sesión** porque:

1. El contexto es **cross-site** (viene de otro dominio)
2. La configuración por defecto de Laravel es `SESSION_SAME_SITE=lax`
3. `SameSite=Lax` **bloquea las cookies** en contextos cross-site

Resultado: **La sesión no está disponible** en el callback → Se pierde el `payment_instrument_id`, `authentication_transaction_id`, y `payment_data` → La página redirige a `/payment/failed`.

---

## ✅ SOLUCIÓN

### **Para Desarrollo Local (HTTP)**

Edita tu archivo `.env` y agrega/modifica:

```env
SESSION_SAME_SITE=null
```

**Explicación**: `null` permite que las cookies se envíen en contextos cross-site durante el desarrollo local.

### **Para Producción (HTTPS)**

En producción con HTTPS, usa:

```env
SESSION_SAME_SITE=none
SESSION_SECURE_COOKIE=true
```

**Explicación**: 
- `SameSite=none` permite cookies cross-site
- `secure=true` es **requerido** por los navegadores cuando usas `SameSite=none`
- Solo funciona con HTTPS (no con HTTP)

---

## 🚀 PASOS PARA APLICAR

1. **Copia `.env.example` a `.env`** (si no lo has hecho):
   ```bash
   cp .env.example .env
   ```

2. **Edita `.env` y agrega**:
   ```env
   SESSION_SAME_SITE=null
   ```

3. **Limpia la configuración en cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Reinicia el servidor**:
   ```bash
   php artisan serve
   ```

5. **Prueba el challenge**:
   - Usa una tarjeta que requiera challenge (ej: `5200000000002235` para Mastercard)
   - Completa el OTP en el iframe
   - Verifica que **NO** aparezca la página de error
   - El pago debe procesarse correctamente

---

## 📋 VERIFICACIÓN

### ✅ **Antes de la configuración** (INCORRECTO):
```
PASO 1: Tokens - Crear (Instrument Identifier)
PASO 2: Tokens - Crear (Payment Instrument)
PASO 3: Configuración de autenticación del pagador (Setup 3DS)
PASO 4: Inscripción de autenticación del pagador (Check Enrollment)
❌ PASO 5: AUTORIZACIÓN INMEDIATA (sin esperar challenge) ← INCORRECTO
```

### ✅ **Después de la configuración** (CORRECTO):
```
PASO 1: Tokens - Crear (Instrument Identifier)
PASO 2: Tokens - Crear (Payment Instrument)
PASO 3: Configuración de autenticación del pagador (Setup 3DS)
PASO 4: Inscripción de autenticación del pagador (Check Enrollment → Y,C)
PASO 4.5: Challenge 3DS (iframe con OTP del banco)
PASO 5.5A: Validation Service (después del challenge)
PASO 5.5B: Authorization (después de validación)
✅ Pago exitoso
```

---

## 🔍 LOGS PARA DIAGNOSTICAR

Si el challenge sigue sin funcionar, revisa `storage/logs/laravel.log`:

### **Buscar estos mensajes**:

```php
// 1. Enrollment debe detectar challenge (Y,C)
'🔄 Challenge Flow - Step-up Authentication Required'
'challenge_required' => true

// 2. Callback debe recibir sesión correctamente
'🔄 Processing Challenge Callback'
'payment_instrument_id' => '...'  // NO debe ser NULL

// 3. Validation debe ser exitosa
'✅ PASO 5.5A: Validation Service Success'
'status' => 'AUTHENTICATION_SUCCESSFUL'

// 4. Authorization después del challenge
'✅ PASO 5.5B: Authorization Success'
```

### **Mensajes de ERROR a buscar**:

```php
// Sesión perdida en callback
'❌ Sesión expirada. Por favor intente nuevamente.'
'payment_instrument_id' => NULL  // ← PROBLEMA

// Cookies rechazadas (esto aparece en la consola del navegador, no en logs)
'Cookie ... has been rejected because it is in a cross-site context'
```

---

## 📚 REFERENCIAS

- [Laravel Session Configuration](https://laravel.com/docs/11.x/session#configuration)
- [MDN: SameSite Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie#samesitesamesite-value)
- [CyberSource 3DS 2.2.0 Documentation](https://developer.cybersource.com/docs/cybs/en-us/payer-auth/developer/all/rest/payer-auth/pa-intro.html)

---

## ⚙️ CONFIGURACIÓN COMPLETA RECOMENDADA

Aquí está el bloque completo de configuración que debes tener en tu `.env`:

```env
# ===== IMPORTANTE: Configuración de sesión para 3DS Challenge =====
# El challenge 3DS viene de un iframe externo (CardinalCommerce).
# Para que las cookies de sesión funcionen en ese contexto cross-site,
# necesitamos configurar SESSION_SAME_SITE como "null" en desarrollo local.
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SAME_SITE=null

# Para producción con HTTPS, cambiar a:
# SESSION_SAME_SITE=none
# SESSION_SECURE_COOKIE=true
# ================================================================

# ===== CyberSource Configuration =====
CYBERSOURCE_MERCHANT_ID=test_tc_cr_011014952
CYBERSOURCE_API_KEY=tu_api_key_aqui
CYBERSOURCE_API_SECRET=tu_api_secret_aqui
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com

# 3D Secure Configuration
CYBERSOURCE_3DS_ENABLED=true
CYBERSOURCE_3DS_VERSION=2.2.0

# Payment Settings
CYBERSOURCE_DEFAULT_CURRENCY=USD
CYBERSOURCE_CAPTURE_ON_AUTH=true

# URLs de Callback
CYBERSOURCE_CHALLENGE_RETURN_URL="${APP_URL}/payment/challenge/callback"
CYBERSOURCE_SUCCESS_URL="${APP_URL}/payment/success"
CYBERSOURCE_FAILURE_URL="${APP_URL}/payment/failed"

# Monedas permitidas
CYBERSOURCE_ALLOWED_CURRENCIES=USD,CRC
# ====================================
```

---

## 🎉 RESULTADO ESPERADO

Después de aplicar esta configuración:

1. ✅ El challenge se mostrará correctamente en el iframe
2. ✅ El usuario podrá completar el OTP
3. ✅ El callback recibirá la sesión correctamente
4. ✅ La autorización se procesará en el orden correcto:
   - PASO 5.5A: Validation
   - PASO 5.5B: Authorization
5. ✅ El pago se guardará correctamente en la base de datos
6. ✅ Redireccionará a la página de éxito

---

**Fecha**: 31 de Octubre de 2025  
**Versión**: 1.0.0

