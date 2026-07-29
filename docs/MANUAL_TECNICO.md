# Manual técnico

## Arquitectura

La aplicación usa capas:

```text
Livewire / Volt → Services y validación → Domain/eventos/excepciones
                 → Repositories / Eloquent → MySQL
```

Livewire mantiene estado de pantalla, autorización y navegación. Los servicios concentran transacciones y reglas de negocio; los repositorios concentran consultas reutilizables y agregadas. Las policies delegan permisos a `RolePermissionSeeder`.

Para el diseño completo consulte `PLAN_ARQUITECTURA.md`, `ANALISIS_TECNICO.md` y `MODELO_BASE_DATOS.md`.

## Reglas de módulos

- **Catálogo:** productos tienen código, barra, categoría, marca, unidad, presentaciones y precios con/sin factura. Códigos, barras y nombres de catálogo son únicos. `PriceService` registra `PriceHistory` append-only.
- **Inventario:** `InventoryService` es la autoridad de existencias. Recepciones, ventas, transferencias y ajustes crean Kardex append-only; ninguna salida puede dejar stock negativo. El costo usa CPP.
- **Compras:** las recepciones pueden ser parciales; al postearse mueven stock y recalculan CPP. Compras recibidas no se cancelan desde la interfaz.
- **Clientes:** un cliente de crédito es registrado con `credit_limit > 0`; no se agregó un tercer tipo.
- **Ventas:** `SaleService::register` crea venta/líneas y delega en `confirm` inventario, crédito y caja. La presentación determina el precio con o sin factura.
- **Cotizaciones:** las abiertas se editan o duplican y pueden convertirse en venta.
- **Créditos:** nacen solo al confirmar ventas a crédito. `CreditService::registerPayment` usa bloqueo de fila, permite pagos parciales y actualiza saldo/estado. `credits:mark-overdue` se ejecuta diariamente.
- **Caja:** una caja tiene una sesión abierta; apertura, movimientos y cierre registran arqueo. QR/transferencia usan cuentas activas.
- **Reportes:** `ReportRepository` y `DashboardService` hacen agregados con `selectRaw`, `groupBy` y eager loading; Excel y PDF respetan filtros.

## Endurecimiento de producción

### Política de contraseñas

`App\Support\PasswordRules` centraliza la regla de contraseñas. Exige al menos
8 caracteres, mayúsculas, minúsculas, números y símbolos, y se utiliza en
usuarios, registro, restablecimiento y actualización del perfil. No se activa
`uncompromised()` porque consulta un servicio externo y el proyecto debe poder
validar contraseñas sin red; puede evaluarse en una futura iteración con una
fuente local o un servicio aprobado.

El login conserva su límite existente de cinco intentos por correo e IP. Las
rutas de solicitud y restablecimiento de contraseña usan un límite adicional
de seis solicitudes por minuto. No se duplicó el rate limiter del login.

### Colas, scheduler y backups

Las migraciones estándar crean `jobs`, `job_batches` y `failed_jobs`, por lo
que `QUEUE_CONNECTION=database` puede utilizarse en producción. Ejecute un
worker persistente mediante Supervisor:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

El cron debe ejecutar cada minuto:

```cron
* * * * * cd /ruta/sis_ferreteria && php artisan schedule:run >> /dev/null 2>&1
```

El comando `backup:database` ejecuta `mysqldump` usando la conexión configurada,
escribe en el disco de Storage configurado, retiene 14 días por defecto y
elimina respaldos vencidos. La tarea se programa diariamente a las 02:00.
Para restaurar, detenga los workers y cargue el SQL en una base de datos
controlada con el cliente `mysql`; pruebe periódicamente el procedimiento.

### HTTPS y proxies

En `APP_ENV=production` se fuerza el esquema HTTPS y se habilita la confianza
en proxies mediante la configuración de middleware. En desarrollo local no se
aplica ninguna de estas opciones, para conservar `http://localhost`.

### Checklist

Antes de publicar:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

Verifique `APP_DEBUG=false`, cookies seguras, HTTPS, permisos de escritura para
`storage` y `bootstrap/cache`, cron del scheduler, worker de colas y un backup
restaurable.

La autenticación de dos factores (2FA) queda pendiente. Se recomienda
implementarla en un PR separado mediante un proveedor compatible con el flujo
Livewire actual, con recuperación, códigos de respaldo, pruebas de sesión y
sin alterar el login hasta contar con una migración y plan de transición.

## Matriz de permisos

| Área | Administrador | Gerente | Vendedor | Cajero | Almacenero |
|---|---|---|---|---|---|
| Productos/catálogo | Todo | Todo salvo restricciones | Ver | Ver | Crear/editar productos |
| Clientes | Todo | Todo | Ver/crear/editar | — | — |
| Compras | Todo | Todo | — | — | Ver |
| Recepciones | Todo | Todo | — | — | Ver/crear/editar/eliminar |
| Ventas | Todo | Todo | Ver/crear | Ver/crear | — |
| Cotizaciones | Todo | Todo | Ver/crear | — | — |
| Créditos | Todo | Todo | Ver | Ver | — |
| Cobros | Todo | Todo | Ver/crear | Ver/crear | — |
| Inventario | Todo | Todo | Ver | Ver | Ver/ajustar/transferir |
| Caja | Todo | Todo | — | Abrir/cerrar/mover | — |
| Cuentas de cobro | Todo | Todo | — | — | — |
| Reportes | Sí | Sí | Sí | — | — |
| Precios | Sí | Sí | — | — | — |

Toda acción sensible reautoriza en backend, aunque el botón se oculte con `@can`. Solo Administrador y Gerente reciben `prices.update` y pueden modificar precios existentes.

## Decisiones y pendientes

- Esta auditoría no modifica tablas, relaciones ni índices.
- Kardex e historial de precios son append-only desde la aplicación.
- “Precio pendiente por producto” queda pendiente de aclaración; no existe lógica ni esquema para ello.
- Para grandes volúmenes conviene medir consultas y evaluar índices sobre fechas y claves de búsqueda antes de migrar.
