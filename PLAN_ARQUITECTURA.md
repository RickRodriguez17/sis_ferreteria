# Plan de Arquitectura — ERP "Construir a tu Alcance"

> Documento de diseño previo a la programación.
> Stack: **Laravel 12 · PHP 8.4 · MySQL · Livewire 3 · TailwindCSS · Spatie Permission · Policies · Storage · Queue**.
> Enfoque: ERP especializado en **control de inventario** para ferretería, no un POS genérico.

---

## 0. Cómo leer este documento

Este plan cubre, en orden, lo que pediste:

1. Análisis del sistema y decisiones transversales.
2. Arquitectura por capas.
3. Modelo ER (entidades + atributos).
4. Relaciones.
5. Estructura de carpetas.
6. Migraciones (orden y contenido).
7. Modelos (Eloquent) y sus responsabilidades.
8. Riesgos.
9. Mejoras propuestas.
10. Plan de desarrollo por fases (recién aquí empieza el código, tras tu aprobación).

Cada decisión relevante lleva una justificación breve marcada con **» Justificación**.

---

## 1. Análisis del sistema

### 1.1 Naturaleza del negocio

Una ferretería tiene características que la diferencian de un comercio genérico y que condicionan todo el diseño:

- **Producto vendido en varias unidades / presentaciones** (ej.: cemento por bolsa o por pallet; cable por metro o por rollo; tornillos por unidad, por caja o por kilo). El inventario se lleva en una **unidad base** y las presentaciones son múltiplos/fracciones de esa base.
- **Doble precio**: "sin factura" y "con factura" por presentación. Esto es una realidad operativa del negocio (informalidad/formalidad tributaria) y debe modelarse explícitamente.
- **Stock físicamente distribuido**: Patio, Muestrario, Depósito. El control debe ser **por ubicación**, no un único número global.
- **Compra ≠ Recepción**: se ordena mercadería que llega en partes. El stock lo mueve la **recepción**, nunca la compra. Esto obliga a separar el documento comercial (compra) del hecho físico (recepción).
- **Crédito**: clientes frecuentes con cuenta corriente, cobros parciales, vencimientos.
- **Trazabilidad total**: Kardex por producto/ubicación + Auditoría de acciones de usuario.

**» Justificación global:** el corazón del sistema NO es la venta, es el **inventario y su trazabilidad**. Por eso el diseño gira alrededor de `stock_movements` (Kardex) como única fuente de verdad de las existencias, y todos los documentos (recepción, venta, transferencia, ajuste, devolución) generan movimientos, nunca escriben el stock "a mano".

### 1.2 Principio de diseño central: Stock derivado de movimientos

Existen dos estrategias:

- **A) Stock como columna mutable** (`inventory.quantity` que se suma/resta). Rápido de leer, pero frágil: cualquier bug corrompe el número y no hay forma de auditar.
- **B) Stock derivado del Kardex** (suma de `stock_movements`). Auditable y reconstruible, pero costoso de calcular en cada lectura.

**Decisión: enfoque híbrido.**
- `stock_movements` es la **fuente de verdad append-only** (nunca se edita ni borra; una corrección es un movimiento nuevo).
- `inventory` (producto + ubicación) mantiene un **saldo cacheado** que se actualiza **dentro de la misma transacción** que inserta el movimiento.
- El saldo cacheado debe poder **reconstruirse** desde el Kardex con un comando (`inventory:rebuild`).

**» Justificación:** obtenemos lecturas O(1) para pantallas de venta/stock y, a la vez, integridad y auditabilidad. La regla de oro: *nadie modifica `inventory` fuera del servicio de inventario*.

### 1.3 Decisiones transversales

