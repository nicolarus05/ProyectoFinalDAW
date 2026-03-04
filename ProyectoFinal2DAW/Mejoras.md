# Correcciones y mejoras — Proyecto Final DAW

**Objetivo:** Reunir de forma exhaustiva todas las correcciones, mejoras y recomendaciones detectadas durante el análisis del proyecto. Cada entrada indica su **estado actual** (✅ corregido, ⚠️ parcialmente, ❌ pendiente) para reflejar la realidad del código.

**Última revisión:** 4 de marzo de 2026

---

## Índice

1. [Resumen ejecutivo](#resumen-ejecutivo)
2. [Estructura del código y buenas prácticas](#estructura-del-código-y-buenas-prácticas)
3. [Correcciones y mejoras por funcionalidad](#correcciones-y-mejoras-por-funcionalidad)
4. [Seguridad](#seguridad)
5. [Rendimiento y escalabilidad](#rendimiento-y-escalabilidad)
6. [Pruebas, CI/CD y entrega](#pruebas-cicd-y-entrega)
7. [Checklist de despliegue y seguridad en producción](#checklist-de-despliegue-y-seguridad-en-producción)
8. [Tareas priorizadas (alta/mediana/baja)](#tareas-priorizadas-altamedianabaja)
9. [Snippets y ejemplos de implementación](#snippets-y-ejemplos-de-implementación)
10. [Notas finales y recursos recomendados](#notas-finales-y-recursos-recomendados)

---

## Resumen ejecutivo

El proyecto ha evolucionado significativamente desde el análisis inicial. Muchas de las recomendaciones originales ya se han implementado (FormRequests, transacciones en cobros, índices de rendimiento, tenancy con Stancl, tests, servicios). Sin embargo, quedan puntos importantes por resolver:

**Ya implementado:**
- ✅ FormRequests para los controladores principales (Cita, Cliente, Cobro, Bono, Deuda).
- ✅ Transacciones y `lockForUpdate()` en `RegistroCobroController`.
- ✅ Migración exhaustiva de índices de rendimiento (360 líneas, idempotente).
- ✅ Multi-tenancy con Stancl/Tenancy v3.9 (aislamiento por base de datos separada).
- ✅ 5 servicios creados (`CacheService`, `FacturacionService`, `NotificacionEmailService`, `TenantCacheManager`, `TenantStorageService`).
- ✅ 23 archivos de test (auth, modelos, seguridad, tenancy, form requests).
- ✅ Eager loading (`with()`) implementado en la mayoría de controladores.
- ✅ `.env` excluido del repositorio. `.gitignore` correcto.
- ✅ Uso correcto de `{{ }}` en Blade (no hay `{!! $variable !!}` inseguro).
- ✅ Uso correcto de `DB::raw`/`whereRaw` (solo comparan columnas o aplican funciones SQL, sin input de usuario).

**Pendiente:**
- ❌ `userController.php` en minúscula rompe PSR-4.
- ❌ `CitaController` sin transacciones ni locks en operaciones que manipulan `horario_trabajo`.
- ❌ `RegistroCobroController` tiene ~1900 líneas — necesita refactoring.
- ❌ El Job `EnviarEmailCita` existe pero no se despacha desde ningún controlador; los emails se envían síncronamente.
- ❌ Email hardcodeado (`ngh2605@gmail.com`) en `RegistroEntradaSalidaController`.
- ❌ Falta `HorarioService` para extraer la lógica de ocupación/liberación de franjas.
- ❌ 10+ controladores aún usan `$request->validate()` inline.

---

## Estructura del código y buenas prácticas

### 1. Convenciones y nombres — ❌ PENDIENTE

**Problemas que persisten:**
- `app/Http/Controllers/userController.php` con `class userController` en minúscula rompe PSR-4 y puede fallar en servidores Linux (case-sensitive).
- 4 controladores importan `App\Models\user` en minúscula en lugar de `App\Models\User`.
- El modelo `Productos` usa plural (`class Productos`), inconsistente con la convención Laravel de singular (`Producto`).

**Corrección recomendada:**
```bash
# Renombrar controlador
git mv app/Http/Controllers/userController.php app/Http/Controllers/UserController.php
# Ajustar clase e imports en todos los archivos afectados
```

---

### 2. Organización / Modularidad — ⚠️ PARCIAL

**Estado actual:**
- Se crearon 5 servicios en `app/Services/` (CacheService, FacturacionService, NotificacionEmailService, TenantCacheManager, TenantStorageService).
- `RegistroCobroController` tiene ~1900 líneas — debería extraer lógica a servicios dedicados (ej. `CobroService`, `BonoUsageService`).
- `CitaController` tiene 752 líneas con lógica de franjas repetida en crear/mover/cancelar.

**Pendiente:**
- Crear `HorarioService` para la lógica de ocupación/liberación de franjas.
- Extraer la lógica de bonos de `RegistroCobroController` a un `BonoUsageService`.
- Extraer la lógica de deuda/facturación embebida en `RegistroCobroController`.

---

### 3. Validaciones y FormRequests — ⚠️ PARCIAL

**Ya implementado:**
- 8 FormRequests creados: `StoreCitaRequest`, `UpdateCitaRequest`, `StoreClienteRequest`, `UpdateClienteRequest`, `StoreRegistroCobroRequest`, `StoreBonoCompraRequest`, `RegistrarPagoDeudaRequest`, `ProfileUpdateRequest`.
- Los controladores principales (`CitaController`, `ClienteController`, `RegistroCobroController`, `DeudaController`) los usan.

**Pendiente (controladores que aún usan `$request->validate()` inline):**
- `ServicioController` (3 sitios)
- `EmpleadoController` (2 sitios)
- `HorarioTrabajoController` (3 sitios)
- `userController` (2 sitios)
- `CitaController` — métodos secundarios: `moverCita`, `cambiarEstado`, etc. (4 sitios)
- Controladores de Auth: `RegisterClienteController`, `RegisteredUserController`, `NewPasswordController`, etc.
- `TenantRegistrationController`, `ProfileController`

---

### 4. Logs y manejo de errores — ✅ CORRECTO

- Los controladores principales usan `try/catch` con `Log::error()` y devuelven mensajes genéricos al usuario.
- `RegistroCobroController` tiene logging detallado con emojis identificativos para facilitar debug.
- Recomendación menor: considerar integrar Sentry o similar para monitorización en producción.

---

## Correcciones y mejoras por funcionalidad

### 1. Autenticación y usuarios — ✅ CORRECTO

- Se usa `Hash::make()` correctamente.
- Contraseñas no se exponen en logs ni respuestas.
- Laravel Breeze instalado para scaffolding de auth.
- Registro separado para clientes (`RegisterClienteController`) y usuarios internos.

---

### 2. Registro de cliente público — ✅ CORRECTO

- Separación de `User` y `Cliente` implementada.
- FormRequests validan los datos.
- El rol se asigna como `'cliente'` internamente (no viene del formulario).

---

### 3. Gestión de citas (reservas) — ❌ PENDIENTE

**Problemas que persisten:**
- `CitaController` (752 líneas) **NO usa transacciones** en ninguna de sus operaciones (crear, mover, cancelar, destruir).
- La lógica de bloqueo/ocupación/liberación de franjas en `horario_trabajo` se repite en múltiples métodos.
- No hay `lockForUpdate()` — dos usuarios podrían reservar la misma franja simultáneamente.

**Corrección imprescindible:**
1. Extraer la lógica de franjas a `HorarioService`.
2. Envolver las operaciones en `DB::transaction()` con `lockForUpdate()`.

---

### 4. HorarioTrabajo (bloqueo/ocupación de franjas) — ❌ PENDIENTE

- La implementación actual ejecuta un `UPDATE` por bloque en un `for` (por cada 15 minutos).
- Sin transacciones ni locks, vulnerable a condiciones de carrera.

**Corrección:** Usar `UPDATE` en rango con `whereBetween` + `lockForUpdate()` dentro de una transacción.

---

### 5. Servicios y productos — ✅ CORRECTO

- Relaciones many-to-many implementadas.
- Control de stock implementado en `RegistroCobroController` (decrementa al cobrar, restaura al eliminar).

---

### 6. Cobros y caja diaria — ✅ CORRECTO

- `RegistroCobroController` usa `DB::beginTransaction()` en store(), update() y destroy().
- `lockForUpdate()` implementado en 2 sitios para operaciones de bonos.
- Redondeos y cálculos monetarios correctos con `round($valor, 2)`.
- Descuentos separados (servicios vs productos) implementados.

---

### 7. Bonos — ✅ CORRECTO

- Validación de decremento dentro de transacciones.
- Comprobaciones: bono activo, cantidad disponible, doble consumo (array `$servicioIdsCubiertosporBonoActivo`).
- `bono_uso_detalle` con `registro_cobro_id` para trazabilidad directa.
- Fallback temporal para datos históricos en destroy().

---

### 8. Emails / Jobs / Queue — ❌ PENDIENTE

**Estado actual:**
- **Existe** el Job `EnviarEmailCita` con `ShouldQueue` y configuración Redis.
- **Sin embargo**, el Job **nunca se despacha** desde ningún controlador.
- `CitaController` usa `NotificacionEmailService` que llama `Mail::to()->send()` **síncronamente** (5 sitios).
- `RegistroEntradaSalidaController` envía email **síncronamente** a un email hardcodeado `ngh2605@gmail.com`.

**Corrección:**
- Usar `EnviarEmailCita::dispatch($cita)` en `CitaController`, o hacer que `NotificacionEmailService` despache el Job en vez de enviar síncrono.
- Mover el email hardcodeado a una variable de entorno (`ADMIN_NOTIFICATION_EMAIL` en `.env`).

---

### 9. Tenancy (multisalón) — ✅ CORRECTO

**Implementación superior a la propuesta original:**
- Stancl/Tenancy v3.9 con **base de datos separada** por tenant (no `salon_id` por columna).
- `DatabaseTenancyBootstrapper` y `QueueTenancyBootstrapper` activos.
- Migraciones separadas en `database/migrations/tenant/`.
- Tests de seguridad multi-tenancy implementados.
- Middleware `InitializeTenancyByDomain` configurado.

---

## Seguridad

### 1. Inyección SQL — ✅ CORRECTO

Todos los usos de `DB::raw`/`whereRaw` encontrados son seguros (comparan columnas entre sí o aplican funciones SQL sin input de usuario):
- `whereRaw('cantidad_usada < cantidad_total')` — compara dos columnas.
- `DB::raw('DATE(fecha_hora) as fecha')` — función SQL sobre columna.

### 2. XSS — ✅ CORRECTO

- Todas las vistas usan `{{ $variable }}` (escapado) para datos de usuario.
- Las únicas instancias de `{!! !!}` son para `vite_asset()`, que es seguro.
- `escapeHtml()` implementada en JavaScript para inserciones `innerHTML` en `create-direct.blade.php`.
- `@json()` usado para pasar datos PHP a JavaScript de forma segura.

### 3. CSRF — ✅ CORRECTO

- Formularios incluyen `@csrf`.
- AJAX incluye token CSRF en cabeceras vía `<meta name="csrf-token">`.

### 4. Contraseñas y verificación — ✅ CORRECTO

- `Hash::make()` en uso.
- Laravel Breeze con flujos de password reset y email verification configurados.

### 5. Control de accesos — ✅ CORRECTO

- Spatie Permission instalado con roles y permisos.
- Middleware `role` aplicado en rutas.

### 6. Información sensible y entorno — ⚠️ PARCIAL

- ✅ `.env` excluido del repositorio (`.gitignore`).
- ✅ `.env.example` documenta que en producción debe ser `APP_DEBUG=false`.
- ❌ **Email personal hardcodeado** en `RegistroEntradaSalidaController` (~línea 244): `ngh2605@gmail.com`. Debe moverse a variable de entorno.

---

## Rendimiento y escalabilidad

### 1. Índices y migraciones — ✅ CORRECTO

Migración completa de 360 líneas en `2025_12_13_add_performance_indexes.php` que cubre:
- `users`: email, rol, [rol,email]
- `clientes`: id_user, fecha_registro
- `empleados`: id_user, categoria
- `citas`: fecha_hora, estado, id_cliente, id_empleado, [fecha_hora,estado], [id_empleado,fecha_hora,estado], grupo_cita_id
- `horario_trabajo`: disponible, [id_empleado,fecha,disponible]
- `registro_cobros`: id_cita, id_cliente, id_empleado, metodo_pago, created_at
- `deudas`, `movimientos_deuda`, `servicios` y más.

La migración es idempotente (usa `hasIndex()` antes de crear).

### 2. Consultas y N+1 — ⚠️ PARCIAL

**Implementado:** Más de 30 usos de `with()` en controladores principales.

**Pendiente:** Algunos loops en `DeudaController` hacen `Empleado::with('user')->find($empId)` dentro de iteraciones, lo que genera N+1. Debería precargarse fuera del loop.

### 3. Actualizaciones en lote vs bucles — ⚠️ PARCIAL

- ✅ `RegistroCobroController` usa queries en bloque (`whereIn`, `update()` masivo).
- ❌ `HorarioTrabajoController` y `CitaController` aún hacen `UPDATE` por cada bloque de 15 minutos en un `for`.

### 4. Caching — ✅ CORRECTO

- `CacheService` y `TenantCacheManager` implementados.
- `Cache::remember()` usado para datos estáticos.
- Coherencia con invalidación de cache en eventos.

### 5. Queues y tareas en background — ⚠️ PARCIAL

- ✅ Infraestructura Redis configurada (`QUEUE_CONNECTION=redis`).
- ✅ Job `EnviarEmailCita` creado con `ShouldQueue` y reintentos.
- ❌ El Job no se despacha desde ningún controlador — los emails van síncronos.

---

## Pruebas, CI/CD y entrega

**Estado actual:**
- ✅ 23 archivos de test en `tests/`:
  - `Feature/Auth/`: Authentication, EmailVerification, PasswordConfirmation, PasswordReset, PasswordUpdate, Registration.
  - `Feature/Models/`: Cita, Cliente, Deuda, Empleado, Productos, Servicio.
  - `Feature/Security/`: AuthenticationSecurity, TenancySecurity.
  - `Feature/`: FormRequestsValidation, MultiTenancyBasic, MultiTenancyFunctional, Profile, Scopes.
  - `Unit/`: ExampleTest.
- ✅ `phpunit.xml` configurado.

**Pendiente:**
- Tests para flujos críticos de negocio: crear/cancelar/mover citas end-to-end.
- Tests para el sistema de cobros (store con bonos, con deuda, con descuentos).
- Tests para facturación por empleado.
- Pipeline CI/CD (GitHub Actions) para ejecución automática.

**Pipeline sugerido (GitHub Actions):**
```yaml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --no-progress
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: php artisan migrate --env=testing --force
      - run: vendor/bin/phpunit --verbose
```

---

## Checklist de despliegue y seguridad en producción

- [x] `APP_KEY` configurada
- [x] `.env` excluido del repositorio
- [x] Índices de rendimiento en base de datos
- [x] Multi-tenancy con aislamiento por DB
- [x] Spatie Permission para roles
- [x] CSRF en formularios y AJAX
- [ ] `APP_DEBUG=false` verificar en producción
- [ ] `composer install --no-dev -o` en producción
- [ ] `php artisan config:cache` y `php artisan route:cache` en producción
- [ ] Permisos correctos en `storage` y `bootstrap/cache`
- [ ] Supervisor configurado para queue workers
- [ ] HTTPS y HSTS habilitados
- [ ] Backups automáticos de la DB
- [ ] Email hardcodeado reemplazado por variable de entorno
- [ ] Emails despachados vía Job/Queue (no síncronos)

---

## Tareas priorizadas (alta/mediana/baja)

### Alta prioridad (corregir antes de entrega)
- ❌ Renombrar `userController.php` a `UserController.php` y corregir imports de `App\Models\user`.
- ❌ Implementar transacciones y `lockForUpdate()` en `CitaController` (crear/mover/cancelar).
- ❌ Extraer lógica de ocupación/liberación de franjas a `HorarioService`.
- ❌ Reemplazar email hardcodeado (`ngh2605@gmail.com`) por variable de entorno.
- ❌ Despachar `EnviarEmailCita` Job desde `CitaController` (en vez de `Mail::send()` síncrono).

### Mediana prioridad
- ⚠️ Crear FormRequests para los ~10 controladores que aún usan `$request->validate()` inline.
- ⚠️ Refactorizar `RegistroCobroController` (~1900 líneas) — extraer lógica de bonos y deuda a servicios.
- ⚠️ Corregir N+1 en `DeudaController` (precargar empleados fuera de loops).
- ⚠️ Renombrar modelo `Productos` a `Producto` (convención singular).
- ⚠️ Actualizar `HorarioTrabajoController` para hacer UPDATE en rango en lugar de UPDATE por bloque.

### Baja prioridad
- Tests para flujos de negocio completos (cobros con bonos, facturación).
- Pipeline CI/CD con GitHub Actions.
- Integración con Sentry/Rollbar para monitorización de errores en producción.
- Refactor más profundo con Repository pattern si se necesita.

---

## Snippets y ejemplos de implementación

### 1) HorarioService para ocupar/liberar franjas

```php
<?php
namespace App\Services;

use App\Models\HorarioTrabajo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HorarioService
{
    public function ocuparRango(int $empleadoId, Carbon $inicio, Carbon $fin)
    {
        return DB::transaction(function () use ($empleadoId, $inicio, $fin) {
            $rows = HorarioTrabajo::where('id_empleado', $empleadoId)
                ->whereDate('fecha', $inicio->format('Y-m-d'))
                ->where('hora', '>=', $inicio->format('H:i:s'))
                ->where('hora', '<', $fin->format('H:i:s'))
                ->lockForUpdate()
                ->get();

            if ($rows->contains(fn($r) => !$r->disponible)) {
                throw new \Exception('Algunas franjas ya no están disponibles');
            }

            HorarioTrabajo::whereIn('id', $rows->pluck('id'))
                ->update(['disponible' => false]);

            return true;
        });
    }

    public function liberarRango(int $empleadoId, Carbon $inicio, Carbon $fin)
    {
        return DB::transaction(function () use ($empleadoId, $inicio, $fin) {
            HorarioTrabajo::where('id_empleado', $empleadoId)
                ->whereDate('fecha', $inicio->format('Y-m-d'))
                ->where('hora', '>=', $inicio->format('H:i:s'))
                ->where('hora', '<', $fin->format('H:i:s'))
                ->lockForUpdate()
                ->update(['disponible' => true]);
        });
    }
}
```

### 2) Despachar Job en lugar de email síncrono

```php
// En CitaController, reemplazar:
// NotificacionEmailService::send($cita, 'creada');

// Por:
use App\Jobs\EnviarEmailCita;
EnviarEmailCita::dispatch($cita);
```

### 3) Email de admin como variable de entorno

```php
// En RegistroEntradaSalidaController, reemplazar:
// \Mail::to('ngh2605@gmail.com')->send(...);

// Por:
\Mail::to(config('mail.admin_notification_email'))->send(...);

// Y en config/mail.php:
'admin_notification_email' => env('ADMIN_NOTIFICATION_EMAIL', 'admin@example.com'),
```

### 4) Ejemplo AJAX con CSRF (ya implementado)

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
fetch(url, { headers: { 'X-CSRF-TOKEN': token } });
</script>
```

### 5) FormRequest básico

```php
// php artisan make:request StoreServicioRequest

public function rules()
{
    return [
        'nombre'      => ['required', 'string', 'max:255'],
        'precio'      => ['required', 'numeric', 'min:0'],
        'duracion'    => ['required', 'integer', 'min:5'],
        'descripcion' => ['nullable', 'string'],
    ];
}
```

---

## Notas finales y recursos recomendados

- Documentación Laravel: https://laravel.com/docs
- Stancl/Tenancy: https://tenancyforlaravel.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission
- OWASP Cheat Sheets: https://cheatsheetseries.owasp.org/
- PSR-12 (estilo PHP): https://www.php-fig.org/psr/psr-12/

---

*Documento actualizado el 4 de marzo de 2026 tras auditoría completa del código fuente.*
