# Construir a tu Alcance

ERP en español para una ferretería y distribuidora de materiales de construcción. Centraliza catálogo, inventario por ubicación, compras, ventas, cotizaciones, créditos, caja y reportes ejecutivos.

## Stack

- PHP 8.5 y Laravel 12
- MySQL 8
- Livewire 3, Volt y Tailwind CSS 4
- Vite 7
- Spatie Permission
- PhpSpreadsheet 5 y Dompdf 3

## Módulos

- Productos, presentaciones, catálogo e imágenes.
- Inventario por ubicación, transferencias, ajustes y Kardex append-only.
- Compras y recepciones parciales con costo promedio ponderado (CPP).
- Clientes, ventas con/sin factura y cotizaciones.
- Créditos, cobros parciales y vencimientos.
- Caja, sesiones, arqueo, libro diario y cuentas QR/transferencia.
- Dashboard ejecutivo y reportes exportables a Excel/PDF.

## Instalación rápida

Consulte [`docs/INSTALACION.md`](docs/INSTALACION.md) para el procedimiento completo.

```bash
composer install
cp .env.example .env
php artisan key:generate
sudo service mysql start
php artisan migrate:fresh --seed --force
npm install
npm run build
php artisan serve
```

Configure las credenciales MySQL en `.env` antes de migrar.

## Credenciales demo

```text
Usuario: admin@construir.local
Contraseña: password
```

Cambie estas credenciales antes de producción.

## Calidad y operación

```bash
php artisan migrate:fresh --seed --force
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
composer dump-autoload -o
npm run build
php artisan credits:mark-overdue
```

El comando de vencimientos debe ejecutarse diariamente mediante el scheduler.

## Documentación

- [`docs/INSTALACION.md`](docs/INSTALACION.md): requisitos y despliegue.
- [`docs/MANUAL_TECNICO.md`](docs/MANUAL_TECNICO.md): arquitectura, reglas y permisos.
- [`docs/MANUAL_DESARROLLADOR.md`](docs/MANUAL_DESARROLLADOR.md): convenciones y flujo de desarrollo.
- [`PLAN_ARQUITECTURA.md`](PLAN_ARQUITECTURA.md), [`ANALISIS_TECNICO.md`](ANALISIS_TECNICO.md) y [`MODELO_BASE_DATOS.md`](MODELO_BASE_DATOS.md): referencias de diseño.

## Licencia

El código pertenece al proyecto Construir a tu Alcance y debe utilizarse según las políticas internas del negocio.