| Tema | Decisión | Justificación |
|---|---|---|
| **IDs** | `bigIncrements` (autoincremental) para entidades operativas. **UUID** solo en: `products` (columna `uuid` adicional, para URLs/QR y APIs futuras), documentos con exposición externa (ventas/cotizaciones para QR de caja) y `audits`. | UUID en todo penaliza índices y joins en MySQL; se usa donde aporta (exposición externa, no adivinable). El PK sigue siendo `bigint` para rendimiento. |
| **Dinero** | `decimal(14,2)` para importes; `decimal(14,4)` para costos unitarios y equivalencias. Nunca `float`. | Evita errores de redondeo. Cantidades con 4 decimales para fracciones (metros, kilos). |
| **Soft deletes** | En catálogos (products, categories, brands, customers, suppliers, users). **NO** en documentos ni en `stock_movements`. | Los documentos y el Kardex son inmutables por auditoría; se anulan con estado, no se borran. |
| **Estados** | Enums PHP 8.1+ (`enum ... : string`) respaldados por columnas string, castificados en el modelo. | Type-safety, sin tablas de catálogo triviales, legible en BD. |
| **Multi-tenant** | No. Una sola empresa, una sola caja (según requerimiento). | Se evita complejidad innecesaria; el diseño no lo impide a futuro. |
| **Timestamps + actor** | `created_by` / `updated_by` en documentos. | Trazabilidad de quién generó cada documento. |
| **Zona horaria / moneda** | Configurable en `config` + tabla `settings`. | Producción real requiere configuración, no hardcode. |

### 1.4 Roles y permisos (Spatie)

Roles base:
- **Administrador**: todo, incluido precios y configuración.
- **Gerente**: casi todo, puede modificar precios, no toca configuración de sistema/usuarios sensibles.
- **Vendedor**: ventas, cotizaciones, cobros, consulta de stock. **No** modifica precios ni costos.
- **Almacenero**: recepciones, transferencias, ajustes, inventario. **No** vende ni ve costos financieros sensibles.
- **Cajero**: caja (apertura/cierre/arqueo), cobros, ingresos/egresos.

Permisos granulares por módulo y acción (`products.view`, `products.create`, `prices.update`, `cash.close`, etc.), agrupados y asignados a roles vía seeder. Autorización efectiva vía **Policies** (una por entidad crítica) que consultan los permisos Spatie.

**» Justificación:** Spatie da el "qué puede" (permiso); las Policies dan el "puede sobre este recurso concreto y bajo estas reglas de negocio" (ej.: un vendedor solo anula su propia venta del día). Separar ambos evita meter lógica de negocio en middleware.

---

## 2. Arquitectura por capas

```
Livewire Component (UI/estado de pantalla)
        │  (valida con Form Request rules / Rule objects)
        ▼
Service (orquesta caso de uso, TRANSACCIONES, dispara eventos)
        │
        ├─► Repository (acceso a datos complejo/reutilizable)  ── opcional
        ├─► Support/Domain (cálculos: márgenes, equivalencias, saldos)
        ▼
Eloquent Model (persistencia, relaciones, casts, scopes)
        │
        ▼
MySQL
```

**Reglas de la arquitectura:**

- **Componentes Livewire**: solo estado de UI y delegación. Nada de lógica de negocio ni queries complejas dentro del componente. Validación con reglas reutilizables (Form Request o clases de reglas).
- **Services**: una clase por caso de uso complejo (`SaleService`, `ReceptionService`, `InventoryService`, `CashService`, `PriceService`, `CreditService`). Aquí viven las **transacciones DB** y la coordinación entre entidades. Un Service puede orquestar varios repositorios/modelos.
- **Repositories**: **solo cuando aportan** (consultas complejas reutilizables, p. ej. `KardexRepository`, `InventoryRepository`, reportes). Para CRUD simple se usa Eloquent directo — no se crea un repositorio por tabla (eso sería sobre-ingeniería y va contra "código limpio", no a favor).
- **Events/Listeners**: donde desacoplan de verdad — `SaleConfirmed`, `ReceptionPosted`, `PriceChanged`, `CreditPaymentRegistered`, `StockBelowMinimum`. Listeners: registrar Kardex, historial de precios, alertas, auditoría. Preparados para cola (`ShouldQueue`) sin activarla aún.
- **Policies**: autorización por recurso.
- **Form Requests**: validación de entrada (en controladores REST futuros y como fuente de reglas compartidas con Livewire).

**» Justificación:** este layering respeta SOLID (cada capa una responsabilidad), permite testear Services sin UI, y deja Livewire delgado y mantenible. Los Repositories se limitan a donde eliminan duplicación real, evitando el anti-patrón "un repositorio por modelo".

---

## 3. Modelo ER (entidades y atributos)

Notación: `PK` clave primaria, `FK` foránea, `U` único, `N` nullable, `I` indexado.

