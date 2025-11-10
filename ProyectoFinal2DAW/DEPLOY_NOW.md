# 🎯 GUÍA PRÁCTICA: Despliegue REAL en Render (Paso a Paso)

## 📌 Antes de Empezar

Esta guía te ayudará a desplegar tu aplicación multi-tenant en Render **AHORA MISMO**.  
Tiempo estimado: **30-45 minutos**

---

## ✅ PASO 1: Preparar Base de Datos MySQL (10 min)

### Opción A: PlanetScale (GRATIS - Recomendado)

1. **Crear cuenta**
   - Ve a: https://planetscale.com
   - Sign up con GitHub o Google
   - Verifica tu email

2. **Crear base de datos**
   ```
   - Click "New database"
   - Name: misalon-central
   - Region: EU West (Frankfurt) o US East
   - Click "Create database"
   ```

3. **Obtener credenciales**
   ```
   - Click en tu database
   - Tab "Connect"
   - Framework: Laravel
   - Copiar credenciales:
     * Host
     * Username  
     * Password
     * Database name (misalon-central)
   ```

4. **Guardar credenciales** (las necesitarás en el PASO 3)
   ```
   DB_HOST=aws.connect.psdb.cloud
   DB_USERNAME=xxxxxxxxxx
   DB_PASSWORD=pscale_pw_xxxxxxxxxx
   DB_DATABASE=misalon-central
   ```

---

## ✅ PASO 2: Crear Web Service en Render (5 min)

1. **Crear cuenta en Render**
   - Ve a: https://render.com
   - Sign up con GitHub
   - Autoriza acceso a tu repositorio

2. **Nuevo Web Service**
   ```
   - Dashboard → "New +" → "Web Service"
   - Selecciona tu repositorio: ProyectoFinal2DAW
   - Click "Connect"
   ```

3. **Configurar servicio**
   ```
   Name: misalon-app
   Environment: PHP
   Region: Frankfurt (EU Central)
   Branch: main
   
   Build Command:
   bash build.sh
   
   Start Command:
   php artisan serve --host=0.0.0.0 --port=$PORT
   
   Plan: Standard ($7/month) ← IMPORTANTE para wildcard
   ```

4. **NO DESPLEGAR AÚN**
   - Click "Create Web Service"
   - Espera que aparezca el dashboard
   - DETÉN el despliegue si ya empezó (botón "Cancel Deploy")

---

## ✅ PASO 3: Configurar Variables de Entorno (10 min)

1. **Ir a Environment**
   ```
   - En dashboard de tu servicio
   - Click "Environment" (menú izquierdo)
   - Click "Add Environment Variable"
   ```

2. **Generar APP_KEY localmente**
   ```bash
   cd /home/nicolas/Descargas/ProyectoFInal2DAW/ProyectoFinalDAW/ProyectoFinal2DAW
   ./vendor/bin/sail artisan key:generate --show
   
   # Copia el resultado (empieza con "base64:")
   ```

3. **Agregar variables UNA POR UNA**

   **Aplicación:**
   ```
   APP_NAME = MiSalon
   APP_ENV = production
   APP_DEBUG = false
   APP_URL = https://misalon-app.onrender.com (temporal)
   APP_KEY = base64:XXXXXXXXXXXX (del paso anterior)
   ```

   **Base de Datos (de PlanetScale):**
   ```
   DB_CONNECTION = mysql
   DB_HOST = aws.connect.psdb.cloud
   DB_PORT = 3306
   DB_DATABASE = misalon-central
   DB_USERNAME = xxxxxxxxxx
   DB_PASSWORD = pscale_pw_xxxxxxxxxx
   ```

   **Multi-Tenancy:**
   ```
   TENANCY_CENTRAL_DOMAINS = misalon-app.onrender.com
   ```

   **Sesiones:**
   ```
   SESSION_DRIVER = database
   SESSION_LIFETIME = 120
   SESSION_DOMAIN = .onrender.com
   SESSION_SECURE_COOKIE = true
   ```

   **Cache y Colas:**
   ```
   CACHE_DRIVER = database
   QUEUE_CONNECTION = database
   ```

   **Logging:**
   ```
   LOG_CHANNEL = stack
   LOG_LEVEL = error
   ```

