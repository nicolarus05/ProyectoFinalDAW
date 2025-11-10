# 🚀 Guía Rápida de Despliegue en Render

Esta guía te ayudará a desplegar tu aplicación multi-tenant en Render en minutos.

## 📋 Pre-requisitos

- [x] Cuenta en [Render.com](https://render.com)
- [x] Cuenta en [PlanetScale.com](https://planetscale.com) o proveedor MySQL
- [x] Dominio propio (ej: misalon.com)
- [x] Repositorio Git configurado

## ⚡ Despliegue Rápido (5 Pasos)

### 1️⃣ Configurar Base de Datos MySQL

**Opción A: PlanetScale (Gratis)**
```bash
1. Crear cuenta en planetscale.com
2. New Database → "misalon-central"
3. Connect → Laravel
4. Copiar credenciales
```

**Opción B: DigitalOcean**
```bash
1. Crear cuenta en digitalocean.com
2. Create → Databases → MySQL
3. Copiar credenciales
```

### 2️⃣ Crear Web Service en Render

1. Ve a [Render Dashboard](https://dashboard.render.com)
2. **New +** → **Web Service**
3. Conecta tu repositorio GitHub
4. Configura:
   ```
   Name: misalon-app
   Environment: PHP
   Region: Frankfurt
   Branch: main
   Plan: Standard ($7/mes) ← Necesario para wildcard
   ```

### 3️⃣ Configurar Build y Start Commands

```bash
# Build Command
bash build.sh

# Start Command
php artisan config:cache && php artisan serve --host=0.0.0.0 --port=$PORT
```

### 4️⃣ Configurar Variables de Entorno

En Render Dashboard → **Environment**, agrega:

```env
APP_NAME=MiSalon
APP_ENV=production
APP_DEBUG=false
APP_URL=https://misalon.com
APP_KEY=                        # Generar con: php artisan key:generate --show

DB_CONNECTION=mysql
DB_HOST=                        # De PlanetScale
DB_PORT=3306
DB_DATABASE=central
DB_USERNAME=                    # De PlanetScale
DB_PASSWORD=                    # De PlanetScale

TENANCY_CENTRAL_DOMAINS=misalon.com,www.misalon.com

SESSION_DRIVER=database
SESSION_DOMAIN=.misalon.com     # ⚠️ IMPORTANTE: El punto inicial
SESSION_SECURE_COOKIE=true

CACHE_DRIVER=database
QUEUE_CONNECTION=database

LOG_LEVEL=error
```

### 5️⃣ Configurar Dominios y DNS

**En Render:**
1. Settings → Custom Domains
2. Add: `misalon.com`
3. Add: `*.misalon.com`

**En tu Proveedor DNS:**
```
Type    Name    Value                    TTL
A       @       [IP de Render]          3600
CNAME   www     misalon.com             3600
CNAME   *       misalon.com             3600
```

## ✅ Verificación

```bash
# 1. Health Check
curl https://misalon.com/health
# Debe retornar: {"status":"healthy",...}

# 2. Crear tenant de prueba
# En Render Shell:
php artisan tinker
$t = \App\Models\Tenant::create(['id' => 'demo', 'plan' => 'basico']);
$t->domains()->create(['domain' => 'demo.misalon.com']);
exit

# 3. Verificar subdominio
curl https://demo.misalon.com
# Debe redirigir a login
```

## 🔧 Comandos Útiles

```bash
# Ver logs
# Render Dashboard → Logs

# Ejecutar migraciones
php artisan migrate --force --database=landlord

# Migrar todos los tenants
php artisan tenants:migrate --force

# Limpiar caché
php artisan config:clear && php artisan cache:clear

# Listar tenants
php artisan tenants:list
```

## 📊 Costos

| Servicio | Plan | Costo |
|----------|------|-------|
| Render | Standard | $7/mes |
| PlanetScale | Free | $0/mes |
| Dominio | Anual | $12/año |
| **Total** | | **$7/mes** |

## 🆘 Problemas Comunes

### Error: "No encryption key"
```bash
php artisan key:generate --show
# Copiar el valor a APP_KEY en Render
```

### Subdominios no funcionan
```bash
# Verificar:
1. Plan Standard o superior en Render ✓
2. Wildcard domain agregado: *.misalon.com ✓
3. DNS CNAME: * → misalon.com ✓
4. SESSION_DOMAIN=.misalon.com (con punto) ✓
```

### Error de base de datos
```bash
# Verificar credenciales en Environment Variables
# Si usas PlanetScale, verificar IP whitelist
```

## 📚 Documentación Completa

Para más detalles, consulta:
- `FASE_10_DESPLIEGUE_RENDER_COMPLETADA.md` - Guía completa
- `render.yaml` - Blueprint de configuración
- `build.sh` - Script de build

## 🎯 Checklist

- [ ] Base de datos MySQL configurada
- [ ] Web Service creado en Render
- [ ] Plan Standard seleccionado
- [ ] Variables de entorno configuradas
- [ ] Build exitoso
- [ ] Dominios agregados en Render
- [ ] DNS configurado
- [ ] Health check funcionando
- [ ] Tenant de prueba creado
- [ ] Subdominio accesible

---

**¿Necesitas ayuda?** Consulta la documentación completa en `FASE_10_DESPLIEGUE_RENDER_COMPLETADA.md`
