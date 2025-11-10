# 💈 Sistema Multi-Tenant SaaS para Salones de Belleza<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



**Versión**: 1.0.0  <p align="center">

**Laravel**: 12.8.1  <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

**PHP**: 8.2+  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

**Estado**: ✅ Producción Ready<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

---</p>



## 📋 Índice## About Laravel



1. [Descripción](#-descripción)Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

2. [Características](#-características)

3. [Requisitos](#-requisitos)- [Simple, fast routing engine](https://laravel.com/docs/routing).

4. [Instalación Local](#-instalación-local)- [Powerful dependency injection container](https://laravel.com/docs/container).

5. [Configuración de Subdominios](#-configuración-de-subdominios)- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

6. [Comandos Artisan](#-comandos-artisan)- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

7. [Deploy en Render](#-deploy-en-render)- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

8. [Testing](#-testing)- [Robust background job processing](https://laravel.com/docs/queues).

9. [Troubleshooting](#-troubleshooting)- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

10. [Documentación Adicional](#-documentación-adicional)

Laravel is accessible, powerful, and provides tools required for large, robust applications.

---

## Learning Laravel

## 🎯 Descripción

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

Sistema SaaS multi-tenant que permite a **múltiples salones de belleza** gestionar sus operaciones de forma independiente en una única aplicación Laravel. Cada salón (tenant) tiene:

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

- ✅ Su propia **base de datos aislada**

- ✅ Su propio **subdominio** (ej: `salon-lola.misalon.com`)If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

- ✅ **Almacenamiento de archivos** separado

- ✅ **Cache independiente** por tenant## Laravel Sponsors

- ✅ Sistema de **backups automático**

- ✅ **Soft deletes** con período de gracia de 30 díasWe would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).



---### Premium Partners



## ✨ Características- **[Vehikl](https://vehikl.com/)**

- **[Tighten Co.](https://tighten.co)**

### Para Salones (Tenants)- **[WebReinvent](https://webreinvent.com/)**

- 📅 **Gestión de citas** con confirmación automática- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

- 👥 **Registro de clientes** con historial- **[64 Robots](https://64robots.com)**

- 💇 **Catálogo de servicios** personalizables- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**

- 👨‍💼 **Gestión de empleados** y permisos- **[Cyber-Duck](https://cyber-duck.co.uk)**

- 📊 **Reportes y estadísticas** de negocio- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

- 💳 **Facturación** integrada- **[Jump24](https://jump24.co.uk)**

- 📱 Sistema **responsive** (móvil/desktop)- **[Redberry](https://redberry.international/laravel/)**

- **[Active Logic](https://activelogic.com)**

### Para Administradores SaaS- **[byte5](https://byte5.de)**

- 🏢 **Creación de tenants** vía artisan commands- **[OP.GG](https://op.gg)**

- 📊 **Monitoreo centralizado** de todos los salones

- 🗄️ **Backups automáticos** pre-eliminación## Contributing

- 🔄 **Restauración** de tenants eliminados (30 días)

- 🧹 **Purga automática** de tenants vencidosThank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

- 📈 **Logs y auditoría** completa

## Code of Conduct

---

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## 📦 Requisitos

## Security Vulnerabilities

### Desarrollo Local

- **PHP**: 8.2 o superiorIf you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

- **Composer**: 2.x

- **Node.js**: 18.x o superior## License

- **MySQL**: 8.0+

- **Docker Desktop**: Para Laravel Sail (recomendado)The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


### Producción (Render)
- **MySQL**: 8.0+ (servidor externo o Render MySQL)
- **Dominio**: Con wildcard DNS configurado
- **SSL**: Let's Encrypt (automático en Render)

---

## 🚀 Instalación Local

### Opción 1: Con Docker Sail (Recomendado)

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/ProyectoFinalDAW.git
cd ProyectoFinalDAW/ProyectoFinal2DAW

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar .env para multi-tenancy
DB_CONNECTION=central
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

TENANCY_CENTRAL_DOMAINS=localhost,127.0.0.1
SESSION_DOMAIN=.localhost

# 5. Iniciar Docker
./vendor/bin/sail up -d

# 6. Ejecutar migraciones centrales
./vendor/bin/sail artisan migrate --database=central

# 7. Compilar assets
./vendor/bin/sail npm run dev
```

### Opción 2: Sin Docker (Nativo)

```bash
# 1-3. Mismo que arriba

# 4. Configurar .env
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salon_central
DB_USERNAME=root
DB_PASSWORD=tu_password

# 5. Crear BD central
mysql -u root -p -e "CREATE DATABASE salon_central;"

# 6. Ejecutar migraciones
php artisan migrate --database=central

# 7. Iniciar servidor
php artisan serve
```

---

## 🌐 Configuración de Subdominios

### Desarrollo Local (hosts file)

#### Windows: `C:\Windows\System32\drivers\etc\hosts`
#### Linux/Mac: `/etc/hosts`

```plaintext
127.0.0.1  localhost
127.0.0.1  salon-demo.localhost
127.0.0.1  salon-prueba.localhost
127.0.0.1  mi-salon.localhost
```

**Nota**: Cada tenant necesita su entrada en el archivo hosts.

### Producción (DNS Wildcard)

En tu proveedor de DNS (Cloudflare, Route53, etc.):

```plaintext
Tipo: A
Nombre: @
Valor: [IP de Render]

Tipo: CNAME
Nombre: *
Valor: misalon.com
```

Esto permite que **cualquier subdominio** (`*.misalon.com`) apunte a tu aplicación.

---

## 🔧 Comandos Artisan

### Gestión de Tenants

#### Crear Tenant
```bash
php artisan tenant:create salon-demo demo.localhost \
  --name="Salón Demo" \
  --email=demo@salon.com \
  --plan=profesional

# Resultado:
# ✅ Tenant: salon-demo
# ✅ BD: tenantsalondemo (creada + migrada)
# ✅ Dominio: demo.localhost
# ✅ Storage: configurado
```

#### Listar Tenants
```bash
# Todos los tenants activos
php artisan tenant:list

# Incluir eliminados
php artisan tenant:list --deleted

# Solo eliminados (pendientes de purga)
php artisan tenant:list --only-deleted
```

#### Poblar con Datos de Prueba
```bash
php artisan tenant:seed salon-demo \
  --users=5 \
  --clientes=20 \
  --servicios=10 \
  --citas=50
```

#### Eliminar Tenant (Soft Delete)
```bash
# Soft delete (30 días de gracia)
php artisan tenant:delete salon-demo

# Eliminación permanente inmediata
php artisan tenant:delete salon-demo --force

# Sin backup (no recomendado)
php artisan tenant:delete salon-demo --skip-backup
```

#### Purgar Tenants Vencidos
```bash
# Purgar tenants eliminados hace más de 30 días
php artisan tenant:purge

# Cambiar período
php artisan tenant:purge --days=60

# Ver qué se purgaría sin hacerlo
php artisan tenant:purge --dry-run

# Sin confirmación (usar en cron)
php artisan tenant:purge --force
```

### Comandos de Base de Datos

```bash
# Migrar todos los tenants
php artisan tenants:migrate

# Migrar tenant específico
php artisan tenants:migrate --tenants=salon-demo

# Rollback
php artisan tenants:rollback

# Fresh (reset + migrate)
php artisan tenants:migrate:fresh
```

---

## 🚀 Deploy en Render

### Resumen Rápido

1. **Crear servicio Web en Render**
2. **Configurar variables de entorno** (30+ vars)
3. **Conectar BD MySQL** (externa o Render MySQL)
4. **Configurar DNS wildcard**
5. **Deploy automático** desde GitHub

### Comandos de Build y Start

```bash
# Build Command
composer install --no-dev --optimize-autoloader && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
npm install && npm run build

# Start Command
php artisan migrate --force && \
php artisan optimize && \
php -S 0.0.0.0:$PORT -t public
```

### Variables de Entorno Críticas

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://misalon.com

SESSION_DOMAIN=.misalon.com
TENANCY_CENTRAL_DOMAINS=misalon.com

DB_CONNECTION=central
DB_HOST=[tu-mysql-host]
DB_PORT=3306
DB_DATABASE=salon_central
DB_USERNAME=[usuario]
DB_PASSWORD=[password]
```

**Ver**: [DEPLOYMENT.md](./DEPLOYMENT.md) para guía completa paso a paso.

---

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
./vendor/bin/sail artisan test

# Tests específicos
./vendor/bin/sail artisan test --filter=MultiTenancyFunctionalTest

# Con coverage
./vendor/bin/sail artisan test --coverage
```

### Tests Implementados

- ✅ **MultiTenancyFunctionalTest**: 8 tests
  - Configuración multi-tenancy
  - Creación de tenants en BD central
  - Migraciones de tenant
  - Estructura de tablas
  - Inserción y consulta de datos
  - Directorios de storage
  - Coexistencia de múltiples tenants
  - Cambio de contexto entre tenants

---

## 🔍 Troubleshooting

### Problema: "Tenant could not be identified"

**Causa**: Subdominio no configurado en hosts o DNS.

**Solución**:
```bash
# 1. Verificar archivo hosts (desarrollo)
cat /etc/hosts | grep salon-demo

# 2. Verificar tenant existe
php artisan tenant:list

# 3. Verificar dominio asociado
php artisan tinker
>>> App\Models\Tenant::find('salon-demo')->domains
```

### Problema: "Base table or view not found"

**Causa**: Migraciones de tenant no ejecutadas.

**Solución**:
```bash
# Ejecutar migraciones
php artisan tenants:migrate --tenants=salon-demo

# Verificar tablas creadas
php artisan tinker
>>> Tenant::find('salon-demo')->run(fn() => Schema::getTables())
```

### Problema: "SQLSTATE[HY000] [2002] Connection refused"

**Causa**: MySQL no está corriendo o mal configurado.

**Solución Docker**:
```bash
# Verificar contenedores
docker ps

# Reiniciar Sail
./vendor/bin/sail down
./vendor/bin/sail up -d

# Verificar logs
./vendor/bin/sail logs mysql
```

**Solución Nativa**:
```bash
# Linux/Mac
sudo systemctl start mysql

# Windows
net start MySQL80
```

### Problema: Tenant ID = 0 al crear

**Estado**: ✅ **CORREGIDO** (Actualización 10/11/2025)

Si aún experimentas este problema:
```bash
# 1. Limpiar caches
php artisan optimize:clear
rm -rf bootstrap/cache/*.php

# 2. Verificar modelo Tenant tiene los métodos correctos
grep -A 5 "getIncrementing\|shouldGenerateId\|getKeyType" app/Models/Tenant.php

# 3. Recrear tenant
php artisan tenant:create test-fix test-fix.localhost --name="Test"
```

### Problema: Campo `data` vacío

**Estado**: ✅ **CORREGIDO** (Actualización 10/11/2025)

Los datos se guardan usando accessors mágicos del trait `VirtualColumn`.

Verificar:
```bash
php artisan tinker
>>> $t = Tenant::find('salon-demo');
>>> $t->nombre  # ✅ Debe mostrar el nombre
>>> $t->data    # ⚠️  Puede ser null (normal)
```

---

## 📚 Documentación Adicional

### Documentos del Proyecto

- 📘 **[DEPLOYMENT.md](./DEPLOYMENT.md)** - Guía completa de despliegue en producción
- 📗 **[BACKUP.md](./BACKUP.md)** - Política de backups y disaster recovery
- 📙 **[FASE_11_SEGURIDAD_OPERACIONES_COMPLETADA.md](./FASE_11_SEGURIDAD_OPERACIONES_COMPLETADA.md)** - Soft deletes y comandos artisan
- 📕 **[MULTI_TENANCY_IMPLEMENTATION_PLAN.md](./MULTI_TENANCY_IMPLEMENTATION_PLAN.md)** - Plan de implementación completo

### Arquitectura Multi-Tenant

```
┌─────────────────────────────────────┐
│      BD Central (laravel)           │
│  ┌─────────────┬─────────────┐      │
│  │   tenants   │   domains   │      │
│  └─────────────┴─────────────┘      │
└─────────────────────────────────────┘
           │
     ┌─────┴─────┬──────────┐
     │           │          │
┌────▼────┐ ┌────▼────┐ ┌──▼──────┐
│ Tenant1 │ │ Tenant2 │ │ Tenant3 │
│ salon-a │ │ salon-b │ │ salon-c │
└─────────┘ └─────────┘ └─────────┘
  BD propia   BD propia   BD propia
```

### Flujo de Request

```
1. Request: https://salon-demo.localhost/
2. Middleware: InitializeTenancyByDomain
3. Identificación: salon-demo (por subdominio)
4. Conexión BD: tenantsalondemo
5. Response: Datos del tenant salon-demo
```

---

## 🤝 Contribuir

### Reportar Bugs

Abre un issue en GitHub con:
- Descripción del problema
- Pasos para reproducir
- Logs relevantes
- Versión de PHP/Laravel

### Pull Requests

1. Fork del repositorio
2. Crea una rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -m 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo [LICENSE](./LICENSE) para más detalles.

---

## 👨‍💻 Autor

**Proyecto Final 2º DAW**  
Sistema Multi-Tenant SaaS para Salones de Belleza

---

## 🙏 Agradecimientos

- **Laravel** - Framework PHP
- **Stancl/Tenancy** - Paquete multi-tenancy
- **Render** - Plataforma de hosting
- **Sail** - Entorno Docker para Laravel

---

**Última actualización**: 10 de noviembre de 2025
