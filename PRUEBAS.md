# 🧪 GUÍA DE PRUEBAS - PASARELA DE PAGOS

## ✅ Verificación Rápida

### 1. **Verificar Base de Datos**

Abre tu gestor de base de datos (phpMyAdmin, MySQL Workbench, etc.) y ejecuta:

```sql
-- Verificar estructura de tabla payments
DESCRIBE payments;
```

**Debes ver estos campos:**
```
✅ cavv (varchar)
✅ eci (varchar)  
✅ xid (varchar)
✅ enrollment_data (json)
✅ flow_type (varchar)
✅ liability_shift (tinyint)
```

**NO debes ver:**
```
❌ threeds_cavv
❌ threeds_eci
❌ threeds_xid
❌ threeds_version
❌ threeds_authentication_status
```

---

### 2. **Probar Flujo Frictionless (Y,Y)**

**Tarjeta de prueba:** `4111111111111111`

```
Número de tarjeta: 4111 1111 1111 1111
Mes de expiración: 12
Año de expiración: 2025
CVV: 123 (opcional para 3DS 2.2.0)
Tipo: Visa

Información de facturación:
- Nombre: John
- Apellido: Doe
- Email: test@example.com
- Dirección: 123 Main St
- Ciudad: San Francisco
- Estado: CA
- Código Postal: 94107
- País: US
```

**Flujo esperado:**
1. ✅ Ingresas datos en checkout
2. ✅ Click en "Procesar Pago"
3. ✅ Página de "Recolección de datos del dispositivo" (1-2 segundos)
4. ✅ **REDIRECCIÓN AUTOMÁTICA** a página de éxito
5. ✅ No hay challenge (es frictionless)

**Verificar en base de datos:**
```sql
SELECT 
    id, 
    transaction_id, 
    flow_type, 
    status, 
    cavv, 
    eci, 
    xid,
    liability_shift
FROM payments 
ORDER BY id DESC 
LIMIT 1;
```

**Resultado esperado:**
```
flow_type: frictionless
status: completed
cavv: [valor presente]
eci: [valor presente] 
xid: [valor presente]
liability_shift: 1 (true)
```

---

### 3. **Probar Flujo Challenge (Y,C)**

**Tarjeta de prueba CyberSource para Challenge:**

Según el ambiente de pruebas de CyberSource, usa una tarjeta que fuerce challenge.
Consulta la documentación de CyberSource para tarjetas de prueba específicas que fuercen challenge.

**Flujo esperado:**
1. ✅ Ingresas datos en checkout
2. ✅ Click en "Procesar Pago"
3. ✅ Página de "Recolección de datos del dispositivo"
4. ✅ **APARECE IFRAME CON CHALLENGE**
5. ✅ Sistema te pide OTP (código)
6. ✅ Ingresas el OTP
7. ✅ **SISTEMA PROCESA AUTOMÁTICAMENTE** (¡esto antes fallaba!)
8. ✅ Redirección a página de éxito

**Verificar en base de datos:**
```sql
SELECT 
    id, 
    transaction_id, 
    flow_type, 
    status, 
    cavv, 
    eci, 
    xid,
    enrollment_data,
    liability_shift
FROM payments 
ORDER BY id DESC 
LIMIT 1;
```

**Resultado esperado:**
```
flow_type: challenge
status: completed
cavv: [valor presente]
eci: [valor presente]
xid: [valor presente]
enrollment_data: [JSON con datos de validación]
liability_shift: 1 (true)
```

---

### 4. **Modo Debug (Paso a Paso)**

Si quieres ver cada paso del proceso en detalle:

**URL:** `http://localhost/pasarelalaravel/payment/debug`

**Pasos manuales:**

1. **Llenar formulario** → Click "Guardar en Sesión"
2. **PASO 1** → Crear Instrument Identifier
3. **PASO 2** → Crear Payment Instrument
4. **PASO 3** → Setup 3D Secure
5. **PASO 4** → Check Enrollment