4. **Guardar**
   - Click "Save Changes"
   - Espera confirmación

---

## ✅ PASO 4: Primer Despliegue (15 min)

1. **Iniciar despliegue**
   ```
   - Click "Manual Deploy" → "Deploy latest commit"
   - O push un commit y se desplegará automáticamente
   ```

2. **Monitorear build**
   ```
   - Click "Logs" (menú izquierdo)
   - Ver progreso en tiempo real
   - Esperar mensajes:
     ✓ Instalando dependencias...
     ✓ Generando APP_KEY...
     ✓ Optimizando configuración...
     ✓ Migraciones ejecutadas...
     ✓ Build completado!
   ```

3. **Esperar "Live"**
   ```
   - Estado cambiará de "Building" → "Live"
   - Tiempo: ~5-10 minutos
   - URL temporal: https://misalon-app.onrender.com
   ```

4. **Verificar health check**
   ```bash
   curl https://misalon-app.onrender.com/health
   
   # Esperado:
   # {"status":"healthy","timestamp":"2025-11-10 12:00:00",...}
   ```

5. **Acceder a la aplicación**
   ```
   - Abre: https://misalon-app.onrender.com
   - Debe cargar la landing page
   - Verificar candado verde (SSL activo)
   ```

---

## ✅ PASO 5: Crear Tenant de Prueba (5 min)

1. **Acceder a Render Shell**
   ```
   - En dashboard de Render
   - Click "Shell" (menú izquierdo)
   - Espera que cargue el terminal
   ```

2. **Crear tenant**
   ```bash
   php artisan tinker
   
   # En tinker:
   $tenant = \App\Models\Tenant::create([
       'id' => 'demo',
       'plan' => 'basico'
   ]);
   
   $tenant->domains()->create([
       'domain' => 'demo-misalon-app.onrender.com'
   ]);
   
   exit
   ```

3. **Verificar tenant creado**
   ```bash
   php artisan tenants:list
   
   # Debe mostrar:
   # demo
   ```

4. **Acceder al tenant**
   ```
   - Abre: https://demo-misalon-app.onrender.com
   - Debe redirigir a login
   - Si funciona: ✅ Multi-tenancy operativo!
   ```

---

## ✅ PASO 6 (OPCIONAL): Configurar Dominio Propio

### Solo si tienes un dominio (ej: misalon.com)

1. **Agregar dominio en Render**
   ```
   - Settings → "Custom Domains"
   - Click "Add Custom Domain"
   - Agregar: misalon.com
   - Agregar: *.misalon.com (wildcard)
   ```

2. **Configurar DNS en tu proveedor**
   ```
   En GoDaddy/Namecheap/Cloudflare:
   
   Tipo    Nombre    Valor                    TTL
   A       @         [IP de Render]          3600
   CNAME   www       misalon.com             3600
   CNAME   *         misalon.com             3600
   ```

3. **Actualizar variables en Render**
   ```
   APP_URL = https://misalon.com
   TENANCY_CENTRAL_DOMAINS = misalon.com,www.misalon.com
   SESSION_DOMAIN = .misalon.com
   ```

4. **Esperar propagación DNS**
   ```
   - Tiempo: 15 minutos - 48 horas
   - Verificar: https://dnschecker.org
   - Buscar: misalon.com
   ```

5. **Crear tenant con dominio real**
   ```bash
   php artisan tinker
   
   $tenant = \App\Models\Tenant::create(['id' => 'salon1', 'plan' => 'basico']);
   $tenant->domains()->create(['domain' => 'salon1.misalon.com']);
   
   # Acceder: https://salon1.misalon.com
   ```

