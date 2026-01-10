# 📋 Configuración de Emails en Plesk

## 🎯 Objetivo
Configurar el sistema de emails (confirmaciones, cancelaciones y recordatorios) en tu servidor Plesk.

---

## ✅ SOLUCIÓN RECOMENDADA: 2 Cron Jobs

### **Paso 1: Acceder a Cron en Plesk**

1. Ingresa a tu panel de Plesk
2. Ve a **Sitios web y dominios**
3. Selecciona tu dominio
4. Click en **Tareas programadas (Cron Jobs)**

### **Paso 2: Crear Cron #1 - Scheduler (Recordatorios)**

Click en **Agregar tarea** y configura:

**Configuración de tiempo:**
- ✅ Minuto: `*`
- ✅ Hora: `*`
- ✅ Día del mes: `*`
- ✅ Mes: `*`
- ✅ Día de la semana: `*`

**Comando:**
```bash
cd /var/www/vhosts/TU-DOMINIO.COM/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

**IMPORTANTE:** Reemplaza `TU-DOMINIO.COM` con tu dominio real.

**Ejemplo:**
```bash
cd /var/www/vhosts/misalon.com/httpdocs && php artisan schedule:run >> /dev/null 2>&1
```

### **Paso 3: Crear Cron #2 - Queue Worker (Emails)**

Click en **Agregar tarea** nuevamente:

**Configuración de tiempo:**
- ✅ Minuto: `*`
- ✅ Hora: `*`
- ✅ Día del mes: `*`
- ✅ Mes: `*`
- ✅ Día de la semana: `*`

**Comando:**
```bash
cd /var/www/vhosts/TU-DOMINIO.COM/httpdocs && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

**IMPORTANTE:** Reemplaza `TU-DOMINIO.COM` con tu dominio real.

**Ejemplo:**
```bash
cd /var/www/vhosts/misalon.com/httpdocs && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

### **Explicación de los parámetros:**

- `--stop-when-empty`: El worker se detiene cuando no hay trabajos pendientes
- `--max-time=50`: Se detiene después de 50 segundos (el cron lo reiniciará en el siguiente minuto)
- `>> /dev/null 2>&1`: Suprime la salida para evitar emails del cron

---

## 🧪 Verificar que Funciona

### **Prueba 1: Verificar Crons**

1. Espera 2-3 minutos después de configurar
2. En Plesk, ve a **Archivos** → **Administrador de archivos**
3. Navega a `storage/logs/`
4. Abre `laravel.log` y busca:
   ```
   Recordatorios de citas
   Processing: App\Mail\CitaConfirmada
   ```

### **Prueba 2: Crear una Cita**

1. Crea una nueva cita desde la web
2. Espera 1-2 minutos
3. Verifica que llegue el email de confirmación
4. Revisa SPAM si no llega a bandeja principal

### **Prueba 3: Verificar Recordatorios**

1. Crea una cita para mañana
2. A las 10:00 AM del día siguiente, debe llegar el recordatorio

---

## 📊 Verificar Logs (Solución de Problemas)

### **Ver logs en Plesk:**

1. **Archivos** → **Administrador de archivos**
2. Navega a `storage/logs/laravel.log`
3. Busca errores con:
   - `ERROR`
   - `queue`
   - `mail`
   - `CitaConfirmada`

### **Ver logs por SSH (si tienes acceso):**

```bash
# Ver últimos logs
tail -f /var/www/vhosts/tu-dominio.com/httpdocs/storage/logs/laravel.log

# Filtrar solo emails
grep -i "mail\|email\|cita" /var/www/vhosts/tu-dominio.com/httpdocs/storage/logs/laravel.log | tail -50

