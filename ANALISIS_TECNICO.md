# Análisis Técnico / Arquitectura — ERP "Construir a tu Alcance"

> Documento CTO-level. **Diseño previo a la programación.** No contiene código de aplicación.
> Stack objetivo: **Laravel 12 · PHP 8.4 · MySQL 8 · Livewire 3 · TailwindCSS 3 · Spatie Permission · Laravel Policy · Storage · Queue**.
> Complementa a `PLAN_ARQUITECTURA.md` (modelo ER detallado y plan por fases). Aquí se profundiza en las 17 áreas solicitadas.

## Índice
1. Arquitectura general del sistema
2. Arquitectura de carpetas
3. Arquitectura MVC
4. Organización por módulos
5. Convenciones de nombres
6. Estándares de código
7. Modelo Entidad-Relación (completo)
8. Diagrama de relaciones entre módulos
9. Riesgos técnicos
10. Recomendaciones de escalabilidad
11. Estrategia de auditoría
12. Estrategia de manejo de imágenes
13. Estrategia de control de inventario
14. Estrategia de precios
15. Estrategia de créditos
16. Estrategia de impresión
17. Roadmap técnico

---

## 1. Arquitectura general del sistema

Aplicación **monolito modular** Laravel (server-driven UI con Livewire). No microservicios: el volumen de una ferretería no lo justifica y un monolito bien modularizado es más barato de mantener y desplegar. La modularidad se logra por **fronteras de dominio internas** (módulos) y **capas**, no por servicios separados.

### 1.1 Capas (flujo de una petición)

```
Navegador
   │  HTTP / Livewire (AJAX)
   ▼
Ruta / Middleware (auth, verified, permission)
   ▼
Componente Livewire  ──────────────►  Blade (Tailwind)   [Capa de presentación]
   │  (valida con reglas de Form Request / Rule objects)
   ▼
Service  (caso de uso, DB::transaction, dispara Events)   [Capa de aplicación]
   │
   ├─► Domain (cálculo puro: UnitConverter, MarginCalculator, BalanceCalculator)  [Capa de dominio]
   ├─► Repository (consultas complejas reutilizables)      [Capa de acceso a datos]
   ▼
Eloquent Model (relaciones, casts, scopes)
   ▼
MySQL
```

**Principios rectores (SOLID + Clean Code):**
- **S**: cada Service = un caso de uso; cada modelo = persistencia; componentes Livewire = estado de UI.
- **O/D**: los Repositories se exponen por interfaces (`Contracts/`) e se inyectan; los Services dependen de abstracciones.
- **L/I**: interfaces pequeñas y específicas (nada de un "God repository").
- Código **desacoplado** mediante Events para efectos secundarios (Kardex, auditoría, alertas, historial de precios).

### 1.2 Componentes de infraestructura
- **Autenticación**: Laravel Breeze/Fortify (a definir en Fase 0) o auth propia con Livewire.
- **Autorización**: Spatie Permission (permisos/roles) + Policies (reglas por recurso).
- **Colas (Queue)**: infra preparada (`jobs`, `failed_jobs`, connection `database`), listeners marcados `ShouldQueue` pero ejecución síncrona hasta activar worker. Para auditoría, generación de PDF pesados y alertas.
- **Storage**: disco `public` para imágenes de producto (con posibilidad de migrar a S3 sin cambiar código).
- **Cache**: config/route/view cache en producción; cache de consultas de dashboard.
- **Comandos programados**: `credits:mark-overdue`, `inventory:rebuild` (mantenimiento).

---

## 2. Arquitectura de carpetas