**Si resultado es Y,Y (Frictionless):**
6. **PASO 5** → Authorization directa

**Si resultado es Y,C (Challenge):**
6. **Challenge Modal** → Se abre modal con iframe
7. **Completar OTP** → Ingresar código
8. **PASO 5.5A** → Validation Service (automático después del OTP)
9. **PASO 5.5B** → Authorization (automático)

---

## 🔍 Logs para Debugging

Si algo falla, revisa los logs de Laravel:

```bash
cd C:\xampp\htdocs\pasarelalaravel
tail -f storage\logs\laravel.log
```

**Busca estos mensajes:**

### **Challenge exitoso:**
```
✅ Challenge successful, processing authorization...
✅ PASO 5.5A: Validation Service Success
✅ PASO 5.5B: Authorization Success
💾 Payment saved to database (Challenge - After Validation)
🎉 Payment completed successfully
```

### **Challenge fallido (antes de los cambios):**
```
❌ Missing required 3DS fields for authorization
❌ Authorization after validation failed
❌ Failed to save payment to database
```

---

## 🎯 Checklist de Verificación

### **Antes de Probar:**
- [ ] Base de datos recreada con `php artisan migrate:fresh`
- [ ] Credenciales de CyberSource configuradas en `.env`
- [ ] Servidor web corriendo (Apache/nginx)
- [ ] PHP 8.1+ instalado

### **Durante la Prueba:**
- [ ] Checkout carga correctamente
- [ ] Formulario valida campos
- [ ] Device collection se muestra
- [ ] Challenge iframe carga (si aplica)
- [ ] OTP se puede ingresar (si aplica)
- [ ] Redirección a success funciona

### **Después de la Prueba:**
- [ ] Registro en tabla `payments` existe
- [ ] Campos `cavv`, `eci`, `xid` tienen valores
- [ ] Campo `enrollment_data` contiene JSON
- [ ] Campo `flow_type` es correcto
- [ ] No hay errores en logs

---

## 🚨 Solución de Problemas

### **Problema: "Sesión expirada" después del challenge**

**Causa:** Session no persiste entre requests

**Solución:**
```php
// Verificar en .env:
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

---

### **Problema: Challenge no se completa**

**Causa posible 1:** JavaScript bloqueado por navegador
**Solución:** Abre consola del navegador (F12) y verifica errores

**Causa posible 2:** CORS issues
**Solución:** Verifica que `challenge_return_url` en `.env` sea correcta

---

### **Problema: "Missing 3DS data" en logs**

**Antes de los cambios:** ❌ Error común
**Después de los cambios:** ✅ No debe ocurrir

Si aún ocurre, verifica:
```bash
# Verificar que migraciones están actualizadas
php artisan migrate:status
```

---

## 📊 Comparación Antes/Después

### **ANTES (con threeds_* campos):**
```
Usuario ingresa OTP → 
Challenge completa → 
Sistema intenta guardar pago → 
❌ FALLA: Campos threeds_cavv, threeds_eci no existen → 
❌ No hay registro en DB → 
❌ Usuario se queda esperando indefinidamente
```

### **DESPUÉS (con campos correctos):**
```
Usuario ingresa OTP → 
Challenge completa → 
Sistema valida (PASO 5.5A) → 
Sistema autoriza (PASO 5.5B) → 
✅ GUARDA pago con campos cavv, eci, xid → 
✅ Registro exitoso en DB → 
✅ Redirección a página de éxito
```

---

## 🎉 ¡Todo Listo!

Si todos los pasos anteriores funcionan correctamente, tu pasarela está lista para:

- ✅ Usar en producción (después de pruebas exhaustivas)
- ✅ Reutilizar en otros proyectos Laravel
- ✅ Integrar con CyberSource 3D Secure 2.2.0
- ✅ Procesar pagos con challenge y frictionless

---

**Última actualización:** 29 de Octubre de 2025
**Estado:** ✅ LISTO PARA PRUEBAS