---

## 🔍 Verificación Final

### Checklist Completo

- [ ] **Base de datos MySQL**
  - [ ] PlanetScale database creado
  - [ ] Credenciales guardadas
  - [ ] Conexión verificada

- [ ] **Render Web Service**
  - [ ] Servicio creado
  - [ ] Plan Standard seleccionado
  - [ ] Variables de entorno configuradas

- [ ] **Primer Despliegue**
  - [ ] Build completado sin errores
  - [ ] Estado "Live" activo
  - [ ] Health check responde 200

- [ ] **Aplicación Funcionando**
  - [ ] Landing page accesible
  - [ ] SSL activo (HTTPS)
  - [ ] Sin errores en logs

- [ ] **Multi-Tenancy Activo**
  - [ ] Tenant de prueba creado
  - [ ] Subdominio de prueba accesible
  - [ ] Login funciona en tenant

- [ ] **Dominio Propio (Opcional)**
  - [ ] Dominio agregado en Render
  - [ ] DNS configurado
  - [ ] Propagación completada
  - [ ] Wildcard funcionando

---

## 🆘 Problemas Comunes

### Error: "No encryption key"
```bash
# Solución:
# 1. Genera key localmente:
./vendor/bin/sail artisan key:generate --show

# 2. Copia el valor completo (incluyendo "base64:")
# 3. Agrégalo en Render → Environment → APP_KEY
# 4. Redespliega: Manual Deploy
```

### Error: "Connection refused" (BD)
```bash
# Solución:
# 1. Verifica credenciales de PlanetScale
# 2. Verifica que DB_HOST está correcto
# 3. Verifica que DB_PASSWORD incluye "pscale_pw_"
# 4. En PlanetScale: Settings → Whitelisting → Disable (permitir todas las IPs)
```

### Build falla
```bash
# Solución:
# 1. Ve a Logs en Render
# 2. Busca el error específico
# 3. Verifica que build.sh tiene permisos:
chmod +x build.sh
git add build.sh
git commit -m "fix: build script permissions"
git push
```

### Subdominios no funcionan
```bash
# Solución:
# 1. Verifica plan Standard o superior
# 2. Verifica wildcard agregado: *.misalon-app.onrender.com
# 3. Verifica SESSION_DOMAIN tiene punto inicial: .onrender.com
# 4. Verifica TENANCY_CENTRAL_DOMAINS NO incluye subdominios
```

---

## 📊 Costos Actuales

```
Render Standard:     $7/mes
PlanetScale Free:    $0/mes
-------------------------
TOTAL:               $7/mes
```

**Puedes cancelar en cualquier momento**

---

## 🎉 ¡Felicidades!

Si completaste todos los pasos:

✅ Tu aplicación multi-tenant está **EN PRODUCCIÓN**  
✅ Accesible desde cualquier lugar del mundo  
✅ SSL configurado automáticamente  
✅ Base de datos en la nube  
✅ Tenants aislados funcionando  
✅ Lista para registrar salones reales  

---

## 📞 Soporte

### Recursos Adicionales
- [Render Docs](https://render.com/docs)
- [PlanetScale Docs](https://planetscale.com/docs)
- [Laravel Deployment](https://laravel.com/docs/deployment)

### Archivos de Documentación
- `FASE_10_DESPLIEGUE_RENDER_COMPLETADA.md` - Guía completa
- `FASE_10_RESUMEN.md` - Resumen ejecutivo
- `DEPLOY_QUICKSTART.md` - Referencia rápida

### Comandos Útiles (Render Shell)
```bash
# Ver tenants
php artisan tenants:list

# Migrar todos los tenants
php artisan tenants:migrate --force

# Ver logs
tail -50 storage/logs/laravel.log

# Limpiar caché
php artisan cache:clear && php artisan config:clear

# Crear tenant
php artisan tinker
```

---

**¿Listo para desplegar? ¡Adelante! 🚀**