### 3.1 Seguridad / acceso
- **users**: id PK, name, email U, password, is_active, remember_token, timestamps, softDeletes.
- **roles / permissions / model_has_roles / model_has_permissions / role_has_permissions**: tablas estándar de **Spatie Permission**.

### 3.2 Catálogo de productos
- **categories**: id PK, name, slug U, parent_id N FK→categories (jerarquía), is_active, timestamps, softDeletes.
- **brands**: id PK, name U, is_active, timestamps, softDeletes.
- **units**: id PK, name, abbreviation U (ej. "und", "m", "kg"), is_active, timestamps. *(Unidad base del producto.)*
- **products**: id PK, uuid U, code U (autogenerado si falta), barcode N U I, name, description N, category_id FK, brand_id FK, unit_id FK (unidad base), min_stock decimal(14,4) N, is_active, created_by/updated_by, timestamps, softDeletes.
- **presentations**: id PK, product_id FK, name, equivalence decimal(14,4) (cuántas unidades base = 1 presentación), price_without_invoice decimal(14,2), price_with_invoice decimal(14,2), is_active, sort_order int. *(Sin código de barras por presentación, según requerimiento.)*
- **attributes**: id PK, name U (ej. "Color", "Medida", "Material"), is_active.
- **attribute_values**: id PK, attribute_id FK, value (ej. "Rojo", "1/2 pulgada"). U(attribute_id, value).
- **product_attribute_value**: id PK, product_id FK, attribute_value_id FK. U(product_id, attribute_value_id). *(Atributos dinámicos por producto.)*
- **product_images**: id PK, product_id FK, path, disk, is_primary, sort_order, timestamps. *(Varias imágenes vía Laravel Storage.)*

### 3.3 Inventario y Kardex
- **locations**: id PK, name U (Patio, Muestrario, Depósito), code N, is_active, is_default, timestamps.
- **inventory**: id PK, product_id FK, location_id FK, quantity decimal(14,4) (**saldo cacheado**), reserved_quantity decimal(14,4) default 0, timestamps. **U(product_id, location_id)**.
- **stock_movements** (Kardex, append-only): id PK, product_id FK, location_id FK, type (enum: `purchase_reception`, `sale`, `transfer_in`, `transfer_out`, `adjustment`, `customer_return`, `supplier_return`), direction (enum: `in`/`out`), quantity decimal(14,4) (siempre positiva), unit_cost decimal(14,4) N, balance_after decimal(14,4) (saldo tras el movimiento), reference_type/reference_id (**morph** al documento origen: recepción, venta, transferencia, ajuste), notes N, created_by FK, created_at I. Índices: (product_id, location_id, created_at), (reference_type, reference_id).

