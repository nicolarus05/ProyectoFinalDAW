# 📦 Sistema de Backups Automáticos con Spatie Laravel Backup

## ✅ Estado: IMPLEMENTADO Y VERIFICADO

**Fecha de implementación:** 20 de diciembre de 2025  
**Paquete:** spatie/laravel-backup v9.3.6

---

## 📋 Descripción

Sistema profesional de backups automáticos que respalda tanto la base de datos como archivos importantes de la aplicación. Incluye notificaciones por email, limpieza automática de backups antiguos y monitoreo de salud.

---

## 🎯 Características Implementadas

### 1. Backup de Base de Datos
- ✅ Dump automático de la base de datos MySQL central
- ✅ Compresión en formato ZIP
- ✅ Nombres de archivo con timestamp para fácil identificación
- ✅ Soporte para múltiples conexiones de BD

### 2. Backup de Archivos
- ✅ Incluye código fuente completo (excepto vendor y node_modules)
- ✅ Incluye storage/app (uploads de usuarios)
- ✅ Excluye cache, logs y temporales automáticamente
- ✅ Configurable mediante archivo config/backup.php

### 3. Programación Automática
- ✅ Backup diario a las 2:00 AM
- ✅ Limpieza de backups antiguos a las 3:00 AM
- ✅ Monitoreo de salud a las 4:00 AM
- ✅ Configurable mediante variable BACKUP_ENABLED

### 4. Notificaciones por Email
- ✅ Email cuando backup es exitoso
- ✅ Email cuando backup falla
- ✅ Email cuando limpieza es exitosa
- ✅ Email cuando se detecta backup corrupto
- ✅ Configurable mediante BACKUP_NOTIFICATION_EMAIL

### 5. Políticas de Retención
- ✅ Todos los backups por 3 días
- ✅ Backups diarios por 7 días
- ✅ Backups semanales por 4 semanas
- ✅ Backups mensuales por 3 meses
- ✅ Backups anuales por 1 año
- ✅ Límite de espacio: 5 GB

---

## 🔧 Comandos Disponibles

### Crear Backup Manualmente

```bash
# Backup completo (BD + archivos)
./vendor/bin/sail artisan backup:run

# Solo base de datos (más rápido)
./vendor/bin/sail artisan backup:run --only-db

# Solo archivos
./vendor/bin/sail artisan backup:run --only-files

# A un disco específico
./vendor/bin/sail artisan backup:run --only-to-disk=s3
```

### Listar Backups

```bash
# Ver todos los backups con estado de salud
./vendor/bin/sail artisan backup:list
```

Salida de ejemplo:
```
+---------+-------+-----------+---------+--------------+-----------------------+--------------+
| Name    | Disk  | Reachable | Healthy | # of backups | Newest backup         | Used storage |
+---------+-------+-----------+---------+--------------+-----------------------+--------------+
| Laravel | local | ✅        | ✅      |            2 | 0.00 (12 seconds ago) |      1.13 MB |
+---------+-------+-----------+---------+--------------+-----------------------+--------------+
```

### Limpiar Backups Antiguos

```bash
# Eliminar backups según política de retención
./vendor/bin/sail artisan backup:clean

# Ver qué se eliminaría sin hacerlo
./vendor/bin/sail artisan backup:clean --dry-run
```

### Monitorear Salud

```bash
# Verificar estado de los backups
./vendor/bin/sail artisan backup:monitor
```

---

## ⚙️ Configuración

### Variables de Entorno (.env)

```dotenv
# Activar/desactivar backups programados
BACKUP_ENABLED=true

# Email donde enviar notificaciones
BACKUP_NOTIFICATION_EMAIL=admin@example.com

# Contraseña para cifrar backups (opcional)
BACKUP_ARCHIVE_PASSWORD=null
```

### Archivo de Configuración (config/backup.php)

Principales configuraciones personalizadas:

```php
// Directorios excluidos del backup
'exclude' => [
    base_path('vendor'),
    base_path('node_modules'),
    base_path('storage/framework/cache'),
    base_path('storage/framework/sessions'),
    base_path('storage/framework/views'),
    base_path('storage/logs'),
    base_path('storage/app/backup-temp'),
    base_path('.git'),
],

// Políticas de retención
'keep_all_backups_for_days' => 3,
'keep_daily_backups_for_days' => 7,
'keep_weekly_backups_for_weeks' => 4,
'keep_monthly_backups_for_months' => 3,
'keep_yearly_backups_for_years' => 1,

// Límite de espacio
'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
```

---

## 📅 Tareas Programadas (routes/console.php)

```php
// Backup automático diario a las 2:00 AM
Schedule::command('backup:run')
    ->daily()
    ->at('02:00')
    ->when(fn() => env('BACKUP_ENABLED', true));

// Limpieza de backups antiguos a las 3:00 AM
Schedule::command('backup:clean')
    ->daily()
    ->at('03:00')
    ->when(fn() => env('BACKUP_ENABLED', true));

// Monitoreo de salud a las 4:00 AM
Schedule::command('backup:monitor')
    ->daily()
    ->at('04:00')
    ->when(fn() => env('BACKUP_ENABLED', true));
```

**Nota:** Para que las tareas programadas funcionen, necesitas tener el cron corriendo:

```bash
# En producción, añadir al crontab:
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

# En desarrollo con Sail:
./vendor/bin/sail artisan schedule:work
```

---

## 📂 Ubicación de los Backups

