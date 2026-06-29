# Yachay Panel

Área de clientes y panel administrativo para servicios digitales: hosting, dominios,
licencias, mantenimiento web, SEO y **facturación recurrente con comprobantes
electrónicos SUNAT** (vía APISPERU).

Construido con **Laravel 12 + Filament 3**, inspirado en WHMCS / Blesta / WISECP pero
con una interfaz más simple y moderna.

## Arquitectura

El ecosistema se divide en:

- **Web comercial (WordPress)** — `yachay.lat` (fuera de este repositorio).
- **Panel administrativo (Laravel/Filament)** — `admin.yachay.lat` → ruta `/admin`.
- **Portal de clientes (Laravel/Filament)** — `clientes.yachay.lat` → ruta `/client`.

Ambos paneles comparten la misma base de datos y modelos.

## Stack

- Laravel 12 · PHP 8.4
- Filament 3 (dos paneles: admin y cliente)
- Spatie Laravel Permission (roles y permisos)
- MySQL en producción / SQLite en desarrollo
- Laravel Notifications, Queues y Scheduler
- Integración SUNAT vía APISPERU

## Roles

| Rol           | Acceso                                                                 |
|---------------|------------------------------------------------------------------------|
| Administrador | Total                                                                   |
| Soporte       | Tickets + lectura de clientes/servicios                                |
| Ventas        | Crea clientes, servicios y órdenes                                     |
| Facturación   | Facturas, pagos y validación de comprobantes                           |
| Cliente       | Portal: servicios, dominios, facturas, pagos, tickets, licencias       |

Los permisos se siembran en `Database\Seeders\RolePermissionSeeder`.

## Módulos (MVP)

Clientes · Productos y planes · Servicios contratados · Dominios · Hosting ·
Facturación (con ítems) · Pagos (con validación de comprobantes) · Tickets de
soporte · Licencias · Notificaciones · Reportes (dashboard) · Configuración ·
Usuarios y roles.

### Lógica de negocio destacada

- **Facturas**: cálculo automático de subtotal/IGV/total a partir de los ítems
  (`App\Models\Invoice::recalculateTotals`).
- **Pagos**: el cliente sube su comprobante; el administrador lo aprueba o rechaza.
  Al aprobar, la factura pasa a *pagada* o *parcial* (`App\Services\PaymentManager`).
- **Renovaciones**: acción "Renovar" que extiende el vencimiento, registra el
  historial y opcionalmente genera una factura (`App\Services\ServiceManager`).
- **Automatizaciones** (`app:process-billing-reminders`, programado a diario):
  marca facturas/servicios/dominios vencidos y envía recordatorios a 7, 3, 0,
  -3 y -7 días del vencimiento.

## Facturación electrónica (APISPERU / SUNAT)

Cuando el cliente tiene **RUC**, una factura puede emitirse como comprobante
electrónico. En el formulario de la factura selecciona el tipo (`boleta`/`factura`),
serie y número, y usa la acción **"Emitir SUNAT"**.

- Servicio: `App\Services\ApisPeruService` (construye el payload UBL 2.1 y lo envía).
- Configuración: panel **Configuración** (admin) o variables `APISPERU_*` en `.env`.
- Con `APISPERU_ENABLED=false` los envíos se **simulan** (útil en desarrollo).

## Puesta en marcha

### Opción rápida (un comando)

```bash
./setup.sh          # instala, configura .env, migra+siembra y compila assets
php artisan serve   # inicia en http://localhost:8000
```

### Manual

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

> Requisitos: PHP 8.2+ con extensiones `pdo_sqlite`/`pdo_mysql`, Composer y Node 18+.
> Por defecto usa **SQLite** (sin configuración). Para MySQL, ajusta `DB_*` en `.env`.

### Credenciales de demostración

| Panel  | Usuario               | Contraseña |
|--------|-----------------------|------------|
| Admin  | `admin@yachay.lat`    | `password` |
| Cliente| `cliente@yachay.lat`  | `password` |

> Cambia estas contraseñas antes de cualquier despliegue.

### Programador de tareas

En producción agrega el cron de Laravel:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Pruebas

```bash
php artisan test
```

Incluye smoke tests de ambos paneles y pruebas de la lógica de facturación,
pagos, renovaciones y construcción del payload de APISPERU.

## Roadmap

- **v2**: pasarelas de pago (Culqi, Niubiz, Mercado Pago), API DirectAdmin,
  suspensión/renovación automática, buscador de dominios, WhatsApp.
- **v3**: marketplace, revendedores, cupones, afiliados, Cloudflare, monitoreo
  uptime, backups automáticos, escáner de malware.