**» Justificación del Kardex morphable:** cada movimiento apunta polimórficamente a su documento origen (venta #123, recepción #45…), permitiendo trazar "por qué se movió este stock" sin FKs duplicadas por tipo. `balance_after` permite reportes de Kardex con saldo corriente sin recalcular toda la historia.

### 3.4 Compras y recepciones
- **suppliers**: id PK, name, document_type N, document_number N U, phone N, email N, address N, is_active, timestamps, softDeletes.
- **purchases**: id PK, code U, supplier_id FK, status (enum: `pending`, `partial`, `completed`, `cancelled`), payment_type (enum: `cash`, `credit`), total decimal(14,2), expected_date N, notes N, created_by FK, timestamps.
- **purchase_items**: id PK, purchase_id FK, product_id FK, quantity_ordered decimal(14,4), quantity_received decimal(14,4) default 0, unit_cost decimal(14,4), subtotal decimal(14,2). *(quantity_received se acumula desde recepciones; define el estado de la compra.)*
- **receptions**: id PK, code U, purchase_id FK, location_id FK (a qué ubicación entra), received_at, notes N, created_by FK, timestamps.
- **reception_items**: id PK, reception_id FK, purchase_item_id FK, product_id FK, quantity decimal(14,4), unit_cost decimal(14,4). *(Al confirmar recepción → genera stock_movements IN + actualiza inventory + actualiza purchase_items.quantity_received + recalcula purchases.status.)*

### 3.5 Clientes, ventas, cotizaciones
- **customers**: id PK, type (enum: `registered`, `occasional`), name, document_type N, document_number N I, phone N, email N, address N, credit_limit decimal(14,2) N, is_active, timestamps, softDeletes. *(Ocasionales: solo name obligatorio.)*
- **quotations**: id PK, code U, customer_id N FK, with_invoice bool, status (enum: `open`, `converted`, `expired`, `cancelled`), valid_until N, subtotal/total decimal(14,2), created_by FK, timestamps.
- **quotation_items**: id PK, quotation_id FK, product_id FK, presentation_id N FK, quantity decimal(14,4), unit_price decimal(14,2), subtotal decimal(14,2).
- **sales**: id PK, uuid U (para QR), code U, customer_id N FK, quotation_id N FK (si vino de cotización), with_invoice bool, payment_type (enum: `cash`, `credit`, `mixed`), status (enum: `completed`, `cancelled`), subtotal/discount/total decimal(14,2), location_id FK (de dónde sale el stock), cash_session_id N FK, created_by FK, timestamps.
- **sale_items**: id PK, sale_id FK, product_id FK, presentation_id N FK, quantity decimal(14,4), base_quantity decimal(14,4) (cantidad convertida a unidad base = quantity × equivalence, para el Kardex), unit_price decimal(14,2), subtotal decimal(14,2).

**» Justificación de `base_quantity`:** la venta se hace en presentación (2 cajas) pero el inventario se mueve en unidad base (2 × 100 = 200 unidades). Guardar ambas evita recalcular y da trazabilidad exacta de la equivalencia usada al momento de la venta (aunque la presentación cambie después).

### 3.6 Créditos y cobros
- **credits**: id PK, customer_id FK, sale_id FK U, original_amount decimal(14,2), paid_amount decimal(14,2) default 0, balance decimal(14,2), status (enum: `open`, `partial`, `paid`, `overdue`), due_date N, timestamps.
- **credit_payments** (cobros): id PK, credit_id FK, amount decimal(14,2), method (enum: `cash`, `qr`, `transfer`), cash_session_id N FK, paid_at, notes N, created_by FK, timestamps. *(Cada cobro → reduce balance, actualiza status, y si entra por caja genera cash_movement.)*

**» Nota sobre "precio pendiente por producto":** interpreto el requerimiento como que un ítem de venta puede quedar con saldo por definir/cobrar. Se modela con `sale_items.price_pending` (bool) + el crédito a nivel venta. **Confirmar contigo** en el punto de preguntas.

### 3.7 Caja
- **cash_registers**: id PK, name, is_active. *(Una sola caja según requerimiento, pero tabla lista para más.)*
- **cash_sessions**: id PK, cash_register_id FK, opened_by FK, closed_by N FK, opening_amount decimal(14,2), closing_amount N decimal(14,2), counted_amount N decimal(14,2) (arqueo), difference N decimal(14,2), status (enum: `open`, `closed`), opened_at, closed_at N.
- **cash_movements**: id PK, cash_session_id FK, type (enum: `income`, `expense`, `sale`, `credit_payment`), method (enum: `cash`, `qr`, `transfer`), amount decimal(14,2), reference_type/reference_id N (morph: venta, cobro), description N, created_by FK, created_at.

### 3.8 Precios, reportes, auditoría, config
- **price_histories**: id PK, priceable_type/priceable_id (morph: presentation o product), field (enum: `price_with_invoice`, `price_without_invoice`, `cost`), old_value decimal(14,4) N, new_value decimal(14,4), reason N, changed_by FK, created_at. **Append-only, nunca se sobrescribe.**
- **audits**: id PK, uuid, user_id N FK, event (created/updated/deleted/custom), auditable_type/auditable_id (morph), old_values json N, new_values json N, ip_address, user_agent N, url N, created_at I. *(Se evaluará usar `owen-it/laravel-auditing`; ver Mejoras.)*
- **settings**: id PK, key U, value, type. *(Config editable: moneda, IGV/IVA, margen sugerido por defecto, datos de la empresa, días de vencimiento por defecto.)*
- **jobs / failed_jobs**: infraestructura de Queue (preparada, no activada).

---

## 4. Relaciones (resumen)

- `Product` **belongsTo** Category, Brand, Unit; **hasMany** Presentation, ProductImage, StockMovement; **belongsToMany** AttributeValue (pivot `product_attribute_value`); **hasMany** Inventory.
- `Category` **hasMany** self (children) / **belongsTo** self (parent).
- `Attribute` **hasMany** AttributeValue.
- `Location` **hasMany** Inventory, StockMovement.
- `Inventory` **belongsTo** Product, Location. *(Único por par.)*
- `StockMovement` **belongsTo** Product, Location, creator; **morphTo** reference.
- `Supplier` **hasMany** Purchase.
- `Purchase` **belongsTo** Supplier; **hasMany** PurchaseItem, Reception.
- `Reception` **belongsTo** Purchase, Location; **hasMany** ReceptionItem.
- `ReceptionItem` **belongsTo** Reception, PurchaseItem, Product.
- `Customer` **hasMany** Sale, Quotation, Credit.
- `Quotation` **belongsTo** Customer; **hasMany** QuotationItem; **hasOne** Sale (conversión).
- `Sale` **belongsTo** Customer, Quotation, Location, CashSession, creator; **hasMany** SaleItem; **hasOne** Credit; **morphMany** StockMovement (reference).
- `Credit` **belongsTo** Customer, Sale; **hasMany** CreditPayment.
- `CashSession` **belongsTo** CashRegister; **hasMany** CashMovement.
- `PriceHistory` **morphTo** priceable; **belongsTo** changer (user).
- `Audit` **belongsTo** user; **morphTo** auditable.

---

## 5. Estructura de carpetas

```
app/
├── Actions/                 # acciones atómicas puntuales (opcional)
├── Console/Commands/        # inventory:rebuild, credits:mark-overdue
├── Domain/                  # lógica pura de dominio, sin Eloquent
│   ├── Inventory/           # cálculo de saldos, conversiones de unidad
│   ├── Pricing/             # cálculo de margen / precio sugerido
│   └── Enums/               # StockMovementType, SaleStatus, PurchaseStatus...
├── Events/                  # SaleConfirmed, ReceptionPosted, PriceChanged...
├── Exceptions/              # InsufficientStockException, CashSessionClosedException...
├── Http/
│   ├── Requests/            # Form Requests (reglas compartidas)
│   └── Controllers/         # mínimos (auth, exports, webhooks futuros)
├── Listeners/               # RecordKardex, RecordPriceHistory, SendStockAlert...
├── Livewire/
│   ├── Dashboard/
│   ├── Products/  Categories/  Brands/  Units/  Attributes/
│   ├── Inventory/ Locations/ Kardex/
│   ├── Purchases/ Receptions/ Suppliers/
│   ├── Sales/ Quotations/
│   ├── Credits/ Payments/
│   ├── Cash/
│   ├── Reports/
│   ├── Users/ Roles/
│   └── Audit/
├── Models/
├── Policies/
├── Providers/
├── Repositories/            # solo los que aportan (Kardex, Inventory, Reports)
│   └── Contracts/           # interfaces
├── Services/                # InventoryService, SaleService, ReceptionService,
│   │                        # PurchaseService, CashService, CreditService,
│   │                        # PriceService, ProductService, QuotationService
│   └── Support/             # CodeGenerator, UnitConverter, MoneyFormatter
database/
├── migrations/
├── seeders/                 # Roles/Permissions, Settings, Locations, Units, Admin
└── factories/
resources/views/livewire/... + layouts + components (Tailwind + Blade)
routes/ (web.php, console.php)
tests/ (Feature + Unit; foco en Services e Inventario)
```

**» Justificación:** carpeta `Domain/` con lógica pura (sin dependencia de Eloquent) para poder testear reglas críticas (conversión de unidades, márgenes, saldos) en aislamiento y cumplir SOLID (dependencias hacia abstracciones). Livewire agrupado por módulo para navegabilidad.

---

## 6. Migraciones (orden por dependencias)

1. Framework base: users, cache, jobs, failed_jobs (Laravel 12 default).
2. Spatie permission (publicada).
3. `settings`.
4. `units`, `brands`, `categories` (self-FK), `attributes`, `attribute_values`.
5. `products`, `presentations`, `product_attribute_value`, `product_images`.
6. `locations`, `inventory`, `stock_movements`.
7. `suppliers`, `purchases`, `purchase_items`, `receptions`, `reception_items`.
8. `customers`, `quotations`, `quotation_items`, `sales`, `sale_items`.
9. `credits`, `credit_payments`.
10. `cash_registers`, `cash_sessions`, `cash_movements`.
11. `price_histories`, `audits`.

Cada migración: FKs con `restrictOnDelete`/`cascadeOnDelete` según corresponda, índices en columnas de búsqueda (barcode, document_number, code, created_at), `unique` compuestos donde aplica. Tipos `decimal` explícitos (nunca float).

**» Justificación del orden:** respeta dependencias de FK (no se referencia una tabla antes de crearla) y agrupa por dominio para que las migraciones sean legibles y reversibles.

---

## 7. Modelos (responsabilidades)

Cada modelo Eloquent contendrá **solo**: relaciones, `casts` (incluye enums y decimales), `fillable`/`guarded`, scopes de consulta (`scopeActive`, `scopeSearch`) y accessors simples. **Nada de lógica de negocio pesada** (eso vive en Services/Domain).

Puntos destacados:
- `Product`: `booted()` genera `uuid` y `code` (vía `CodeGenerator`) si faltan; scope `search` (nombre/código/barcode/marca/medida); relación `attributeValues` y helper `stockTotal()` (suma de inventory).
- `Presentation`: cast de precios a decimal; `baseQuantity($qty)` delega en `UnitConverter`.
- `StockMovement`: inmutable — se sobreescribe `update`/`delete` para lanzar excepción (append-only enforced en código).
- `Sale`, `Purchase`, `Credit`, `CashSession`: enums de estado casteados; métodos de consulta de estado (`isEditable()`), pero las transiciones las hacen los Services.
- Traits: `HasAudit` (dispara auditoría), `HasCreator` (setea created_by/updated_by).

---

## 8. Riesgos identificados

| # | Riesgo | Impacto | Mitigación |
|---|---|---|---|
| R1 | **Condiciones de carrera en stock** (dos ventas simultáneas del mismo producto) | Stock negativo / sobreventa | Todo movimiento dentro de `DB::transaction` con `lockForUpdate()` sobre la fila `inventory`. Validación de stock dentro del lock. |
| R2 | **Desincronización saldo cacheado ↔ Kardex** | Reportes erróneos | `inventory` solo se toca vía `InventoryService`; comando `inventory:rebuild`; test que compara ambos. |
| R3 | **Doble precio (con/sin factura)** mal usado | Problema legal/tributario | Regla explícita: el flujo pregunta "¿con factura?" antes de vender; se guarda `with_invoice` por venta y el precio congelado en el ítem. |
| R4 | **Redondeo monetario** | Descuadres de caja | `decimal` en BD, cálculos con enteros de centavos o `bcmath`; nunca `float`. |
| R5 | **Cambio de equivalencia/precio de presentación** afecta documentos históricos | Reportes inconsistentes | Congelar `unit_price` y `base_quantity` en los ítems; historial de precios append-only. |
| R6 | **Arqueo de caja** con métodos mixtos (efectivo/QR) | Descuadre | `cash_movements` discrimina `method`; arqueo compara solo efectivo esperado vs contado. |
| R7 | **Permisos mal configurados** exponen costos/precios | Fuga de info sensible | Policies + Gates; costos ocultos por permiso; tests de autorización por rol. |
| R8 | **Borrado de catálogos con historial** | Rotura de integridad referencial | SoftDeletes en catálogos; `restrictOnDelete` cuando hay documentos asociados. |
| R9 | **Auditoría pesada** en cada request | Rendimiento | Auditoría vía eventos, `ShouldQueue` cuando se active la cola; solo entidades marcadas. |
| R10 | **Estados de compra** mal recalculados con múltiples recepciones | Compras "completas" con faltantes | `PurchaseService` recalcula `status` comparando `quantity_ordered` vs `quantity_received` sumado, en transacción. |
| R11 | **PHP 8.4 / Laravel 12 muy nuevos** — compatibilidad de paquetes | Instalación falla | Fijar versiones compatibles; verificar Livewire 3 y Spatie soportan 8.4; documentar en composer. |

---

## 9. Mejoras propuestas (para tu decisión)

1. **Auditoría con `owen-it/laravel-auditing`** en vez de tabla manual: maduro, morphable, captura old/new/IP/user automáticamente. Cumple exactamente tu requerimiento de auditoría (usuario, fecha, acción, IP, antes, después) con menos código propio.
2. **Reservas de stock** (`inventory.reserved_quantity`): al crear cotización o venta en proceso, reservar sin descontar. Evita vender lo ya comprometido. Propuesto como opcional (fase posterior).
3. **Costeo promedio ponderado (CPP)** para valorizar inventario a partir de recepciones: da costo real para márgenes y reportes. Recomiendo CPP sobre FIFO por simplicidad operativa en ferretería.
4. **Precio sugerido por margen**: `PriceService` calcula precio = costo × (1 + margen), con margen configurable en `settings`; el Administrador decide y confirma (nunca automático).
5. **QR de venta**: el `uuid` de `sales` genera un QR para consulta/verificación en caja (pediste QR en caja como método; además puede servir para comprobante).
6. **Búsqueda de productos performante**: índice sobre barcode/code + scope; a futuro (fuera de alcance inicial) Scout/Meilisearch si el catálogo crece mucho.
7. **Comando `credits:mark-overdue`** programado para marcar vencidos y disparar alertas de vencimiento.
8. **Tests de foco**: no cobertura total, sino Feature tests sobre los flujos críticos (recepción actualiza stock, venta descuenta stock y respeta lock, cobro reduce crédito, arqueo cuadra). Esto protege el núcleo sin frenar el desarrollo.

---

## 10. Plan de desarrollo por fases (código, tras tu aprobación)

- **Fase 0 — Bootstrap**: `composer create-project laravel/laravel`, instalar Livewire, Tailwind, Spatie; layout base, auth, roles/permisos seeders, settings. CI básico (pint + phpstan + tests).
- **Fase 1 — Catálogo**: Unidades, Marcas, Categorías, Atributos/Valores, Productos (código auto, barcode, imágenes, presentaciones, atributos).
- **Fase 2 — Inventario**: Ubicaciones, InventoryService (lock + transacción), Kardex, movimientos de Ajuste y Transferencia, comando rebuild.
- **Fase 3 — Compras/Recepciones**: Proveedores, Compras (crédito, historial de costos, sugerencia último proveedor), Recepciones (actualizan stock), estados parcial/completa.
- **Fase 4 — Ventas/Cotizaciones**: flujo "¿con factura?", búsqueda multi-criterio, doble precio, cotización→venta, descuento de stock con lock.
- **Fase 5 — Créditos/Cobros**: cuenta corriente, cobros parciales, historial, alertas de vencimiento.
- **Fase 6 — Caja**: apertura/cierre/arqueo, ingresos/egresos, cobros, QR/efectivo.
- **Fase 7 — Precios**: historial append-only, precio sugerido por margen, permisos Admin/Gerente.
- **Fase 8 — Reportes + Auditoría + Dashboard**: reportes (inventario, ventas, compras, más vendidos, clientes, créditos, caja), auditoría, dashboard con KPIs.

Cada fase = rama + PR revisable, con migraciones, seeders, Services, Livewire y tests del núcleo. Nada de "todo de una vez".

---

## Preguntas antes de programar

1. **Alcance del primer PR**: ¿Quieres que el primer PR sea solo **Fase 0 (bootstrap + auth + roles/permisos + estructura)** para validar la base, y luego avanzar fase por fase? (Recomendado.)
2. **Auditoría**: ¿OK usar `owen-it/laravel-auditing` (mejora #1) o prefieres tabla `audits` 100% manual?
3. **"Precio pendiente por producto"** (sección 3.6): ¿lo interpreto bien como un ítem de venta cuyo precio queda por definir/cobrar dentro de un crédito? ¿O te refieres a otra cosa?
4. **Costeo de inventario**: ¿implementamos **Costo Promedio Ponderado** (recomendado, mejora #3) desde el inicio o dejamos costo simple del último ingreso?
5. **Facturación fiscal**: ¿el sistema debe emitir comprobantes fiscales reales (integración con ente tributario) o "con factura" es solo una marca de precio/registro interno? Esto cambia bastante el módulo de ventas.
6. **Idioma del código/BD**: nombres de tablas/columnas en **inglés** (convención Laravel, recomendado) con UI en español, ¿de acuerdo?
```