# Ver jobs fallidos
php /var/www/vhosts/tu-dominio.com/httpdocs/artisan queue:failed
```

---

## ⚠️ Problemas Comunes

### **1. No llegan emails de confirmación**

**Causa:** El cron del queue no está corriendo o hay error en la ruta.

**Solución:**
1. Verifica que el cron #2 esté configurado correctamente
2. Verifica la ruta en el comando: `/var/www/vhosts/TU-DOMINIO/httpdocs`
3. Revisa `storage/logs/laravel.log` para ver errores

### **2. No llegan recordatorios**

**Causa:** El cron del scheduler no está corriendo.

**Solución:**
1. Verifica que el cron #1 esté configurado correctamente
2. Los recordatorios solo se envían a las 10:00 AM para citas de mañana
3. Prueba manualmente por SSH:
   ```bash
   php /var/www/vhosts/tu-dominio.com/httpdocs/artisan citas:enviar-recordatorios
   ```

### **3. Emails van a SPAM**

**Causa:** Gmail/Outlook marca emails automáticos como spam.

**Solución:**
- Configura SPF, DKIM y DMARC en tu dominio
- Considera usar un servicio profesional:
  - **Mailgun** (12,000 emails gratis/mes)
  - **SendGrid** (100 emails gratis/día)
  - **Amazon SES** (muy económico)

### **4. Error "Permission denied"**

**Causa:** Permisos incorrectos en las carpetas.

**Solución por SSH:**
```bash
cd /var/www/vhosts/tu-dominio.com/httpdocs
chmod -R 775 storage bootstrap/cache
chown -R USUARIO:psacln storage bootstrap/cache
```
Reemplaza `USUARIO` con tu usuario FTP/SSH.

### **5. Crons se ejecutan pero no pasa nada**

**Causa:** Ruta del proyecto incorrecta o versión de PHP incorrecta.

**Solución:**
1. Verifica la ruta exacta en Plesk: **Archivos** → **Administrador de archivos**
2. Verifica la versión de PHP:
   - En Plesk: **PHP Settings**
   - Debe ser PHP 8.1 o superior
3. Usa la ruta completa del PHP en el cron:
   ```bash
   cd /var/www/vhosts/tu-dominio.com/httpdocs && /usr/bin/php artisan queue:work --stop-when-empty --max-time=50
   ```

---

## 🔧 Configuración Avanzada (Opcional)

### **Opción A: Worker Persistente con nohup**

Si tienes acceso SSH, puedes iniciar un worker que se mantenga corriendo:

```bash
cd /var/www/vhosts/tu-dominio.com/httpdocs
nohup php artisan queue:work --sleep=3 --tries=3 --daemon > storage/logs/queue-worker.log 2>&1 &
```

**Verificar que está corriendo:**
```bash
ps aux | grep "queue:work"
```

**Detenerlo:**
```bash
pkill -f "queue:work"
```

### **Opción B: Usar Task Manager de Plesk**

Si tu Plesk tiene el **Task Manager** (Process Manager):

1. Ve a **Tools & Settings**
2. Click en **Task Manager**
3. Crea una nueva tarea:
   - **Comando:** `php artisan queue:work --daemon`
   - **Directorio:** `/var/www/vhosts/tu-dominio.com/httpdocs`
   - **Reinicio automático:** Sí

---

## 📧 Configuración de Email (Ya está en tu .env)

Tu configuración actual:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=teton750@gmail.com
MAIL_PASSWORD="dbnc nhjo jkys thqb"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=teton750@gmail.com
MAIL_FROM_NAME="Salón Lola Hernández"
```

✅ Está correcta, no necesitas cambiar nada.

⚠️ **Importante:** Si Gmail bloquea el envío o superas el límite de 500 emails/día, considera usar Mailgun o SendGrid.

---

## ✅ Checklist Final

Después de configurar todo, verifica:

- [ ] Cron #1 (Scheduler) configurado y corriendo
- [ ] Cron #2 (Queue Worker) configurado y corriendo
- [ ] Crear cita de prueba → Email de confirmación llega
- [ ] Crear cita para mañana → Recordatorio llega a las 10:00 AM
- [ ] Logs en `storage/logs/laravel.log` sin errores críticos
- [ ] Permisos de carpetas correctos (775 en storage)

---

## 🆘 Soporte

Si algo no funciona:

1. Revisa `storage/logs/laravel.log`
2. Verifica que los crons estén activos en Plesk
3. Prueba los comandos manualmente por SSH
4. Verifica permisos de carpetas

---

## 📚 Resumen

**Para que funcione en Plesk necesitas:**

1. ✅ 2 Cron jobs configurados (cada minuto)
2. ✅ Permisos correctos en storage/
3. ✅ Configuración de email en .env (ya la tienes)
4. ✅ PHP 8.1+ configurado en Plesk

**Emails que se enviarán automáticamente:**
- ✉️ **Confirmación:** Al crear una cita
- ⏰ **Recordatorio:** 24h antes (10:00 AM)
- ❌ **Cancelación:** Al cancelar una cita

**Todo esto funcionará automáticamente con los crons configurados.**
