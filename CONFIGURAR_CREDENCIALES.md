# 🔑 Configurar Credenciales de CyberSource

## ⚠️ IMPORTANTE: Necesitas Credenciales Válidas

El error que viste fue:

```
"Authentication Failed" - HTTP 401
```

Esto significa que las credenciales en `.env` **NO son válidas**.

---

## 🛠️ Cómo Obtener Credenciales de CyberSource

### **Opción 1: Usar las Credenciales de `ociann-legal`**

Si `ociann-legal` ya tiene credenciales funcionando, cópialas de ahí:

1. **Busca el archivo `.env` en `ociann-legal`** (puede estar oculto)
2. **Copia estas líneas**:
```env
CYBERSOURCE_MERCHANT_ID=xxxxx
CYBERSOURCE_API_KEY=xxxxx
CYBERSOURCE_API_SECRET=xxxxx
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com
```

3. **Pégalas en** `C:\xampp\htdocs\pasarelalaravel\.env`

### **Opción 2: Crear Cuenta de Sandbox en CyberSource**

Si necesitas credenciales nuevas:

1. **Regístrate en CyberSource**:
   - Ve a: https://developer.cybersource.com/
   - Click en "Sign Up"
   - Completa el formulario

2. **Obtén tus credenciales**:
   - Merchant ID
   - API Key (Key ID)
   - API Secret (Shared Secret Key)

3. **Actualiza `.env`**

---

## 📝 Configuración del .env

Edita `C:\xampp\htdocs\pasarelalaravel\.env`:

```env
# ============================================
# CyberSource Configuration
# ============================================
CYBERSOURCE_MERCHANT_ID=tu_merchant_id_aqui
CYBERSOURCE_API_KEY=tu_api_key_aqui
CYBERSOURCE_API_SECRET=tu_api_secret_aqui
CYBERSOURCE_BASE_URL=https://apitest.cybersource.com

# Callback URLs (ya configuradas correctamente)
CYBERSOURCE_CHALLENGE_RETURN_URL="${APP_URL}/payment/challenge/callback"
CYBERSOURCE_SUCCESS_URL="${APP_URL}/payment/success"
CYBERSOURCE_FAILURE_URL="${APP_URL}/payment/failed"

# 3D Secure 2.2.0 Configuration
CYBERSOURCE_3DS_ENABLED=true
CYBERSOURCE_3DS_VERSION=2.2.0

# Payment Settings
CYBERSOURCE_DEFAULT_CURRENCY=USD
CYBERSOURCE_CAPTURE_ON_AUTH=true
CYBERSOURCE_REQUEST_TIMEOUT=30

# Logging
CYBERSOURCE_LOG_REQUESTS=true
CYBERSOURCE_LOG_RESPONSES=true
CYBERSOURCE_LOG_LEVEL=info
```

---

## 🔍 Dónde Encontrar las Credenciales en `ociann-legal`

### **Opción A: Archivo .env (puede estar oculto)**

```bash
# En PowerShell
Get-Content C:\xampp\htdocs\ociann-legal\.env | Select-String "CYBERSOURCE"
```

### **Opción B: Configuración de PHP**

```bash
# Ver en el servidor PHP
C:\xampp\htdocs\ociann-legal> php artisan tinker
>>> config('cybersource.merchant_id')
>>> config('cybersource.api_key')
>>> config('cybersource.api_secret')
```

### **Opción C: Variables de entorno**

Puede que estén configuradas en:
- Variables de entorno de Windows
- Archivo de configuración del servidor
- Panel de control de hosting

---

## ✅ Después de Actualizar las Credenciales

```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Probar el pago nuevamente
```

---

## 🧪 Cómo Verificar que las Credenciales Funcionan

### **Método 1: Debug Mode**

1. Ve a: http://localhost:8000/payment/debug
2. Llena el formulario
3. Click en "Guardar Datos"
4. Click en "Ejecutar PASO 1"
5. **Si ves HTTP 201** → ✅ Credenciales correctas
6. **Si ves HTTP 401** → ❌ Credenciales incorrectas

### **Método 2: Ver Logs**

```bash
Get-Content storage\logs\laravel.log -Tail 20
```

Busca:
- ✅ `"http_code":201` → Correcto
- ❌ `"http_code":401` → Credenciales inválidas
- ❌ `"Authentication Failed"` → Credenciales inválidas

---

## 🚨 Errores Comunes

### **Error: "Authentication Failed" (401)**
**Causa**: Credenciales incorrectas en `.env`
**Solución**: Verifica y actualiza las 3 credenciales

### **Error: "Invalid API Key"**
**Causa**: API Key o Secret mal copiado
**Solución**: Copia sin espacios adicionales

### **Error: "Merchant ID not found"**
**Causa**: Merchant ID incorrecto
**Solución**: Verifica en el dashboard de CyberSource

---

## 📞 Si No Tienes las Credenciales

Contacta a:
- **Equipo de desarrollo** que configuró `ociann-legal`
- **CyberSource Support**: https://support.cybersource.com/
- **Osiann Admin**: hpoveda@osiann.com

---

## 💡 Modo de Prueba Sin Credenciales

Si solo quieres ver la interfaz sin procesar pagos reales:

1. **Accede al formulario**: http://localhost:8000/payment/checkout
2. **Verifica el diseño** y la UX
3. **No podrás procesar** hasta tener credenciales válidas

---

## 🎯 Siguiente Paso

**Configura las credenciales en `.env` y prueba nuevamente** 🚀

Una vez configuradas correctamente, el sistema procesará pagos sin problemas.

