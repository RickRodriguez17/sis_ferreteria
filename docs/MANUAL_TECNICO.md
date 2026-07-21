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
