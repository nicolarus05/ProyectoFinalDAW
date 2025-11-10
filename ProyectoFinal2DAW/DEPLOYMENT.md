# 🚀 DEPLOYMENT - Guía de Despliegue en Producción

Guía completa para desplegar el sistema multi-tenant SaaS en producción.

---

## 📋 Índice

- [Pre-requisitos](#-pre-requisitos)
- [Checklist Pre-Deploy](#-checklist-pre-deploy)
- [Despliegue en Render](#-despliegue-en-render)
- [Configuración DNS](#-configuración-dns)
- [Certificados SSL](#-certificados-ssl)
- [Variables de Entorno](#-variables-de-entorno)
- [Comandos de Deploy](#-comandos-de-deploy)
- [Post-Deploy](#-post-deploy)
- [Rollback](#-rollback)
- [Monitoreo](#-monitoreo)
- [Troubleshooting](#-troubleshooting)

---

## 📦 Pre-requisitos

### Servicios Necesarios

- ✅ **Hosting**: Render, DigitalOcean, AWS, Heroku, etc.
- ✅ **Base de Datos**: MySQL 8.0+ o MariaDB 10.3+ (con acceso remoto)
- ✅ **Dominio**: Con acceso a configuración DNS
- ✅ **SMTP**: Para envío de emails (opcional: SendGrid, SES, Mailgun)
- ✅ **Storage**: S3 o equivalente (opcional, para archivos)

### Límites y Capacidad

- **Conexiones MySQL**: Mínimo 100 (10-20 por tenant activo)
- **RAM**: Mínimo 512MB (recomendado 1GB+)
- **CPU**: 1 core mínimo (recomendado 2+)
- **Disco**: 10GB+ (crece con tenants y backups)

---

## ✅ Checklist Pre-Deploy

### Código y Repositorio

- [ ] Código en branch `main` o `production`
- [ ] Todos los tests pasando (`php artisan test`)
- [ ] Sin errores de lint/análisis estático
- [ ] `.env.example` actualizado con todas las variables
- [ ] `composer.json` con versiones correctas
- [ ] Assets compilados (`npm run build`)

### Configuración

- [ ] `APP_ENV=production` configurado
- [ ] `APP_DEBUG=false` configurado
- [ ] `APP_KEY` generado único
- [ ] Base de datos MySQL accesible remotamente
- [ ] Dominio principal apuntando al servidor
- [ ] DNS wildcard configurado (`*.tudominio.com`)

### Seguridad

- [ ] Credenciales seguras (contraseñas fuertes)
- [ ] Variables sensibles en `.env` (no en código)
- [ ] HTTPS configurado (certificado SSL)
- [ ] CORS configurado correctamente
- [ ] Rate limiting activado
- [ ] CSRF protection activado

### Backups

- [ ] Script de backup probado localmente
- [ ] Cron para backups automáticos configurado
- [ ] Almacenamiento de backups definido
- [ ] Política de retención establecida
- [ ] Procedimiento de restauración documentado

---

## 🌐 Despliegue en Render

### Paso 1: Crear Servicio Web

1. **Ir a [Render Dashboard](https://dashboard.render.com)**
2. Click en **"New +"** → **"Web Service"**
3. Conectar repositorio GitHub/GitLab
4. Configurar:

```yaml
Name: salon-saas-prod
Environment: Docker (o Native si no usas Docker)
Branch: main
```

### Paso 2: Build Settings

```bash
# Build Command
composer install --no-dev --optimize-autoloader && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
npm install && npm run build
```

**Explicación:**
- `--no-dev`: No instala dependencias de desarrollo
- `--optimize-autoloader`: Optimiza autoload de clases
- `config:cache`: Cachea configuración (mejora rendimiento)
- `route:cache`: Cachea rutas
- `view:cache`: Cachea vistas Blade
- `npm run build`: Compila assets para producción

### Paso 3: Start Command

```bash
# Start Command
php artisan migrate --force && \
php artisan tenants:migrate --force && \
php artisan serve --host=0.0.0.0 --port=$PORT
```

**Explicación:**
- `migrate --force`: Ejecuta migraciones centrales sin confirmación
- `tenants:migrate --force`: Ejecuta migraciones en todos los tenants
- `--port=$PORT`: Render asigna puerto dinámico

**Alternativa con Nginx** (mejor rendimiento):

```dockerfile
# Crear Dockerfile
FROM php:8.2-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    nginx \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copiar código
COPY . /var/www/html
WORKDIR /var/www/html

# Instalar composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exponer puerto
EXPOSE 80

CMD php artisan migrate --force && \
    php artisan tenants:migrate --force && \
    nginx -g "daemon off;"
```

### Paso 4: Configurar Variables de Entorno

Ver sección [Variables de Entorno](#-variables-de-entorno) más abajo.

### Paso 5: Configurar Dominio

1. En Render Dashboard → Tu servicio → **"Settings"**
2. **Custom Domain** → Add Custom Domain
3. Añadir:
   - `tudominio.com` (dominio principal)
   - `*.tudominio.com` (wildcard para subdominios)

**Nota:** Wildcard requiere plan Render **Pro** ($85/mes) o superior.

---

## 🌍 Configuración DNS

### Registros Necesarios

#### Dominio Principal

```dns
Tipo: A
Host: @
Valor: [IP del servidor Render]
TTL: 300

# O si Render usa CNAME:
Tipo: CNAME
Host: @
Valor: [tu-app].onrender.com
TTL: 300
```

#### Wildcard para Subdominios

```dns
Tipo: CNAME
Host: *
Valor: tudominio.com
TTL: 300

# O directamente a Render:
Tipo: A
Host: *
Valor: [IP del servidor Render]
TTL: 300
```

### Verificar DNS

```bash
# Verificar dominio principal
dig tudominio.com

# Verificar wildcard
dig salon-prueba.tudominio.com
dig cualquier-subdominio.tudominio.com

# Todos deben resolver a la misma IP
```

### Tiempo de Propagación

- **Típico**: 5-30 minutos
- **Máximo**: 24-48 horas
- **Verificar**: https://www.whatsmydns.net

---

## 🔒 Certificados SSL

### Opción 1: Let's Encrypt con Render (Automático)

Render provisiona certificados SSL automáticamente para:
- Dominio principal: `tudominio.com`
- Subdominios: `*.tudominio.com` (requiere plan Pro)

**Configuración:**
1. Render Dashboard → Tu servicio → Settings → SSL
2. Activar **"Auto-generate SSL certificate"**
3. Esperar 2-5 minutos para provisión

### Opción 2: Certificado Wildcard Manual (Certbot)

Si no usas Render o necesitas más control:

```bash
# Instalar certbot
sudo apt-get install certbot

# Obtener certificado wildcard (requiere validación DNS)
sudo certbot certonly --manual --preferred-challenges dns \
  -d tudominio.com \
  -d *.tudominio.com

# Certbot pedirá crear un registro TXT en DNS:
# _acme-challenge.tudominio.com. TXT "valor-generado"

# Configurar Nginx para usar certificado
server {
    listen 443 ssl;
    server_name ~^(?<tenant>.+)\.tudominio\.com$;
    
    ssl_certificate /etc/letsencrypt/live/tudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tudominio.com/privkey.pem;
    
    # ...resto de configuración
}

# Auto-renovación (cron cada 3 meses)
0 0 1 */3 * certbot renew --quiet
```

### Verificar SSL

```bash
# Online
https://www.ssllabs.com/ssltest/analyze.html?d=tudominio.com

# CLI
curl -I https://tudominio.com
curl -I https://salon-prueba.tudominio.com
```

---

## ⚙️ Variables de Entorno

### Variables de Producción (.env)

```env
# === APLICACIÓN ===
APP_NAME="Salón SaaS"
APP_ENV=production
APP_KEY=base64:... # Generar con: php artisan key:generate
APP_DEBUG=false
APP_URL=https://tudominio.com

# === BASE DE DATOS CENTRAL ===
DB_CONNECTION=mysql
DB_HOST=mysql-prod.example.com  # IP o hostname de MySQL
DB_PORT=3306
DB_DATABASE=salon_central
DB_USERNAME=salon_user
DB_PASSWORD=password_seguro_aqui  # ¡CAMBIAR!

# === TENANCY ===
TENANCY_CENTRAL_DOMAINS=tudominio.com,www.tudominio.com
TENANCY_DATABASE_PREFIX=tenant_
TENANCY_DATABASE_SUFFIX=

# === SESIONES ===
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.tudominio.com  # ← Punto al inicio para wildcard

# === CACHÉ ===
CACHE_DRIVER=database  # O redis si tienes
QUEUE_CONNECTION=database  # O redis

# === MAIL (SMTP) ===
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com  # O SendGrid, SES, Mailgun
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="${APP_NAME}"

# === LOGGING ===
LOG_CHANNEL=stack
LOG_LEVEL=warning  # production: warning o error
LOG_SLACK_WEBHOOK_URL=  # Opcional: notificaciones Slack

# === STORAGE ===
FILESYSTEM_DISK=local  # O s3 para producción
AWS_ACCESS_KEY_ID=  # Si usas S3
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# === SEGURIDAD ===
BCRYPT_ROUNDS=12  # Aumentar en producción
SANCTUM_STATEFUL_DOMAINS=tudominio.com,*.tudominio.com

# === MONITOREO (Opcional) ===
SENTRY_LARAVEL_DSN=  # Tracking de errores
NEW_RELIC_LICENSE_KEY=  # Performance monitoring
```

### Generar APP_KEY

```bash
php artisan key:generate --show
# Copiar output y pegarlo en .env
```

### Verificar Configuración

```bash
php artisan config:show database
php artisan config:show tenancy
php artisan config:show session
```

---

## 📜 Comandos de Deploy

### Deploy Inicial

```bash
# 1. Conectar a servidor (SSH)
ssh usuario@tu-servidor.com

# 2. Clonar repositorio
git clone https://github.com/tu-usuario/salon-saas.git
cd salon-saas

# 3. Configurar .env
cp .env.example .env
nano .env  # Editar con valores de producción

# 4. Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 5. Generar APP_KEY
php artisan key:generate

# 6. Ejecutar migraciones
php artisan migrate --force

# 7. Optimizar aplicación
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Configurar permisos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 9. Crear directorio backups
mkdir -p storage/backups
chmod 775 storage/backups

# 10. Configurar cron (backups + purge)
crontab -e
```

Añadir al crontab:

```cron
# Backups diarios a las 2 AM
0 2 * * * cd /ruta/al/proyecto && ./scripts/backup-tenants.sh >> /var/log/tenant-backup.log 2>&1

# Purga de tenants vencidos a las 3 AM
0 3 * * * cd /ruta/al/proyecto && php artisan tenant:purge --force >> /var/log/tenant-purge.log 2>&1

# Laravel scheduler (si usas tareas programadas)
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Deploy de Actualizaciones

```bash
# 1. Activar modo mantenimiento
php artisan down --retry=60

# 2. Pull últimos cambios
git pull origin main

# 3. Actualizar dependencias
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 4. Ejecutar migraciones (si hay)
php artisan migrate --force
php artisan tenants:migrate --force

# 5. Limpiar cachés antiguos
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 6. Recrear cachés optimizados
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Reiniciar workers (si usas queues)
php artisan queue:restart

# 8. Desactivar modo mantenimiento
php artisan up
```

### Script de Deploy Automático

```bash
#!/bin/bash
# deploy.sh

set -e  # Salir si hay error

echo "🚀 Iniciando deploy..."

# Activar mantenimiento
php artisan down --retry=60 || true

# Pull código
git pull origin main

# Dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Migraciones
php artisan migrate --force
php artisan tenants:migrate --force

# Cachés
php artisan optimize:clear
php artisan optimize

# Queues
php artisan queue:restart || true

# Desactivar mantenimiento
php artisan up

echo "✅ Deploy completado!"
```

Uso:

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## ⏪ Rollback

### Rollback de Código

```bash
# 1. Activar mantenimiento
php artisan down

# 2. Volver a commit anterior
git log --oneline -5  # Ver commits recientes
git reset --hard abc123  # Reemplazar abc123 con commit hash

# 3. Reinstalar dependencias de esa versión
composer install --no-dev --optimize-autoloader

# 4. Rollback migraciones (si es necesario)
php artisan migrate:rollback --step=1 --force
php artisan tenants:rollback --step=1 --force

# 5. Limpiar y recrear cachés
php artisan optimize:clear
php artisan optimize

# 6. Desactivar mantenimiento
php artisan up
```

### Rollback de Base de Datos

```bash
# Opción 1: Rollback paso a paso
php artisan migrate:rollback --step=1 --force

# Opción 2: Restaurar desde backup
cd storage/backups
./restore-tenant.sh backup_central_20250110_020000.sql.gz salon_central

# Opción 3: Rollback específico
php artisan migrate:rollback --path=database/migrations/2025_01_10_nueva_migracion.php --force
```

### Plan de Rollback Rápido

```bash
# Crear tag de versión antes de deploy
git tag v1.5.0
git push origin v1.5.0

# Si hay problema, rollback rápido
git checkout v1.4.0  # Versión anterior estable
./deploy.sh
```

---

## 📊 Monitoreo

### Logs de Aplicación

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Filtrar errores
grep -i error storage/logs/laravel.log

# Ver últimos 100 errores
tail -n 100 storage/logs/laravel.log | grep ERROR
```

### Monitoreo de Tenants

```bash
# Listar todos los tenants
php artisan tenant:list

# Ver tenants eliminados pendientes de purga
php artisan tenant:list --only-deleted

# Verificar backups recientes
ls -lh storage/backups/

# Ver uso de base de datos
php artisan tinker
>>> DB::select("SELECT table_schema, SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)' FROM information_schema.tables WHERE table_schema LIKE 'tenant%' GROUP BY table_schema;");
```

### Herramientas de Monitoreo Externas

#### Sentry (Tracking de Errores)

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=tu-dsn-aqui
```

```env
# .env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

#### New Relic (Performance)

```bash
# Instalar agente
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
sudo sh -c 'echo "deb http://apt.newrelic.com/debian/ newrelic non-free" > /etc/apt/sources.list.d/newrelic.list'
sudo apt-get update
sudo apt-get install newrelic-php5

# Configurar
sudo newrelic-install install
```

#### Laravel Telescope (Local/Staging)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**⚠️ NO usar Telescope en producción** (consume muchos recursos).

---

## 🐛 Troubleshooting

### Error: "Connection refused" en MySQL

**Síntoma**: No puede conectar a base de datos

**Soluciones**:
```bash
# Verificar que MySQL acepta conexiones remotas
mysql -h tu-servidor-mysql.com -u usuario -p

# Verificar firewall
sudo ufw allow 3306/tcp

# Verificar bind-address en MySQL
sudo nano /etc/mysql/my.cnf
# bind-address = 0.0.0.0  (NO 127.0.0.1)

# Dar permisos remotos al usuario
mysql> GRANT ALL PRIVILEGES ON *.* TO 'usuario'@'%' IDENTIFIED BY 'password';
mysql> FLUSH PRIVILEGES;
```

### Error: "Tenant not found" después de deploy

**Síntoma**: Subdominios no funcionan

**Soluciones**:
```bash
# Limpiar cachés
php artisan config:clear
php artisan cache:clear

# Verificar SESSION_DOMAIN en .env
# Debe ser: SESSION_DOMAIN=.tudominio.com (con punto al inicio)

# Verificar tenants en BD
php artisan tenant:list

# Recrear dominio si es necesario
php artisan tinker
>>> $tenant = App\Models\Tenant::find('salon-id');
>>> $tenant->domains()->create(['domain' => 'salon.tudominio.com']);
```

### Error: Assets (CSS/JS) no cargan

**Síntoma**: Página sin estilos o JS roto

**Soluciones**:
```bash
# Recompilar assets
npm run build

# Verificar APP_URL en .env
APP_URL=https://tudominio.com  # Sin barra final

# Verificar permisos de public
chmod -R 755 public/build

# Limpiar caché de Vite
rm -rf node_modules/.vite
npm run build
```

### Error: 500 Internal Server Error

**Síntoma**: Error genérico 500

**Soluciones**:
```bash
# Ver logs
tail -f storage/logs/laravel.log

# Verificar permisos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Regenerar autoload
composer dump-autoload

# Limpiar todo
php artisan optimize:clear
php artisan optimize
```

### Performance Lenta

**Síntoma**: Aplicación responde lento

**Soluciones**:
```bash
# Activar todas las optimizaciones
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Usar OPcache (PHP)
# Editar php.ini:
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

# Usar Redis para cache/sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# Monitorear queries lentas
php artisan telescope:install  # En staging
```

---

## 📞 Soporte

- **Documentación**: [README_MULTITENANCY.md](README_MULTITENANCY.md)
- **Backups**: [BACKUP.md](BACKUP.md)
- **Issues**: GitHub Issues

---

**Versión**: 1.0.0  
**Última actualización**: 10 de Noviembre de 2025  
**Estado**: ✅ Producción Ready