```
app/
├── Console/Commands/          # inventory:rebuild, credits:mark-overdue
├── Domain/                    # LÓGICA PURA (sin Eloquent, testeable en aislamiento)
│   ├── Enums/                 # StockMovementType, SaleStatus, PurchaseStatus, PaymentMethod...
│   ├── Inventory/             # UnitConverter, BalanceCalculator
│   ├── Pricing/               # MarginCalculator, SuggestedPrice
│   └── ValueObjects/          # Money (centavos), Quantity
├── Events/                    # SaleConfirmed, ReceptionPosted, PriceChanged, CreditPaymentRegistered, StockBelowMinimum
├── Exceptions/                # InsufficientStockException, CashSessionClosedException, PriceChangeNotAllowedException
├── Http/
│   ├── Controllers/           # mínimos: auth, exports/PDF, futura API
│   └── Requests/              # Form Requests (reglas compartidas con Livewire)
├── Listeners/                 # RecordKardexMovement, RecordPriceHistory, WriteAuditLog, SendStockAlert
├── Livewire/                  # componentes agrupados por módulo (ver §4)
├── Models/
├── Policies/
├── Providers/                 # AppServiceProvider, AuthServiceProvider, EventServiceProvider, RepositoryServiceProvider
├── Repositories/
│   ├── Contracts/             # interfaces
│   └── Eloquent/              # implementaciones
├── Services/                  # ProductService, InventoryService, ReceptionService, PurchaseService,
│   │                          # SaleService, QuotationService, CreditService, CashService, PriceService, ReportService
│   └── Support/               # CodeGenerator, DocumentNumberGenerator, MoneyFormatter
database/
├── factories/
├── migrations/
└── seeders/                   # RolePermissionSeeder, SettingsSeeder, LocationSeeder, UnitSeeder, AdminUserSeeder, DemoSeeder
resources/
├── views/
│   ├── components/            # Blade components + Livewire layout
│   ├── layouts/               # app, guest, print
│   ├── livewire/              # vistas de componentes por módulo
│   └── pdf/                    # plantillas de impresión (venta, cotización, arqueo)
├── css/  js/                   # Tailwind + Alpine (viene con Livewire)
routes/
├── web.php   console.php
tests/
├── Feature/                   # flujos críticos (recepción→stock, venta→lock→stock, cobro→crédito, arqueo)
└── Unit/                       # Domain (UnitConverter, MarginCalculator, Money)
```

**» Justificación:** `Domain/` separa reglas puras de Eloquent para testear sin BD y cumplir DIP. `Repositories/{Contracts,Eloquent}` habilita sustitución/mocking. `resources/views/pdf` aísla impresión.

---

## 3. Arquitectura MVC (adaptada a Livewire)

Laravel es MVC, pero con Livewire el "Controller" clásico casi desaparece de las vistas interactivas. Distribución de responsabilidades:

| Rol MVC | Implementación en este proyecto | Responsabilidad |
|---|---|---|
| **Model** | `app/Models/*` (Eloquent) | Persistencia, relaciones, casts, scopes. **Sin** lógica de negocio pesada. |
| **View** | Blade + componentes Tailwind en `resources/views` | Render. Sin lógica; solo presentación. |
| **Controller** | Componentes **Livewire** (para pantallas interactivas) + Controllers HTTP mínimos (auth, PDF/export, API futura) | Orquestar entrada/estado de UI y **delegar** al Service. |
| **Service** (extensión) | `app/Services/*` | Casos de uso, transacciones, coordinación. |
| **Request** | `app/Http/Requests/*` | Validación de entrada; reglas reutilizadas por Livewire. |
| **Policy** | `app/Policies/*` | Autorización por recurso. |

Regla dura: **un componente Livewire no abre transacciones ni contiene reglas de negocio**; llama a un Service. Esto mantiene la UI reemplazable (si mañana se agrega API REST/móvil, los Services se reutilizan intactos).

---

## 4. Organización por módulos

26 módulos agrupados en 6 dominios funcionales. Cada módulo tiene sus componentes Livewire, y comparte Services/Models/Policies según corresponda.

| Dominio | Módulos | Componentes Livewire (carpeta) |
|---|---|---|
| **Seguridad** | Usuarios, Roles, Permisos, Auditoría, Dashboard | `Users/`, `Roles/`, `Permissions/`, `Audit/`, `Dashboard/` |
| **Catálogo** | Productos, Categorías, Marcas, Unidades, Presentaciones, Atributos, Valores de atributos, Imágenes | `Products/`, `Categories/`, `Brands/`, `Units/`, `Attributes/` |
| **Inventario** | Ubicaciones, Inventario, Kardex, Historial de precios | `Locations/`, `Inventory/`, `Kardex/`, `PriceHistory/` |
| **Abastecimiento** | Proveedores, Compras, Recepciones | `Suppliers/`, `Purchases/`, `Receptions/` |
| **Comercial** | Ventas, Cotizaciones, Créditos, Cobros | `Sales/`, `Quotations/`, `Credits/`, `Payments/` |
| **Finanzas / BI** | Caja, Reportes | `Cash/`, `Reports/` |

