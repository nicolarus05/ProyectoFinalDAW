# 📚 Sistema Multi-Tenant SaaS - Salones de Belleza

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Multi-Tenancy](https://img.shields.io/badge/Multi--Tenancy-stancl%2Ftenancy-green.svg)](https://tenancyforlaravel.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Sistema SaaS multi-tenant para gestión de salones de belleza. Cada salón obtiene su propia base de datos aislada, subdominio personalizado y gestión completa de clientes, citas, servicios y empleados.

---

## 📋 Índice

- [Características](#-características)
- [Arquitectura Multi-Tenant](#-arquitectura-multi-tenant)
- [Requisitos](#-requisitos)
- [Instalación Local](#-instalación-local)
- [Configuración de Subdominios](#-configuración-de-subdominios)
- [Comandos Artisan](#-comandos-artisan)
- [Testing](#-testing)
- [Despliegue en Producción](#-despliegue-en-producción)
- [Troubleshooting](#-troubleshooting)
- [Contribución](#-contribución)

---

## ✨ Características

### Para Propietarios de Salones
- ✅ **Subdominio personalizado**: `tu-salon.tudominio.com`
- ✅ **Base de datos aislada**: Datos 100% privados y seguros
- ✅ **Gestión completa de citas**: Calendario, recordatorios, historial
- ✅ **Gestión de clientes**: Fichas, historial, observaciones
- ✅ **Catálogo de servicios**: Precios, duraciones, categorías
- ✅ **Control de empleados**: Horarios, comisiones, desempeño
- ✅ **Inventario de productos**: Stock, alertas, proveedores
- ✅ **Dashboard analytics**: Métricas, gráficos, reportes

### Para Administradores SaaS
- ✅ **Creación de tenants** via artisan o interfaz web
- ✅ **Backups automáticos** antes de eliminaciones
- ✅ **Soft deletes** con período de gracia de 30 días
- ✅ **Comandos de gestión**: create, delete, list, seed, purge
- ✅ **Monitoreo de tenants**: Estado, uso, estadísticas
- ✅ **Migraciones automáticas** para nuevos tenants

---

## 🏗️ Arquitectura Multi-Tenant

### Estrategia: Base de Datos por Tenant

```
┌─────────────────────────────────────────────┐
│          Base de Datos Central              │
│  - tenants (registro de salones)            │
│  - domains (subdominios)                    │
│  - cache, jobs (sistema)                    │
└─────────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┬─────────────┐
        │                         │             │
┌───────▼──────┐         ┌────────▼─────┐  ┌──▼──────┐
│ tenant_salon1│         │ tenant_salon2│  │  ...    │
│  - users     │         │  - users     │  │         │
│  - clientes  │         │  - clientes  │  │         │
│  - citas     │         │  - citas     │  │         │
│  - servicios │         │  - servicios │  │         │
│  - empleados │         │  - empleados │  │         │
│  - productos │         │  - productos │  │         │
└──────────────┘         └──────────────┘  └─────────┘
```

### Identificación por Subdominio

```
https://salon-maria.tudominio.com
         └──────┬──────┘
            Tenant ID
                ↓
      Inicializa contexto
                ↓
    Conecta a tenant_salon_maria
```

---

## 💻 Requisitos

### Software Requerido

- **PHP**: 8.2 o superior
- **Composer**: 2.x
- **Node.js**: 18.x o superior (para assets)
- **MySQL**: 8.0 o superior / MariaDB 10.3+
- **Docker** (opcional, recomendado para desarrollo)

### Extensiones PHP

```bash
php -m | grep -E 'pdo_mysql|mbstring|xml|bcmath|json|openssl|tokenizer'
```

Todas deben estar instaladas.

---

## 🚀 Instalación Local

### Opción 1: Con Laravel Sail (Docker) - Recomendado

```bash
# 1. Clonar repositorio
git clone https://github.com/tu-usuario/salon-saas.git
cd salon-saas

# 2. Copiar archivo de entorno
cp .env.example .env

# 3. Instalar dependencias
composer install

# 4. Iniciar contenedores Docker
./vendor/bin/sail up -d

# 5. Generar APP_KEY
./vendor/bin/sail artisan key:generate

# 6. Ejecutar migraciones centrales
./vendor/bin/sail artisan migrate

# 7. (Opcional) Crear tenant de prueba
./vendor/bin/sail artisan tenant:create demo demo.localhost \
    --name="Salón Demo" \
    --email="demo@salon.com" \
    --plan="premium"

# 8. Compilar assets
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

### Opción 2: Instalación Nativa (sin Docker)

```bash
# 1-3. Igual que opción 1

# 4. Configurar base de datos en .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salon_central
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# 5. Crear base de datos
mysql -u root -p -e "CREATE DATABASE salon_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6-8. Igual que opción 1, pero sin ./vendor/bin/sail
php artisan key:generate
php artisan migrate
php artisan tenant:create demo demo.localhost --name="Salón Demo"
```

---

## 🌐 Configuración de Subdominios

### En Desarrollo (localhost)

**Opción A: Editar archivo hosts** (más simple)

```bash
# Linux/Mac: /etc/hosts
# Windows: C:\Windows\System32\drivers\etc\hosts

127.0.0.1 salon-demo.localhost
127.0.0.1 salon-maria.localhost
127.0.0.1 salon-test.localhost
```

**Opción B: Usar dnsmasq** (automático para todos los subdominios)

```bash
# Mac (con Homebrew)
brew install dnsmasq
echo 'address=/.localhost/127.0.0.1' > /usr/local/etc/dnsmasq.conf
sudo brew services start dnsmasq

# Linux (Ubuntu/Debian)
sudo apt-get install dnsmasq
echo 'address=/.localhost/127.0.0.1' | sudo tee -a /etc/dnsmasq.conf
sudo systemctl restart dnsmasq
```

**Variables de Entorno para Desarrollo:**

```env
# .env
APP_URL=http://localhost
SESSION_DOMAIN=.localhost
TENANCY_CENTRAL_DOMAINS=localhost,127.0.0.1
```

### En Producción

Ver [DEPLOYMENT.md](DEPLOYMENT.md) para configuración completa de DNS y subdominios wildcard.

---

## 🛠️ Comandos Artisan

### Gestión de Tenants

#### Crear Tenant

```bash
php artisan tenant:create {slug} {domain} [opciones]

# Ejemplos:
php artisan tenant:create salon-maria salon-maria.tudominio.com
php artisan tenant:create barberia-lopez barberia-lopez.tudominio.com \
    --name="Barbería López" \
    --email="contacto@lopez.com" \
    --plan="premium"
```

**Validaciones:**
- Slug: 3-20 caracteres, solo minúsculas/números/guiones
- Dominio: Debe ser único
- DB name: Máximo 64 caracteres (límite MySQL)

#### Listar Tenants

```bash
php artisan tenant:list                 # Solo activos
php artisan tenant:list --deleted       # Incluir eliminados
php artisan tenant:list --only-deleted  # Solo eliminados
```

#### Eliminar Tenant

```bash
# Soft delete (recomendado, período de gracia 30 días)
php artisan tenant:delete salon-maria

# Eliminación permanente (¡PELIGROSO!)
php artisan tenant:delete salon-maria --force

# Sin backup (¡NO RECOMENDADO!)
php artisan tenant:delete salon-maria --force --skip-backup
```

**Seguridad:**
- Soft delete: Automático, reversible 30 días
- Force delete: Requiere doble confirmación + escribir frase exacta
- Backup automático: Se crea antes de cualquier eliminación

#### Poblar con Datos Demo

```bash
php artisan tenant:seed {id} [opciones]

# Ejemplo:
php artisan tenant:seed salon-maria \
    --users=10 \
    --clientes=50 \
    --servicios=8 \
    --citas=100
```

#### Purgar Tenants Vencidos

```bash
# Ver qué se eliminaría (dry-run)
php artisan tenant:purge --dry-run

# Purgar tenants eliminados hace >30 días
php artisan tenant:purge

# Período personalizado (60 días)
php artisan tenant:purge --days=60

# Sin confirmación (para cron)
php artisan tenant:purge --force
```

### Migraciones y Mantenimiento

```bash
# Ejecutar migraciones en todos los tenants
php artisan tenants:migrate

# Ejecutar migraciones en tenant específico
php artisan tenants:migrate --tenants=salon-maria

# Rollback en todos los tenants
php artisan tenants:rollback

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Backups

```bash
# Backup de todos los tenants
./scripts/backup-tenants.sh

# Backup de tenant específico
./scripts/backup-tenants.sh tenant_salon_maria

# Restaurar tenant
./scripts/restore-tenant.sh storage/backups/backup_salon_maria_20250110.sql.gz tenant_salon_maria
```

---

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Solo tests de multi-tenancy
php artisan test --filter=MultiTenancy

# Con cobertura
php artisan test --coverage
```

### Tests Importantes

**MultiTenancyFunctionalTest** (8 tests)
- ✅ Sistema multi-tenancy configurado
- ✅ Crear tenant registra en BD central
- ✅ Comando tenants:migrate funciona
- ✅ Tabla users tiene estructura correcta
- ✅ Insertar y consultar datos en tenant
- ✅ Directorio storage se puede crear
- ✅ Múltiples tenants pueden coexistir
- ✅ Contexto tenant cambia correctamente

**Ejecutar tests de ejemplo:**

```bash
# Test de creación de tenant
php artisan tenant:create test-unit test-unit.localhost --name="Test Unit"

# Verificar que existe
php artisan tenant:list

# Poblar con datos
php artisan tenant:seed test-unit --users=5 --clientes=10

# Verificar aislamiento
php artisan tenants:run test-unit -- db:table users

# Limpiar
php artisan tenant:delete test-unit --force
```

---

## 🚀 Despliegue en Producción

Ver documentación completa en:
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guía paso a paso de despliegue
- **[BACKUP.md](BACKUP.md)** - Política de backups y disaster recovery

### Checklist Rápido

- [ ] Configurar variables de entorno de producción
- [ ] Configurar DNS con wildcard `*.tudominio.com`
- [ ] Configurar certificados SSL (Let's Encrypt wildcard)
- [ ] Ejecutar migraciones centrales
- [ ] Configurar cron para purga automática
- [ ] Configurar cron para backups diarios
- [ ] Verificar límites de base de datos (max connections)
- [ ] Configurar monitoreo (Sentry, New Relic, etc.)
- [ ] Probar creación de tenant de prueba
- [ ] Verificar aislamiento de datos
- [ ] Configurar emails (SMTP, SES, etc.)

---

## 🐛 Troubleshooting

### Problema: "Tenant not found"

**Síntoma**: Error 404 al acceder a subdominio

**Solución**:
```bash
# Verificar que el tenant existe
php artisan tenant:list

# Verificar dominio registrado
php artisan tinker
>>> App\Models\Domain::all()

# Limpiar cachés
php artisan config:clear && php artisan cache:clear
```

### Problema: "Table doesn't exist"

**Síntoma**: Error al acceder a datos del tenant

**Solución**:
```bash
# Ejecutar migraciones en tenant específico
php artisan tenants:migrate --tenants=nombre-salon

# Ver estado de migraciones
php artisan tenants:run nombre-salon -- migrate:status
```

### Problema: Sesiones no persisten en subdominio

**Síntoma**: Se cierra sesión al cambiar de página

**Solución**:
```env
# Verificar .env
SESSION_DRIVER=database
SESSION_DOMAIN=.tudominio.com  # ← El punto al inicio es crucial
```

### Problema: Error "DB name exceeds MySQL limit"

**Síntoma**: Error al crear tenant con slug largo

**Solución**:
```bash
# Usar slug más corto (máximo 57 caracteres después de tenant_)
php artisan tenant:create salon-nuevo salon-nuevo.tudominio.com

# El nombre de BD resultante será: tenant_salon_nuevo (21 chars)
```

### Problema: Backup falla con "mysqldump: command not found"

**Síntoma**: Error al eliminar tenant o ejecutar backup

**Solución**:
```bash
# Docker Sail
./vendor/bin/sail shell
apt-get update && apt-get install -y default-mysql-client

# O usar --skip-backup (NO RECOMENDADO)
php artisan tenant:delete salon --force --skip-backup
```

### Más Troubleshooting

Ver documentación completa en cada fase:
- `FASE_11_SEGURIDAD_OPERACIONES_COMPLETADA.md` - Sección Troubleshooting
- [DEPLOYMENT.md](DEPLOYMENT.md) - Problemas de despliegue
- [BACKUP.md](BACKUP.md) - Problemas de backups

---

## 📚 Documentación Adicional

- **[MULTI_TENANCY_IMPLEMENTATION_PLAN.md](MULTI_TENANCY_IMPLEMENTATION_PLAN.md)** - Plan completo de implementación
- **[FASE_X_COMPLETADA.md](.)** - Documentación detallada de cada fase
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Guía de despliegue en producción
- **[BACKUP.md](BACKUP.md)** - Política de backups y disaster recovery
- **[GUIA_MULTI_TENANCY.txt](GUIA_MULTI_TENANCY.txt)** - Guía técnica multi-tenancy

---

## 🤝 Contribución

### Reportar Issues

1. Verificar que no existe un issue similar
2. Incluir pasos para reproducir
3. Especificar versión de PHP/Laravel/MySQL
4. Adjuntar logs relevantes

### Pull Requests

1. Fork del repositorio
2. Crear branch: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -m "Añade nueva funcionalidad"`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Crear Pull Request con descripción detallada

### Estándares de Código

- PSR-12 para PHP
- Laravel best practices
- Tests para nuevas funcionalidades
- Documentación actualizada

---

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo [LICENSE](LICENSE) para más detalles.

---

## 👥 Autores

- **Equipo de Desarrollo** - Sistema Multi-Tenant SaaS

---

## 🙏 Agradecimientos

- [Laravel](https://laravel.com) - Framework PHP
- [stancl/tenancy](https://tenancyforlaravel.com) - Package multi-tenancy
- [Laravel Sail](https://laravel.com/docs/sail) - Entorno Docker
- Comunidad Laravel

---

## 📞 Soporte

- **Email**: soporte@tudominio.com
- **Documentación**: https://docs.tudominio.com
- **Issues**: https://github.com/tu-usuario/salon-saas/issues

---

**Versión**: 1.0.0  
**Última actualización**: 10 de Noviembre de 2025  
**Estado**: ✅ Producción