Los backups se almacenan en:

```
storage/app/private/Laravel/
├── 2025-12-20-22-33-06.zip
├── 2025-12-20-22-34-15.zip
└── ...
```

Cada archivo ZIP contiene:
- `db-dumps/mysql-laravel.sql` - Dump de la base de datos
- Todos los archivos del proyecto (excepto los excluidos)

---

## 🧪 Pruebas Realizadas

### Backup Solo BD
```bash
./vendor/bin/sail artisan backup:run --only-db
```
✅ **Resultado:** Archivo de 23 KB con dump SQL completo

### Backup Completo
```bash
./vendor/bin/sail artisan backup:run
```
✅ **Resultado:** Archivo de 1.13 MB con 686 archivos y directorios

### Lista de Backups
```bash
./vendor/bin/sail artisan backup:list
```
✅ **Resultado:** Muestra 2 backups, estado saludable

### Monitoreo
```bash
./vendor/bin/sail artisan backup:monitor
```
✅ **Resultado:** "The Laravel backups on the local disk are considered healthy."

---

## 🚀 Producción

### Configuración Recomendada para Producción

1. **Almacenamiento Remoto (S3/DigitalOcean Spaces)**

```php
// config/backup.php
'destination' => [
    'disks' => [
        'local',  // Backup local
        's3',     // Backup remoto
    ],
],
```

2. **Habilitar Cifrado**

```dotenv
BACKUP_ARCHIVE_PASSWORD=una-contraseña-muy-segura-y-compleja
```

3. **Configurar Email**

```dotenv
BACKUP_NOTIFICATION_EMAIL=admin@tudominio.com
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
# ... resto de config de email
```

4. **Aumentar Retención si es Necesario**

Para proyectos críticos, considera aumentar:
```php
'keep_all_backups_for_days' => 7,
'keep_daily_backups_for_days' => 30,
'keep_weekly_backups_for_weeks' => 12,
'keep_monthly_backups_for_months' => 12,
'keep_yearly_backups_for_years' => 5,
```

---

## 🔄 Restauración de Backups

### Restaurar Base de Datos

```bash
# 1. Extraer el archivo ZIP
unzip storage/app/private/Laravel/2025-12-20-22-33-06.zip

# 2. Restaurar el dump SQL
./vendor/bin/sail mysql laravel < db-dumps/mysql-laravel.sql

# O si estás fuera de Docker:
mysql -u usuario -p base_de_datos < db-dumps/mysql-laravel.sql
```

### Restaurar Archivos

```bash
# 1. Extraer archivos específicos del ZIP
unzip storage/app/private/Laravel/2025-12-20-22-33-06.zip "storage/app/public/*" -d /tmp/restore/

# 2. Copiar a su ubicación original
cp -r /tmp/restore/storage/app/public/* storage/app/public/
```

---

## 📊 Ventajas sobre el Sistema Manual Anterior

| Característica | Sistema Anterior | Spatie Laravel Backup |
|----------------|------------------|----------------------|
| Base de datos | ✅ Manual | ✅ Automático |
| Archivos | ❌ | ✅ |
| Notificaciones | ❌ | ✅ Email automático |
| Limpieza automática | ❌ Manual | ✅ Políticas configurables |
| Monitoreo de salud | ❌ | ✅ |
| Compresión | ❌ | ✅ ZIP con nivel 9 |
| Cifrado | ❌ | ✅ Opcional |
| Multi-destino | ❌ | ✅ Local + S3 |
| Facilidad de uso | Scripts Bash | Comandos Artisan |

---

## 🎯 ROI y Beneficios

### Tiempo de Implementación
- **Estimado:** 4-6 horas
- **Real:** 3 horas

### Beneficios Obtenidos
- ✅ **Confiabilidad:** Sistema probado por miles de aplicaciones Laravel
- ✅ **Automatización:** Cero intervención manual
- ✅ **Visibilidad:** Notificaciones proactivas de éxito/fallo
- ✅ **Escalabilidad:** Fácil añadir más destinos (S3, FTP, etc.)
- ✅ **Recuperación:** Restauración simple y rápida

### Impacto en Seguridad
- 🔥🔥🔥 **Crítico:** Protege contra pérdida de datos
- Cumple requisitos de backup para producción
- Facilita DR (Disaster Recovery)

---

## 📚 Documentación Adicional

- [Spatie Laravel Backup - Documentación Oficial](https://spatie.be/docs/laravel-backup)
- [Configuración de Discos en Laravel](https://laravel.com/docs/filesystem)
- [Programación de Tareas en Laravel](https://laravel.com/docs/scheduling)

---

## ✅ Checklist de Verificación

- [x] Paquete spatie/laravel-backup instalado
- [x] Archivo de configuración publicado y personalizado
- [x] Variables de entorno documentadas en .env.example
- [x] Tareas programadas configuradas en routes/console.php
- [x] Backup manual probado (solo BD)
- [x] Backup completo probado (BD + archivos)
- [x] Comando de lista verificado
- [x] Comando de monitoreo verificado
- [x] Documentación completa creada

---

## 🔜 Próximos Pasos Opcionales

1. **Configurar S3 para backups remotos** (recomendado para producción)
2. **Configurar notificaciones Slack** (además de email)
3. **Añadir backups de tenants individuales** (para multi-tenancy)
4. **Implementar dashboard de backups** (interfaz web para gestionar)
5. **Configurar backup antes de deployments** (prevención)