**» Justificación:** agrupar por dominio (no por tipo de archivo) hace navegable un sistema grande y define **fronteras** claras: el dominio Comercial consume servicios de Inventario (para descontar stock) pero no toca sus tablas directamente.

---

## 5. Convenciones de nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Tablas | `snake_case` plural, **inglés** | `stock_movements`, `purchase_items` |
| Pivot | orden alfabético singular | `product_attribute_value` |
| Columnas | `snake_case` | `unit_cost`, `with_invoice`, `created_by` |
| FK | `{singular}_id` | `product_id`, `cash_session_id` |
| PK | `id` (bigint) | — |
| Booleanas | prefijo `is_`/`with_`/`has_` | `is_active`, `with_invoice` |
| Modelos | `PascalCase` singular | `StockMovement`, `PurchaseItem` |
| Services | `{Dominio}Service` | `InventoryService` |
| Repositories | `{Modelo}Repository` + `{Modelo}RepositoryInterface` | `KardexRepository` |
| Enums | `PascalCase`, casos `PascalCase` | `SaleStatus::Completed` |
| Events / Listeners | verbo pasado / verbo presente | `SaleConfirmed` / `RecordKardexMovement` |
| Componentes Livewire | `PascalCase` por acción | `Products\ProductForm`, `Sales\SalePos` |
| Rutas (name) | `dominio.recurso.accion` | `sales.create`, `inventory.kardex` |
| Permisos | `recurso.accion` | `prices.update`, `cash.close` |
| Variables/métodos | `camelCase` | `baseQuantity()`, `postMovement()` |

**Idioma:** **código y BD en inglés** (convención Laravel, evita fricción con el framework y paquetes); **UI/labels/PDF en español**. Traducciones en `lang/es`.

---

## 6. Estándares de código

- **PSR-12** + **Laravel Pint** (formateo automático, corre en pre-commit y CI).
- **PHPStan / Larastan nivel 6+** (análisis estático; sube de nivel gradualmente).
- **Tipado estricto**: `declare(strict_types=1);`, type hints y return types en todo; **enums** para estados; sin `mixed`/`getattr`-style.
- **Inmutabilidad** donde importe: Value Objects (`Money`, `Quantity`) inmutables; `stock_movements`/`price_histories` append-only.
- **DB en transacciones** para toda operación multi-tabla; nunca escritura parcial.
- **Sin lógica en Blade**; sin queries en Livewire; **N+1** evitado con eager loading (`with`).
- **Commits**: Conventional Commits (`feat:`, `fix:`, `refactor:`); ramas `feature/fase-X-modulo`.
- **Tests** en flujos críticos (no cobertura 100%): recepción actualiza stock, venta con lock, cobro reduce crédito, arqueo cuadra, autorización por rol.
- **Pre-commit hooks**: Pint + PHPStan + tests rápidos.
- **CI** (GitHub Actions): setup PHP 8.4, `composer install`, migraciones sobre MySQL/sqlite, Pint `--test`, PHPStan, PHPUnit/Pest.

---

## 7. Modelo Entidad-Relación (completo)

> Detalle de columnas/tipos en `PLAN_ARQUITECTURA.md §3`. Aquí, el mapa consolidado de ~35 tablas por dominio.

**Seguridad:** `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.

**Catálogo:** `categories`(self parent_id), `brands`, `units`, `products`(uuid, code, barcode, category_id, brand_id, unit_id, min_stock), `presentations`(product_id, equivalence, price_without_invoice, price_with_invoice, sort_order), `attributes`, `attribute_values`(attribute_id), `product_attribute_value`(pivot), `product_images`(product_id, path, is_primary).

**Inventario:** `locations`, `inventory`(product_id, location_id, quantity, reserved_quantity; UNIQUE par), `stock_movements`(product_id, location_id, type, direction, quantity, unit_cost, balance_after, morph reference, created_by).

**Abastecimiento:** `suppliers`, `purchases`(supplier_id, status, payment_type, total), `purchase_items`(purchase_id, product_id, quantity_ordered, quantity_received, unit_cost), `receptions`(purchase_id, location_id, received_at), `reception_items`(reception_id, purchase_item_id, product_id, quantity, unit_cost).

**Comercial:** `customers`(type registered/occasional, credit_limit), `quotations`(customer_id, with_invoice, status, valid_until), `quotation_items`, `sales`(uuid, customer_id, quotation_id, with_invoice, payment_type, status, location_id, cash_session_id), `sale_items`(product_id, presentation_id, quantity, base_quantity, unit_price), `credits`(customer_id, sale_id, original_amount, paid_amount, balance, status, due_date), `credit_payments`(credit_id, amount, method, cash_session_id).

**Finanzas/BI:** `cash_registers`, `cash_sessions`(opening/closing/counted/difference, status), `cash_movements`(cash_session_id, type, method, amount, morph reference).

**Transversal:** `price_histories`(morph priceable, field, old/new, changed_by), `audits`(morph auditable, user, event, old/new json, ip), `settings`, `jobs`/`failed_jobs`.

### 7.1 Reglas de integridad clave
- `inventory` UNIQUE(product_id, location_id); solo lo escribe `InventoryService`.
- `stock_movements` inmutable; corrección = movimiento de ajuste nuevo.
- `purchases.status` derivado de la suma de `purchase_items.quantity_received`.
- `credits.balance = original_amount − paid_amount`; nunca negativo.
- `sale_items.base_quantity = quantity × presentation.equivalence` (congelado).
- Precios en documentos **congelados** al momento; cambios solo en `presentations` + `price_histories`.

---

## 8. Diagrama de relaciones entre módulos

```
                         ┌─────────────┐
                         │  SEGURIDAD  │  (users, roles, permisos, auditoría)
                         └──────┬──────┘
        Policies/permisos gobiernan todos los módulos ▼ (transversal)
 ┌───────────┐   define productos   ┌───────────────┐
 │ CATÁLOGO  │─────────────────────►│  INVENTARIO   │◄────────── Kardex (fuente de verdad de stock)
 │(productos,│                      │(ubicaciones,  │
 │ present., │◄── precios/hist. ────│ stock_moves)  │
 │ atributos)│                      └──┬────────┬───┘
 └─────┬─────┘             (+) genera IN │        │ (−) genera OUT
       │                                 │        │
       ▼                        ┌────────┴───┐ ┌──┴──────────┐
 ┌──────────────┐  recepción    │ ABASTECIM. │ │  COMERCIAL  │
 │  PRECIOS     │  actualiza     │(proveedor, │ │(ventas,     │
 │ (historial)  │  stock         │ compras,   │ │ cotización, │
 └──────────────┘                │ recepción) │ │ créditos)   │
                                  └────────────┘ └──────┬──────┘
                                                        │ cobros/ventas
                                                        ▼
                                                 ┌─────────────┐
                                                 │  FINANZAS   │ (caja: ingresos,
                                                 │  (caja)     │  egresos, cobros)
                                                 └──────┬──────┘
                                                        ▼
                                                 ┌─────────────┐
                                                 │  REPORTES   │ (lee de todos)
                                                 └─────────────┘
```

**Flujos clave (quién dispara qué):**
- **Recepción** → `InventoryService.postMovement(IN)` → `stock_movements` + `inventory` + `purchase.status`.
- **Venta confirmada** → `InventoryService.postMovement(OUT)` (con lock) → si crédito, crea `Credit`; si contado, `cash_movement`.
- **Cobro** → reduce `Credit.balance` + `cash_movement`.
- **Cambio de precio** → `PriceService` → `price_histories` (append-only) vía evento.
- **Cualquier acción marcada** → `WriteAuditLog` (evento).

**» Regla de dependencia:** Comercial y Abastecimiento **dependen de** Inventario (vía Service), nunca al revés. Reportes solo lee. Seguridad es transversal.

---

## 9. Riesgos técnicos

| # | Riesgo | Mitigación |
|---|---|---|
| R1 | Carrera de stock (ventas simultáneas) → sobreventa | `DB::transaction` + `lockForUpdate()` sobre fila `inventory`; validación dentro del lock. |
| R2 | Saldo cacheado ↔ Kardex desincronizado | Escritura única vía `InventoryService`; comando `inventory:rebuild`; test de consistencia. |
| R3 | Redondeo monetario / descuadre caja | `decimal` en BD; Value Object `Money` en centavos; nunca `float`. |
| R4 | Doble precio (con/sin factura) mal aplicado | Flujo obliga elegir "¿con factura?" antes de vender; precio congelado por ítem. |
| R5 | Cambio de precio/equivalencia altera históricos | Congelar `unit_price`/`base_quantity`; historial append-only. |
| R6 | Estados de compra mal recalculados (multi-recepción) | Recalculo transaccional comparando ordenado vs recibido. |
| R7 | Permisos exponen costos/precios | Policies + ocultar columnas de costo por permiso; tests por rol. |
| R8 | Borrado de catálogo con historial | SoftDeletes + `restrictOnDelete`. |
| R9 | PHP 8.4 / Laravel 12 muy nuevos → paquetes incompatibles | Fijar versiones verificadas de Livewire 3 y Spatie; validar en Fase 0. |
| R10 | Auditoría pesada por request | Vía eventos + `ShouldQueue` al activar cola; solo entidades marcadas. |
| R11 | Crecimiento de `stock_movements`/`audits` | Índices adecuados; particionado/archivado a futuro (§10). |
| R12 | Impresión (drivers de impresora térmica) | Generar PDF/HTML imprimible; impresión térmica ESC/POS como fase opcional (§16). |

---

## 10. Recomendaciones de escalabilidad

- **Lecturas O(1) de stock** por el saldo cacheado (no sumar Kardex en cada pantalla).
- **Índices**: `products(barcode)`, `products(code)`, `stock_movements(product_id, location_id, created_at)`, `sales(created_at)`, `customers(document_number)`.
- **Paginación** en todas las listas (nunca `all()`); `select` de columnas necesarias; eager loading para evitar N+1.
- **Cache** de KPIs de dashboard y catálogos poco cambiantes (marcas, unidades).
- **Queue** para PDF pesados, auditoría y alertas cuando el volumen crezca (infra ya lista).
- **Búsqueda**: scope indexado ahora; Laravel Scout + Meilisearch si el catálogo supera decenas de miles de SKUs.
- **Archivado**: estrategia futura para mover `stock_movements`/`audits` antiguos a tablas históricas o particiones por año.
- **Storage**: disco `public` hoy; abstracción de Storage permite pasar a S3/CDN sin tocar código de negocio.
- **Read replicas de MySQL** para reportes si la carga lo exige (config de conexión de lectura/escritura).
- El monolito modular puede extraerse a servicios más adelante gracias a las fronteras por dominio, si alguna vez fuera necesario (no se prevé).

---

## 11. Estrategia de auditoría

Requerimiento: registrar **usuario, fecha, acción, IP, antes, después**.

**Recomendación: `owen-it/laravel-auditing`** (maduro, morphable) sobre las entidades sensibles (productos, precios, ventas, compras, recepciones, créditos, caja, usuarios/roles).
- Captura automática de `old_values`/`new_values`, `user_id`, `ip_address`, `user_agent`, `url`, `created_at`.
- Auditoría también de acciones "de negocio" (no solo CRUD): eventos custom (`sale.cancelled`, `price.changed`, `cash.closed`) registrados manualmente.
- Ejecutable en cola (`ShouldQueue`) cuando el volumen lo pida.
- UI de solo lectura en módulo **Auditoría**: filtro por usuario, entidad, rango de fechas, acción; vista diff antes/después.

**Alternativa** (si prefieres cero dependencias): tabla `audits` manual + trait `HasAudit` + listener `WriteAuditLog`. Funciona pero reimplementa lo que el paquete ya resuelve. **Pendiente de tu decisión.**

---

## 12. Estrategia de manejo de imágenes

- **Laravel Storage**, disco `public` (symlink), ruta `products/{product_id}/{uuid}.{ext}`. Migrable a S3 sin cambios de código.
- **Múltiples imágenes por producto** (`product_images`) con `is_primary` y `sort_order`; una sola primaria (garantizado en Service).
- **Validación**: mime (`jpg`,`png`,`webp`), tamaño máx configurable, dimensiones.
- **Optimización**: `intervention/image` para redimensionar y generar **thumbnail** (lista) + imagen media (detalle); conversión a WebP para peso.
- **Borrado**: al eliminar imagen/producto (soft delete del producto conserva archivos; borrado físico opcional vía comando).
- **Presentación**: no maneja imágenes propias (usa las del producto).
- Servir vía Storage URL; a futuro CDN.

---

## 13. Estrategia de control de inventario

**Núcleo del sistema.**

1. **Unidad base + presentaciones**: el stock se lleva SIEMPRE en unidad base; ventas/compras en presentación se convierten con `equivalence` (`UnitConverter`).
2. **Stock por ubicación** (`inventory` = producto × ubicación). No hay número global mutable; el total es la suma por ubicación.
3. **Kardex append-only** (`stock_movements`) = fuente de verdad. Cada movimiento guarda `type`, `direction`, `quantity` (base), `unit_cost`, `balance_after`, y morph al documento origen.
4. **Saldo cacheado** en `inventory.quantity`, actualizado en la MISMA transacción que el movimiento; reconstruible con `inventory:rebuild`.
5. **Tipos de movimiento**: `purchase_reception` (IN), `sale` (OUT), `transfer_in`/`transfer_out` (entre ubicaciones), `adjustment` (±), `customer_return` (IN), `supplier_return` (OUT).
6. **Regla de oro**: **Compras NO mueven stock; las Recepciones sí.** Una compra puede tener múltiples recepciones (parcial → completa).
7. **Concurrencia**: `lockForUpdate()` sobre `inventory` dentro de la transacción antes de validar/descontar → evita stock negativo por ventas simultáneas.
8. **Costeo**: **Costo Promedio Ponderado (CPP)** recalculado en cada recepción → base para margen/valorización (recomendado; pendiente de tu confirmación vs. último costo).
9. **Alertas**: evento `StockBelowMinimum` cuando `quantity < min_stock` → notificación/dashboard.
10. **Transferencias**: generan un OUT en origen y un IN en destino, atómicos.

`InventoryService.postMovement(...)` es el **único** punto de escritura de stock; toda la app pasa por ahí.

---

## 14. Estrategia de precios

- **Doble precio por presentación**: `price_without_invoice` y `price_with_invoice`. El flujo de venta obliga a elegir "¿con factura?" y usa el precio correspondiente.
- **Solo Administrador y Gerente** modifican precios/costos (permiso `prices.update`, reforzado por Policy). Vendedor/Almacenero no.
- **Historial completo append-only** (`price_histories`, morph a presentación/producto, campo, old/new, motivo, usuario). **Nunca se sobrescribe** — cada cambio es un registro nuevo, disparado por `PriceChanged`.
- **Precio sugerido por margen**: `MarginCalculator` propone `precio = costo × (1 + margen)` con margen por defecto en `settings`; **el Administrador decide y confirma** — nunca automático.
- **Precios congelados** en `sale_items`/`quotation_items` al momento de la operación (cambios futuros no alteran históricos).
- **Cotización → venta**: hereda precios de la cotización sin reescribir (opción de refrescar si expiró).

---

## 15. Estrategia de créditos

- **Clientes**: `registered` (frecuentes, datos completos, `credit_limit`) vs `occasional` (solo nombre; sin crédito o crédito limitado).
- **Crédito por venta** (`credits` 1:1 con `sale`): `original_amount`, `paid_amount`, `balance`, `status` (`open`/`partial`/`paid`/`overdue`), `due_date`.
- **Cobros parciales** (`credit_payments`): cada cobro reduce `balance`, actualiza `status`, y si entra por caja genera `cash_movement` (método efectivo/QR/transferencia). Historial completo de pagos.
- **Límite de crédito**: validación al vender a crédito (no superar `credit_limit` disponible del cliente registrado).
- **Alertas de vencimiento**: comando `credits:mark-overdue` (programado) marca `overdue` y dispara alertas; dashboard muestra próximos a vencer.
- **"Precio pendiente por producto"**: interpretado como ítem de venta cuyo precio queda por definir/cobrar dentro del crédito (flag `price_pending` en `sale_items`). **Pendiente de tu confirmación de significado exacto.**

---

## 16. Estrategia de impresión

- **Generación de documentos**: HTML imprimible (Blade en `resources/views/pdf`) + PDF con **`barryvdh/laravel-dompdf`** (o `spatie/laravel-pdf` si se requiere fidelidad con navegador). Documentos: comprobante de venta, cotización, orden de compra, comprobante de recepción, reporte de arqueo de caja, estado de cuenta de crédito.
- **QR**: `simplesoftwareio/simple-qrcode` — QR de venta (a partir del `uuid`) para verificación/consulta en caja; también método de cobro QR.
- **Formatos**: A4 para documentos administrativos; **ticket 58/80 mm** para punto de venta (plantilla dedicada). Impresión térmica ESC/POS directa (`mike42/escpos-php`) como **fase opcional** (depende de hardware disponible).
- **Numeración**: `DocumentNumberGenerator` con series por tipo de documento, correlativos, configurables en `settings`.
- **Impresión**: por diálogo del navegador (HTML `@media print`) para lo básico; PDF descargable/adjuntable; térmica directa a definir según impresora real de la ferretería.

---

## 17. Roadmap técnico

| Fase | Entregable | Contenido |
|---|---|---|
| **0. Bootstrap** | Base ejecutable + CI | Proyecto Laravel 12, Livewire, Tailwind, Spatie; auth; roles/permisos + seeders; `settings`; layout; Pint + PHPStan + tests en CI; pre-commit. |
| **1. Catálogo** | Gestión de productos | Unidades, Marcas, Categorías, Atributos/Valores, Productos (código auto, barcode, imágenes múltiples, presentaciones, atributos dinámicos). |
| **2. Inventario** | Núcleo de stock | Ubicaciones, `InventoryService` (lock+transacción), Kardex, Ajustes, Transferencias, `inventory:rebuild`, alertas de mínimo. |
| **3. Abastecimiento** | Compras y recepciones | Proveedores, Compras (crédito, historial de costos, sugerencia último proveedor), Recepciones (mueven stock), estados parcial/completa, CPP. |
| **4. Comercial – Ventas** | Punto de venta | Flujo "¿con factura?", búsqueda multi-criterio (nombre/código/barcode/marca/medida), doble precio, descuento de stock con lock, cotizaciones y conversión a venta. |
| **5. Créditos y cobros** | Cuenta corriente | Créditos, cobros parciales, historial de pagos, límite y alertas de vencimiento. |
| **6. Caja** | Operación financiera | Apertura/cierre/arqueo, ingresos/egresos, cobros, métodos efectivo/QR. |
| **7. Precios** | Gobierno de precios | Historial append-only, precio sugerido por margen, permisos Admin/Gerente, Policies. |
| **8. Reportes + Auditoría + Dashboard** | BI y control | Reportes (inventario, ventas, compras, más vendidos, clientes, créditos, caja), auditoría con diff, dashboard con KPIs. |
| **9. Impresión** (transversal, se integra desde Fase 4) | Documentos | PDF/HTML/ticket, QR, numeración; térmica ESC/POS opcional. |

Cada fase = una rama + **un PR revisable** con migraciones, seeders, Services, Livewire y tests del núcleo. **Nada de "todo de una vez".**

---

## Decisiones pendientes (necesito tu confirmación antes de programar)

1. **Primer PR = Fase 0** (bootstrap + auth + roles/permisos + estructura + CI) y luego fase por fase. (Recomendado)
2. **Auditoría**: `owen-it/laravel-auditing` (recomendado) vs. tabla manual.
3. **"Precio pendiente por producto"**: ¿confirmas la interpretación (ítem con precio por definir/cobrar dentro del crédito)?
4. **Costeo**: Costo Promedio Ponderado (recomendado) vs. último costo.
5. **"Con factura"**: ¿emisión fiscal real (integración tributaria) o solo marca de precio/registro interno?
6. **Impresión térmica ESC/POS**: ¿hay impresora térmica específica? ¿la incluimos o dejamos solo PDF/HTML por ahora?

Dime "adelante con lo recomendado" o ajusta lo que quieras, y arranco con la **Fase 0**.
```
