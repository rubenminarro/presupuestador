# Project Handoff Document

## Presupuestador — Sistema de Gestión para Taller Mecánico

**Versión del handoff:** 1.2
**Fecha:** 2026-10-17
**Estado:** Backend/API funcional — roadmap original completo (FASES 10 a 20: repuestos, inventario, servicios,
cierre económico, facturación, pagos, entrega, historial, mantenimiento preventivo, reportes); 6 roles; 235 tests
en verde (verificado el 2026-10-17 sobre `main` @ `8b3027a`; reverificado el 2026-10-19 sobre `main` @ `4ad9466`, sin
cambios de código desde `8b3027a`)
**Stack principal:** Laravel 12.56 + MySQL (8.4 en Laragon). Frontend previsto en React 19: **no está en este
repositorio** (ver 1.2)
**Entorno local:** Laragon / Windows
**Base de datos:** `presupuestador`

> **Propósito de este documento (desde la v1.2).** Es el registro histórico y de decisiones del proyecto: contexto,
> diseño por fase, decisiones de negocio y roadmap original. Hasta la v1.1 estaba en `CLAUDE.md`; en la v1.2 se movió
> aquí (con `git mv`, sin perder contenido) y `CLAUDE.md` pasó a contener solo las instrucciones operativas vigentes.
>
> * Instrucciones para trabajar en el código: [`CLAUDE.md`](../CLAUDE.md).
> * Instalación y uso: [`README.md`](../README.md).
> * Pruebas manuales por fase: [`docs/testing/guia-postman.md`](testing/guia-postman.md).
> * Discrepancias conocidas entre código y documentación: sección 14.
>
> Las secciones 5 y 6 (roadmap original) y 8 (checklist de migración) se conservan como historia.

---

# 1. Visión general y arquitectura

## 1.1 Propósito del proyecto

El proyecto **Presupuestador** es un sistema de gestión para un taller mecánico.

Su objetivo es cubrir progresivamente el flujo completo de atención de un vehículo:

```text
Cliente
   ↓
Vehículo
   ↓
Recepción
   ↓
Diagnóstico
   ↓
Presupuesto
   ↓
Aprobación del presupuesto
   ↓
Orden de Trabajo
   ↓
Ejecución de trabajos
   ↓
Repuestos / materiales
   ↓
Finalización
   ↓
Entrega del vehículo
```

El proyecto comenzó con foco en la generación y gestión de presupuestos, pero evolucionó hacia un sistema más completo de gestión operativa de taller.

La arquitectura debe permitir continuar agregando módulos sin romper los módulos existentes.

---

# 1.2 Stack tecnológico

## Backend

* PHP
* Laravel 12
* Laravel Sanctum
* Laravel API Resources
* Laravel Form Requests
* Eloquent ORM
* MySQL
* Spatie Laravel Permission

## Frontend

* React 19 (previsto)
* Consumo de API REST
* Autenticación mediante Sanctum

> **Verificado el 2026-10-17:** este repositorio no contiene código React. `package.json` es el scaffold por defecto
> de Laravel (Vite 7, Tailwind CSS 4, axios) sin uso real (`resources/views/welcome.blade.php` y `resources/js/app.js`
> sin cambios; `routes/web.php` sin rutas). Lo único preparado para un SPA es `config/cors.php` y
> `SANCTUM_STATEFUL_DOMAINS` con `http://localhost:5173` (puerto por defecto de Vite). La versión de React, la
> biblioteca visual y el repositorio del frontend no pudieron verificarse.

## Entorno de desarrollo

* Windows
* Laragon
* MySQL
* Composer
* Node.js / npm
* Git

---

# 1.3 Arquitectura general

El backend utiliza una arquitectura orientada a:

```text
Controller
    ↓
Form Request
    ↓
Service
    ↓
Model / Eloquent
    ↓
Database
```

Los Controllers son responsables principalmente de:

* recibir requests;
* autorizar operaciones;
* invocar Services;
* devolver Resources;
* devolver respuestas API estandarizadas.

La lógica de negocio importante debe permanecer en los Services y no ser trasladada directamente a los Controllers.

---

# 1.4 Principales capas

## Controllers

Ubicación:

```text
app/Http/Controllers/Api/
```

Responsabilidades:

* endpoints HTTP;
* autorización;
* recepción de requests;
* llamada a Services;
* transformación mediante Resources;
* respuesta API.

No deben contener lógica de negocio compleja.

---

## Requests

Ubicación:

```text
app/Http/Requests/
```

Responsabilidades:

* validación;
* reglas de entrada;
* mensajes de validación.

Cuando ya existe un Request específico para una operación, debe reutilizarse en lugar de duplicar reglas dentro del Controller.

---

## Services

Ubicación:

```text
app/Services/
```

Contienen reglas de negocio.

Ejemplos:

```text
BudgetService
WorkOrderService
```

Los Services son particularmente importantes para:

* cambios de estado;
* validaciones de negocio;
* transacciones;
* creación de entidades relacionadas;
* reglas que involucran múltiples modelos.

---

## Models

Ubicación:

```text
app/Models/
```

Representan entidades persistentes y sus relaciones Eloquent.

---

## Resources

Ubicación:

```text
app/Http/Resources/
```

Responsables de controlar el formato JSON público de la API.

Existe una diferenciación entre:

* Resource para listados;
* ShowResource para detalle.

Ejemplo:

```text
BudgetResource
ShowBudgetResource
WorkOrderResource
ShowWorkOrderResource
```

---

## Exceptions

Ubicación:

```text
app/Exceptions/
```

Actualmente existe una jerarquía orientada a errores de dominio (una excepción por módulo):

```text
AppException
├── BudgetException
├── WorkOrderException
├── WorkOrderPartException
├── PartException
├── SupplierException
├── InventoryException
├── ServiceException
├── InvoiceException
├── PaymentException
└── VehicleDeliveryException
```

---

## Enums

Ubicación:

```text
app/Enums/
```

Los estados y tipos importantes se representan mediante Enums PHP.

Esto evita trabajar indiscriminadamente con strings dispersos por el código.

---

## Traits

Ubicación:

```text
app/Traits/
```

Principalmente:

```text
ApiResponse
```

Este Trait centraliza las respuestas JSON estándar.

---

# 1.5 Estructura sugerida

La estructura lógica del backend debe mantenerse aproximadamente así:

```text
app/
├── Enums/
│   ├── BudgetStatus.php
│   ├── BudgetItemType.php
│   ├── WorkOrderStatus.php
│   ├── WorkOrderItemStatus.php
│   ├── WorkOrderItemType.php
│   └── ...
│
├── Exceptions/
│   ├── AppException.php
│   ├── BudgetException.php
│   ├── WorkOrderException.php
│   └── ...                    (una por módulo: ver 1.4)
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php
│   │       ├── BudgetController.php
│   │       ├── WorkOrderController.php
│   │       ├── WorkOrderItemController.php
│   │       ├── VehicleController.php
│   │       └── ...
│   │
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── StoreBudgetRequest.php
│   │   ├── UpdateBudgetRequest.php
│   │   ├── StoreWorkOrderRequest.php
│   │   ├── UpdateWorkOrderRequest.php
│   │   └── ...
│   │
│   └── Resources/
│       ├── BudgetResource.php
│       ├── ShowBudgetResource.php
│       ├── WorkOrderResource.php
│       ├── ShowWorkOrderResource.php
│       ├── WorkOrderItemResource.php
│       └── ...
│
├── Models/
│   ├── User.php
│   ├── Client.php
│   ├── Vehicle.php
│   ├── Reception.php
│   ├── Diagnostic.php
│   ├── Budget.php
│   ├── BudgetItem.php
│   ├── WorkOrder.php
│   ├── WorkOrderItem.php
│   ├── Mechanic.php
│   └── ...
│
├── Services/
│   ├── BudgetService.php
│   ├── WorkOrderService.php
│   └── ...
│
└── Traits/
    └── ApiResponse.php
```

---

# 2. Estado actual

## 2.1 Estado global

Las siguientes áreas ya fueron desarrolladas y validadas durante el proyecto:

* autenticación;
* usuarios/roles/permisos;
* clientes;
* vehículos;
* recepciones;
* diagnóstico;
* categorías de servicios;
* presupuestos;
* items de presupuesto;
* órdenes de trabajo;
* items de órdenes de trabajo;
* mecánicos;
* flujo de estados;
* respuestas API;
* excepciones;
* códigos internos de error;
* estandarización de códigos HTTP;
* auditoría de Controllers;
* auditoría de manejo de excepciones.

La **FASE 9.14 — Auditoría de excepciones y Controllers** fue cerrada.

También se completaron:

* **FASE 10 — Catálogo de repuestos:** `Part`, `PartCategory`, `Supplier`, pivote `part_supplier`.
* **FASE 11 — Inventario:** ver sección 2.24.
* **FASE 12 — Repuestos en WorkOrder:** ver sección 2.25.
* **FASE 13 — Servicios y mano de obra (comisiones):** ver sección 2.26.
* **FASE 14 — Cierre económico de WorkOrder:** ver sección 2.27.
* **FASE 15 — Facturación:** ver sección 2.28.
* **FASE 16 — Pagos:** ver sección 2.29.
* **FASE 17 — Entrega del vehículo:** ver sección 2.30.
* **FASE 18 — Historial del vehículo:** ver sección 2.31.
* **FASE 19 — Mantenimiento preventivo:** ver sección 2.32.
* **FASE 20 — Reportes y dashboard:** ver sección 2.33.

---

# 2.24 Inventario (FASE 11)

Componentes:

```text
Warehouse              (un único depósito "DEP-01 Principal", creado por migración, sin API)
PartStock              (saldo por repuesto/depósito: on_hand, reserved, average_cost)
InventoryMovement      (historial inmutable, fuente de verdad del stock)
InventoryMovementType  (initial, purchase, adjustment_in/out, supplier_return, loss,
                        work_order_out, work_order_return)
InventoryService       (registerEntry, registerExit, adjust, reserve, release)
InventoryException
App\Support\Decimal    (redondeo BCMath)
```

Reglas:

* Todo cambio de stock pasa por `InventoryService::applyMovement()` bajo `lockForUpdate()` sobre `part_stocks`.
* `quantity` del movimiento tiene signo (+ entrada / − salida); `balance_after` = saldo acumulado.
* Los movimientos no se editan ni eliminan (`INVENTORY_MOVEMENT_IMMUTABLE`); se corrigen con ajustes.
* Costo promedio ponderado (4 decimales), recalculado solo en entradas. `parts.cost_price` es solo referencia.
* Cálculos con BCMath (strings), nunca con float.
* Sin stock negativo: salidas contra disponible (`on_hand − reserved`) → `INSUFFICIENT_STOCK`.
* Ajuste = conteo físico (`counted_quantity`); el sistema genera la diferencia.
* Descontinuado: no admite entradas (`PART_DISCONTINUED_NO_ENTRY`); sí salidas y ajustes.
* Unidades `unit`/`set`/`kit` no admiten fracciones (`INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED`).
* Un repuesto con stock ≠ 0 no puede eliminarse (`PART_HAS_STOCK`).
* `reserve()`/`release()` no tienen endpoint propio: los usa la FASE 12 (`WorkOrderPartService`).
* Cantidades de inventario en `decimal(12,3)`; `budget_items`/`work_order_items` siguen en `(10,2)`.

Endpoints: `GET inventory/stocks`, `GET inventory/movements[/{id}]`, `GET parts/{part}/movements`,
`POST inventory/entries|exits|adjustments`. Permisos: `inventory.index|show|entry|exit|adjust`
(mecánico: solo `index` y `show`).

Tests: los tests usan SQLite en memoria; si en el `php.ini` de Laragon `pdo_sqlite` está deshabilitado (pasaba en
una de las máquinas de desarrollo; en otra ya está habilitado y alcanza con `vendor/bin/phpunit`), ejecutar con:

```bash
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

---

# 2.25 Repuestos en WorkOrder (FASE 12)

Componentes:

```text
budget_items.part_id    (nullable; solo type = part; vínculo con el catálogo)
WorkOrderPart           (repuesto realmente usado en la OT; work_order_item_id nullable = extra no presupuestado)
WorkOrderPartStatus     (pending, reserved, consumed, returned, cancelled)
WorkOrderPartService    (createFromBudgetItem, add, update, reserve, consume, returnToStock, cancel,
                         cancelOpenLines, ensureResolved)
WorkOrderPartException
InventoryService        (+ consumeReservation, returnToStock, normalizeQuantity pública)
```

Ciclo de una línea:

```text
creación con stock        → reserved   (reserve)
creación sin stock        → pending
pending   --reserve-->      reserved
pending/reserved --consume--> consumed  (libera reserva + salida work_order_out por lo usado)
pending/reserved --cancel-->  cancelled (libera reserva)
consumed  --return-->       consumed / returned si neto = 0 (entrada work_order_return al unit_cost de la línea)
```

Reglas:

* `createFromBudget` crea una línea por cada BudgetItem `part` con `part_id` e intenta reservar; si falta stock la OT se crea igual y la línea queda `pending`.
* Agregar/editar/reservar/cancelar: OT `pending|in_progress|paused`. Consumir/devolver: solo `in_progress`.
* OT `completed`/`cancelled`: líneas congeladas (`WORK_ORDER_PARTS_NOT_EDITABLE`).
* Cancelar la OT o un WorkOrderItem cancela sus líneas abiertas y libera reservas.
* Completar la OT exige que no haya líneas `pending|reserved` (`WORK_ORDER_HAS_UNRESOLVED_PARTS`).
* Un consumo por línea; lo usado puede diferir de lo reservado (excedente contra disponible).
* `unit_cost` se copia del movimiento de consumo; `unit_price` del BudgetItem o del `sale_price` del catálogo.
* Totales (`total_cost`, `total_price`) se calculan sobre la cantidad neta (consumida − devuelta); no se persisten.
* Repuestos `inactive` no se presupuestan ni se agregan (`PART_NOT_USABLE`); descontinuados sí.
* `InventoryMovement.reference_type = 'work_order_part'` (morphMap sin enforce en AppServiceProvider).
* Orden de bloqueo: OT → línea → `part_stocks`.

Endpoints: `GET|POST work-orders/{workOrder}/parts`, `PATCH work-orders/{workOrder}/parts/{part}`,
`POST work-orders/{workOrder}/parts/{part}/reserve|consume|return|cancel`.
Permisos: `work-order-parts.index|store|update|reserve|consume|return|cancel`
(mecánico: `index`, `consume`, `return`).

---

# 2.26 Servicios y mano de obra (FASE 13)

Contexto del negocio: los mecánicos cobran **sueldo + comisión**, un trabajo puede hacerlo **más de un mecánico**
y la mano de obra se cobra con **lista de precios**. Hoy **el taller no registra horas**: por eso el tiempo estimado,
las horas reales y el costo del mecánico (13.4–13.6) son **opcionales**. Sin horas, el costo de mano de obra es
desconocido (`null`), nunca cero.

Tipos de ítem:

```text
service  → trabajo del catálogo (precio de lista, ajustable)      → genera comisión
labor    → mano de obra fuera de catálogo, cotizada a mano        → genera comisión
part     → repuesto (FASE 12)                                     → no genera comisión
```

Componentes:

```text
Service                   (catálogo: code SRV-XXXXXX, service_category_id, name, description, standard_price,
                           estimated_hours nullable, status)
ServiceStatus             (active, inactive)
ServiceCategory           (existente; reutilizada como categoría del catálogo; API solo lectura)
budget_items.service_id   (nullable; solo type = service; sin unit_price toma standard_price)
budget_items.estimated_hours (nullable; service: catálogo × cantidad si no se informa; labor: opcional; part: prohibido)
WorkOrderItemMechanic     (work_order_item_mechanics: mecánico, share_percentage, commission_percentage copiado,
                           hours nullable, hour_cost nullable copiado de Mechanic.hour_cost)
ServiceCatalogService     (create, update, activate, deactivate, delete)
WorkOrderCommissionService (assignResponsible, syncItemMechanics, reassignResponsible, logHours,
                            summary, laborSummary, mechanicCommissions)
ServiceException
```

Reglas:

* Al crear la OT, cada ítem `labor`/`service` queda asignado al mecánico responsable con 100%.
* Cambiar el responsable de una OT `pending` reasigna los trabajos que tenía al 100%.
* `PUT .../items/{item}/mechanics` reemplaza la asignación completa: sin porcentajes → partes iguales
  (33.33/33.33/33.34); con porcentajes → todos deben informarlo y sumar 100 (`WORK_ORDER_ITEM_SHARES_INVALID`).
* Solo mecánicos `active` (`MECHANIC_NOT_ACTIVE`); solo ítems `labor`/`service` (`WORK_ORDER_ITEM_NOT_COMMISSIONABLE`).
* No editable con la OT `completed`/`cancelled` ni con el ítem `cancelled` (`WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE`).
* `commission_percentage` se copia al asignar; un mecánico que sigue asignado conserva su copia.
* Comisión = `budget_item.total` (sin IVA) × `share_percentage` × `commission_percentage`. Se calcula, no se persiste
  (la FASE 14 la congela). Los ítems cancelados no generan comisión.
* Reporte liquidable: ítems `completed` de OT `completed`, filtrado por `completed_at` (por defecto, el mes en curso).
  *(Desde la FASE 14 el reporte lee las comisiones congeladas en `work_order_commissions`, filtradas por `earned_at`;
  ver 2.27.)*
  Sin `mechanic-commissions.view-all` cada mecánico ve solo las propias (403 `FORBIDDEN`).
* Servicio inactivo no se presupuesta (`SERVICE_NOT_USABLE`); usado en presupuestos no se elimina (`SERVICE_IN_USE`).
* Horas reales: `PATCH .../items/{item}/mechanics/{mechanic}/hours` (`hours` o `null` para borrar). Solo con la OT
  `in_progress`/`paused` y el ítem no cancelado (`WORK_ORDER_ITEM_HOURS_NOT_EDITABLE`). Mecánico no asignado → 404
  (scoped binding vía `WorkOrderItem::mechanics()`). Sin `log-hours-any` cada mecánico carga solo las propias.
* `hour_cost` se copia en la primera carga y se conserva; si `Mechanic.hour_cost` es 0 queda `null` (costo desconocido).
* Costo de mano de obra = `hours × hour_cost`; completar la OT no exige horas.

Endpoints: `GET service-categories`, CRUD `services` (+ `activate`/`deactivate`),
`GET|PUT work-orders/{workOrder}/items/{item}/mechanics`,
`PATCH work-orders/{workOrder}/items/{item}/mechanics/{mechanic}/hours`,
`GET mechanics/{mechanic}/commissions?date_from&date_to`.
`ShowWorkOrderResource` incluye `items[].estimated_hours`, `items[].mechanics` (con `hours`, `hour_cost`, `labor_cost`),
`commission_summary` (`is_final`, `total_commission`, `by_mechanic`) y `labor_summary` (`estimated_hours`,
`actual_hours`, `labor_cost`, `assignments_without_hours`, `assignments_without_cost`, `is_complete`).

Permisos: `service-categories.index`, `services.index|store|show|update|destroy|change-status`,
`work-order-item-mechanics.index|sync|log-hours|log-hours-any`, `mechanic-commissions.index|view-all`
(mecánico: `service-categories.index`, `services.index|show`, `work-order-item-mechanics.index|log-hours`,
`mechanic-commissions.index`).

---

# 2.27 Cierre económico de WorkOrder (FASE 14)

Componentes:

```text
WorkOrderClosing        (work_order_closings: 1 por OT; presupuestado, venta, costos, margen, is_cost_complete)
WorkOrderCommission     (work_order_commissions: comisión congelada por mecánico y trabajo;
                         earned_at = completed_at de la OT; settled_at/settled_by = liquidación)
WorkOrderClosingService (close, summary)
WorkOrderCommissionService (+ freeze, settle; mechanicCommissions lee las comisiones congeladas)
```

Reglas (importes sin IVA):

* `WorkOrderService::complete()` cierra la OT en la misma transacción: congela el cierre y las comisiones.
* Venta = mano de obra/servicios no cancelados (total del BudgetItem) + repuestos **usados** (neto × `unit_price`,
  incluidos los extras no presupuestados) + repuestos fuera de catálogo no cancelados (total presupuestado).
* Costo = repuestos (neto × `unit_cost`) + mano de obra conocida (horas × `hour_cost`) + comisiones.
* Si falta algún costo (horas sin cargar, mecánico sin `hour_cost`, repuesto fuera de catálogo) el margen se calcula
  con los costos conocidos y `is_cost_complete = false`. `cost.labor` es `null` si no hay ninguna hora cargada.
* `variance` = venta real − presupuestado. `margin_percentage` = margen / venta × 100 (`null` si la venta es 0).
* OT abierta: `economic_summary` se calcula a la fecha (`is_final = false`). OT completada: se lee del cierre congelado.
* `economic_summary` solo se incluye con `work-orders.view-economics` (el mecánico no ve costos ni margen).
* Comisiones: el reporte lee `work_order_commissions` (`?status=pending|settled`, `total_pending`, `total_settled`).
  `POST mechanics/{mechanic}/commissions/settle` (`date_from`, `date_to` obligatorios) liquida las pendientes del
  período; sin pendientes → `MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE`.
* `WorkOrderClosing` es inmutable (`WORK_ORDER_CLOSING_IMMUTABLE`): es la base de la facturación.
* La migración 000037 crea solo la estructura. Regla: **las migraciones no llaman a código de la aplicación**
  (Services/Models), porque ese código evoluciona con el esquema y rompería `migrate:fresh` en el futuro.

Endpoints: `GET work-orders/{workOrder}` (`economic_summary`), `GET mechanics/{mechanic}/commissions`,
`POST mechanics/{mechanic}/commissions/settle`.
Permisos: `work-orders.view-economics`, `mechanic-commissions.settle` (solo administrador).

---

# 2.28 Facturación (FASE 15)

Contexto (Paraguay): **timbrado** con numeración `001-001-0000001` (establecimiento − punto de expedición −
correlativo), IVA **10% / 5% / exento**, condición **contado/crédito**. Los precios del sistema son **sin IVA**;
la factura los expresa **con IVA incluido**. Importes en **guaraníes enteros** (`config/invoice.php`: `decimals = 0`,
`default_tax_rate = 10`).

Componentes:

```text
InvoiceStamp        (invoice_stamps: stamp_number, establishment, expedition_point, valid_from, valid_until,
                     next_number, is_active; solo uno activo por establecimiento + punto)
Invoice             (invoices: work_order_id, client_id, status, número y copia del timbrado al emitir, datos del
                     cliente copiados, condition, due_date, totales por tasa, IVA 5/10, descuento, total)
InvoiceItem         (invoice_items: source morph work_order_item | work_order_part, quantity, base_unit_price y
                     base_total sin IVA, tax_rate, unit_price con IVA, discount, total)
InvoiceStatus       (draft, issued, cancelled)   InvoiceCondition (cash, credit)   TaxRate (0, 5, 10)
InvoiceService      (generate, update, updateItem, issue, cancel, delete)
InvoiceStampService (create, update)
InvoiceException
```

Ciclo:

```text
OT completed --generar-->  draft    (líneas = lo realmente usado; debe coincidir con closing.sale_total)
draft        --editar-->   draft    (cliente, condición, vencimiento; por línea: descripción, tasa, descuento)
draft        --descartar-> (se elimina; no consume número)
draft        --emitir-->   issued   (timbrado activo y vigente; toma next_number con lockForUpdate; inmutable)
issued       --anular-->   cancelled (motivo obligatorio; el número no se reutiliza; la OT puede refacturarse)
```

Reglas:

* Líneas: `labor`/`service` no cancelados y repuestos fuera de catálogo (valores presupuestados), repuestos de
  catálogo por cantidad neta usada (`WorkOrderPart`, incluidos extras). Si no coincide con el cierre:
  `INVOICE_TOTAL_MISMATCH`.
* Precio con IVA = sin IVA × (100 + tasa) / 100, redondeado; total = cantidad × precio − descuento (con IVA).
  Liquidación: IVA 10% = total_10 ÷ 11, IVA 5% = total_5 ÷ 21. Descuento solo por línea, ≤ importe de la línea.
* Una factura vigente (`draft`/`issued`) por OT (`WORK_ORDER_ALREADY_INVOICED`); OT no completada:
  `WORK_ORDER_NOT_INVOICEABLE`.
* Emitir exige RUC/CI (`INVOICE_CUSTOMER_TAX_ID_REQUIRED`) y, a crédito, vencimiento ≥ hoy (`INVOICE_DUE_DATE_REQUIRED`).
* Timbrado: sin vigente → `INVOICE_STAMP_NOT_AVAILABLE`; varios vigentes sin `invoice_stamp_id` →
  `INVOICE_STAMP_REQUIRED`; numeración agotada (> 9.999.999) → `INVOICE_STAMP_EXHAUSTED`; dos activos en el mismo
  establecimiento + punto → `INVOICE_STAMP_OVERLAP`.
* `Invoice` emitida es inmutable a nivel de modelo (solo admite los campos de anulación): `INVOICE_NOT_EDITABLE`.
* `ShowWorkOrderResource.invoice` (factura vigente) solo con `invoices.show`.
* Fuera de alcance: SIFEN (factura electrónica), notas de crédito, venta de mostrador sin OT, PDF, pagos (FASE 16).

Endpoints: `GET|POST invoice-stamps`, `PATCH invoice-stamps/{invoiceStamp}`, `POST work-orders/{workOrder}/invoice`,
`GET invoices`, `GET|PATCH|DELETE invoices/{invoice}`, `PATCH invoices/{invoice}/items/{item}`,
`POST invoices/{invoice}/issue|cancel`.
Permisos (solo administrador): `invoice-stamps.index|store|update`,
`invoices.index|show|store|update|issue|cancel|destroy`.

---

# 2.29 Pagos (FASE 16)

Componentes:

```text
Payment               (payments: invoice_id, payment_date, amount, method, reference, bank, notes, status,
                       receipt_sequence/receipt_number, created_by, datos de anulación)
PaymentMethod         (cash, card, transfer, check)
PaymentStatus         (registered, cancelled)
InvoicePaymentStatus  (unpaid, partial, paid; calculado, no se persiste)
PaymentService        (register, cancel, summary)
PaymentException
Invoice               (+ payments, paidTotal, balance, paymentStatus)
```

Ciclo:

```text
factura issued --registrar--> registered   (no supera el saldo; bloquea la factura)
registered     --anular-->    cancelled    (motivo obligatorio; el saldo vuelve a estar disponible)
```

Reglas:

* Solo facturas `issued` admiten pagos (`INVOICE_NOT_PAYABLE`). Varios pagos por factura, al contado o a crédito,
  hasta cubrir el total. Saldo = total − pagos `registered`.
* Monto en guaraníes enteros (`integer`, > 0). Supera el saldo → `PAYMENT_EXCEEDS_BALANCE`; saldo 0 → `INVOICE_ALREADY_PAID`.
* Vuelto (solo efectivo): `amount_received` opcional, ≥ `amount`; se guarda `change_amount = amount_received − amount`
  (migración 000042). `amount` es lo aplicado a la factura; sin `amount_received` el pago es exacto (ambos `null`).
  En otros medios `amount_received` está prohibido.
* `payment_date` opcional (hoy por defecto), no futura (validación) ni anterior a `issue_date` (`PAYMENT_DATE_BEFORE_ISSUE`).
* `reference` obligatoria para tarjeta, transferencia y cheque (se descarta en efectivo); `bank` obligatorio en cheque
  (se guarda solo en cheque y transferencia).
* Recibo `REC-000001` correlativo solo para pagos de facturas a crédito (`receipt_sequence` único); un recibo anulado
  no se reutiliza. Al contado, la factura es el comprobante.
* El pago no se edita ni se elimina (`PAYMENT_NOT_EDITABLE`, a nivel de modelo); anular uno ya anulado →
  `PAYMENT_NOT_CANCELLABLE`.
* Una factura con pagos vigentes no se anula (`INVOICE_HAS_PAYMENTS`): primero se anulan los pagos.
* `ShowInvoiceResource` incluye `payment_summary` (`paid_total`, `balance`, `status`) y `payments`; el listado de
  facturas incluye `balance` y `payment_status` (con `withSum`).
* Resumen de caja: pagos `registered` del período (por defecto, hoy) agrupados por medio, `cash.received` y
  `cash.change` (efectivo recibido y vuelto entregado), más la cantidad de anulados.

Endpoints: `POST invoices/{invoice}/payments`, `GET payments`, `GET payments/summary?date_from&date_to`,
`GET payments/{payment}`, `POST payments/{payment}/cancel`.
Permisos (solo administrador): `payments.index|show|store|cancel|summary`.

---

# 2.30 Entrega del vehículo (FASE 17)

Componentes:

```text
VehicleDelivery         (vehicle_deliveries: reception_id, vehicle_id, client_id, delivery_number ENT-000001,
                         delivered_at/by, final_mileage, fuel_level, received_by_name/document, customer_accepted,
                         signature_path (imagen opcional), observations, previous_reception_status, datos de anulación)
VehicleDeliveryStatus   (delivered, cancelled)
VehicleDeliveryService  (deliver, cancel)
VehicleDeliveryException
Reception               (+ deliveries)
```

Ciclo:

```text
recepción --entregar--> delivered   (recepción → delivered; vehicles.mileage solo avanza)
delivered --anular-->   cancelled   (motivo; la recepción vuelve a previous_reception_status; ENT no se reutiliza)
```

Reglas:

* Una entrega vigente por recepción (`DELIVERY_ALREADY_REGISTERED`, 409); recepción cancelada → `DELIVERY_RECEPTION_CANCELLED`.
* Ninguna OT de la recepción abierta (`pending|in_progress|paused` → `DELIVERY_WORK_ORDERS_OPEN`) y al
  menos una completada (`DELIVERY_WITHOUT_COMPLETED_WORK_ORDER`).
* Cada OT completada con factura vigente **emitida** (`DELIVERY_WORK_ORDER_NOT_INVOICED`); si es al contado, sin saldo
  (`DELIVERY_INVOICE_NOT_PAID`). A crédito se entrega con saldo.
* `final_mileage` ≥ kilometraje de la recepción (`DELIVERY_MILEAGE_BELOW_RECEPTION`). `customer_accepted` debe ser `true`
  (validación). Quién retira (nombre y documento) es obligatorio; la firma es una imagen opcional (disco `public`).
* La OT no cambia de estado al entregar (sigue `completed`; `WorkOrderStatus` no tiene estado de entrega), porque el cierre
  económico y la facturación dependen de `completed`.
* La entrega no se edita ni se elimina (`DELIVERY_NOT_EDITABLE`, a nivel de modelo); anular una anulada →
  `DELIVERY_NOT_CANCELLABLE`. Anular no revierte el kilometraje del vehículo.
* *(2026-10-19)* Si anular reabriría la recepción y el vehículo ya reingresó con otra recepción abierta, la anulación
  se rechaza (`DELIVERY_VEHICLE_HAS_OPEN_RECEPTION`, 409): un vehículo nunca tiene dos recepciones abiertas.

Endpoints: `POST receptions/{reception}/delivery` (JSON o multipart con `signature`), `GET vehicle-deliveries`,
`GET vehicle-deliveries/{delivery}`, `POST vehicle-deliveries/{delivery}/cancel`.
Permisos (solo administrador): `vehicle-deliveries.index|show|store|cancel`.

---

# 2.31 Historial del vehículo (FASE 18)

Solo lectura y **sin tablas nuevas**: `VehicleHistoryService` reúne lo que ya existe por vehículo.

```text
VehicleHistoryService           (summary, timeline, parts, mileage)
VehicleHistoryController        (show, parts, mileage)
VehicleHistoryReceptionResource (una visita del timeline)
VehicleHistoryPartResource
```

Contenido:

* `summary`: visitas (recepciones), primera y última visita, último kilometraje (última lectura o `vehicles.mileage`),
  OT completadas, último servicio, total facturado (facturas emitidas) y saldo pendiente. Con
  `work-orders.view-economics` agrega `economics` (venta, costo y margen de los cierres).
* `timeline` (paginado, visita más reciente primero): recepción (fecha, km de ingreso, problema, categorías),
  diagnósticos con hallazgos, presupuestos, OT (trabajos, repuestos usados con cantidad neta, factura con saldo y
  estado de cobro, `economics` solo con el permiso) y entrega vigente (km de salida).
* `parts`: líneas de repuestos con cantidad neta > 0 (precio; costo solo con el permiso).
* `mileage`: lecturas de recepciones y entregas vigentes en orden cronológico (mismo día: por hora de registro);
  `decreased = true` si una lectura es menor que la anterior (posible error de carga).

Nota técnica: `$this->when()` no se resuelve dentro de colecciones anidadas de un Resource; para claves condicionales
anidadas (como `economics` por OT) se arma el array a mano.

Endpoints: `GET vehicles/{vehicle}/history?per_page`, `GET vehicles/{vehicle}/history/parts?per_page`,
`GET vehicles/{vehicle}/history/mileage`.
Permiso: `vehicle-history.view` (administrador, mecánico, recepcionista, jefe-taller, cajero y depósito).

---

# 2.32 Mantenimiento preventivo (FASE 19)

Componentes:

```text
MaintenancePlan     (maintenance_plans: name, description, service_id nullable, interval_km, interval_days, is_active)
VehicleMaintenance  (vehicle_maintenances: vehicle_id + maintenance_plan_id únicos, interval_km/interval_days propios
                     (null = los del plan), last_done_at, last_done_mileage, work_order_id, next_due_at, next_due_mileage)
MaintenanceStatus   (unknown, ok, due_soon, overdue; calculado)
MaintenanceService  (createPlan, updatePlan, deletePlan, recordDone, registerFromWorkOrder, configure,
                     estimatedMileage, forVehicle, alerts)
MaintenanceException
config/maintenance.php (due_soon_km = 1000, due_soon_days = 30)
MaintenancePlanSeeder  (aceite 5.000 km/180 días, rotación 10.000 km, alineación 10.000 km/365 días)
```

Reglas:

* Un plan tiene intervalo en km y/o días (al menos uno: validación al crear, `MAINTENANCE_INTERVAL_REQUIRED` al editar).
  Con seguimiento no se elimina (`MAINTENANCE_PLAN_IN_USE`, 409): se desactiva.
* Registro **automático**: `WorkOrderService::complete()` llama a `registerFromWorkOrder()`; cada ítem `service`
  completado cuyo `service_id` es el de un plan activo registra el mantenimiento con la fecha de cierre y el km de la
  recepción. Un registro más viejo que el último se ignora.
* Registro **manual** (`POST vehicles/{vehicle}/maintenances`): plan activo (`MAINTENANCE_PLAN_INACTIVE`), fecha no
  futura, no anterior al último (`MAINTENANCE_DONE_OUT_OF_ORDER`) ni con menos km (`MAINTENANCE_MILEAGE_BELOW_LAST`).
* Próximo vencimiento = último + intervalo efectivo (propio del vehículo o del plan). Cambiar los intervalos del plan o
  del vehículo recalcula.
* Km actual **estimado**: última lectura (historial, sin las marcadas `decreased`) + promedio km/día entre la primera y
  la última × días desde la última. Con una sola lectura (o el mismo día) se usa la lectura; sin lecturas, `vehicles.mileage`.
* Estado: vence por km o por fecha, lo que ocurra primero. `overdue` si faltan ≤ 0 km o la fecha pasó; `due_soon`
  dentro de los umbrales de `config/maintenance.php`; `unknown` si nunca se registró (no genera alerta).
* Alertas: solo planes activos, vehículos no eliminados y mantenimientos con registro; vencidos primero, con datos de
  contacto del cliente. Los avisos automáticos (email/WhatsApp) quedan para el módulo de notificaciones.
* La ruta `PATCH vehicles/{vehicle}/maintenances/{maintenancePlan}` usa `withoutScopedBindings()`: el plan no es una
  relación del vehículo.

Endpoints: CRUD `maintenance-plans` (PATCH para editar/activar), `GET|POST vehicles/{vehicle}/maintenances`,
`PATCH vehicles/{vehicle}/maintenances/{maintenancePlan}`, `GET maintenance/alerts?status=overdue|due_soon`.
Permisos: `maintenance-plans.index|show|store|update|destroy`, `vehicle-maintenances.index|record|configure`,
`maintenance-alerts.index`. Mecánico: ver planes y el estado del vehículo; recepcionista: además registrar y ver
alertas; jefe-taller: además configurar intervalos del vehículo; ABM de planes: administrador.

---

# 2.33 Reportes y dashboard (FASE 20)

Solo lectura y sin tablas nuevas. `ReportService` (reutiliza `PaymentService::summary` y `MaintenanceService::alerts`),
`ReportController`, `ReportRequest` (`date_from`/`date_to`, por defecto el mes en curso; `group_by` = day|month).
Solo JSON (la exportación queda para más adelante).

| Endpoint | Contenido | Fecha que filtra | Permiso |
|---|---|---|---|
| `GET dashboard` | Bloques según permisos: `workshop` (siempre), `sales`, `budgets`, `profitability`, `productivity`, `inventory`, `maintenance` | mes en curso | `dashboard.view` (+ el permiso de cada bloque) |
| `GET reports/sales` | Facturas emitidas: totales por tasa, IVA, descuento, ticket promedio, por día/mes, por condición; cobrado por medio; anuladas | emisión de la factura | `reports.sales` |
| `GET reports/budgets` | Por estado actual (cantidad y monto); tasa de aprobación = aprobados / (aprobados + rechazados) | creación del presupuesto | `reports.budgets` |
| `GET reports/profitability` | Cierres: venta, costos, comisiones, margen y %; 5 OT de menor y mayor margen; cuántas con costo incompleto | cierre de la OT | `reports.profitability` |
| `GET reports/productivity` | Por mecánico: OT como responsable, trabajos, venta de mano de obra atribuida (total × participación), comisiones, horas y costo | cierre de la OT | `reports.productivity` |
| `GET reports/inventory` | Valor del stock y bajo mínimo (a hoy; sin stock = 0); 10 más consumidos (neto de devoluciones) y movimientos por tipo | movimiento | `reports.inventory` |
| `GET reports/clients` | 10 clientes que más facturan, clientes nuevos, saldos por cobrar por cliente (a hoy), 10 vehículos con más visitas | emisión / alta / recepción | `reports.clients` |

Importes de ventas y clientes: con IVA (factura). Rentabilidad y productividad: sin IVA (cierre).
Acceso por área: cajero `sales` y `clients`; recepcionista `budgets`; jefe-taller `productivity`; depósito `inventory`;
`profitability` solo administrador; mecánico solo el dashboard (bloque `workshop`). El bloque `maintenance` del
dashboard usa `maintenance-alerts.index`.

---

# 2.2 Autenticación

Existe:

```text
AuthController
LoginRequest
UserResource
```

El login se realiza mediante:

```http
POST /api/login
```

La autenticación utiliza:

```text
Laravel Sanctum
```

El login inválido devuelve una respuesta API estandarizada.

Ejemplo conceptual:

```json
{
    "success": false,
    "status": 401,
    "message": "Credenciales inválidas.",
    "data": null,
    "errors": null,
    "error_code": "UNAUTHENTICATED"
}
```

---

# 2.3 Roles y permisos

Se utiliza:

```text
spatie/laravel-permission
```

Roles principales:

```text
administrador
mechanic
recepcionista
jefe-taller
cajero
deposito
```

(El rol del mecánico se llama `mechanic` en el seeder y en el código; no `mecanico`.)

| Rol | Función | Accesos principales |
|---|---|---|
| `administrador` | Todo (`Gate::before`) | Exclusivo: usuarios/roles/permisos, `work-orders.view-economics`, liquidar comisiones, anular entregas, timbrados |
| `mechanic` | Ejecuta la OT | Diagnóstico, iniciar/completar OT e ítems, consumir/devolver repuestos, cargar sus horas, ver sus comisiones |
| `recepcionista` | Atiende al cliente | Clientes, vehículos, recepciones, checklist, presupuestos completos, registrar entregas; consulta OT, facturas y stock |
| `jefe-taller` | Organiza el trabajo | OT completas, ítems, repuestos en OT, asignación y horas de todos, diagnóstico, comisiones (consulta) |
| `cajero` | Cobra | Facturas, pagos, resumen de caja; consulta OT, presupuestos y timbrados |
| `deposito` | Maneja el stock | Repuestos, proveedores, inventario, repuestos en OT |

Los cuatro últimos se definen en `RolesAndPermissionsSeeder::workshopRoles()` (se sincronizan al volver a ejecutar el
seeder). `tests/Feature/Access/WorkshopRolesTest` verifica que todos sus permisos existan y qué puede y qué no puede
hacer cada rol. Ningún usuario del seeder tiene estos roles: se asignan desde `PATCH /api/users/{id}` (`role`).

Guard:

```text
api
```

Middleware configurados:

```text
role
permission
role_or_permission
```

Los Controllers modernos implementan:

```php
HasMiddleware
```

y definen permisos por operación.

> El rol `administrador` pasa todos los permisos por `Gate::before` (AppServiceProvider), por eso un permiso mal
> escrito en un controller no se nota con el admin. El nombre del middleware debe coincidir exactamente con el del
> `RolesAndPermissionsSeeder`; los tests con roles propios (`tests/Feature/Access`, `WorkOrderLifecycleTest`) lo verifican.

Ejemplo conceptual:

```text
work-orders.index
work-orders.store
work-orders.show
work-orders.update
work-orders.start
work-orders.pause
work-orders.resume
work-orders.complete
work-orders.cancel
```

---

# 2.4 Clientes

El módulo de clientes constituye una de las entidades principales del sistema.

Su función es identificar al propietario/responsable del vehículo y servir como origen del flujo de atención.

---

# 2.5 Vehículos

Existe el módulo de vehículos.

El vehículo se encuentra relacionado con el cliente y participa posteriormente en:

```text
Reception
Diagnostic
Budget
WorkOrder
```

Se implementaron:

```text
VehicleController
VehicleResource
ShowVehicleResource
StoreVehicleRequest
UpdateVehicleRequest
```

También existe contexto de:

```text
VehicleModel
```

para separar correctamente marca/modelo de la instancia concreta del vehículo.

Rutas anidadas por cliente propietario (complementan a `/api/vehicles`, que se mantiene):

```text
GET  /api/clients/{client}/vehicles             (vehicles.index)
POST /api/clients/{client}/vehicles             (vehicles.store)
GET  /api/clients/{client}/vehicles/{vehicle}   (vehicles.show)
```

Componentes: `ClientVehicleController` y `StoreClientVehicleRequest` (extiende `StoreVehicleRequest`;
el `client_id` se toma de la URL e ignora el del body). `scopeBindings()` devuelve 404 si el vehículo
pertenece a otro cliente. Editar y eliminar siguen solo en `/api/vehicles/{vehicle}`.

Decisión: la tabla se mantiene como `vehicles` (no `clients_vehicles`). El vehículo es una entidad propia
—eje de recepciones, presupuestos, OT e historial— y su dueño puede cambiar; un nombre compuesto
sugeriría una tabla pivote. Si en el futuro se necesita historial de propietarios, modelarlo aparte
(por ejemplo `vehicle_ownerships`).

---

# 2.6 Recepción

La recepción representa el ingreso del vehículo al taller.

Tiene relación con:

* cliente;
* vehículo;
* categorías de servicio;
* checklist;
* diagnóstico;
* presupuesto;
* orden de trabajo.

Existe una relación **many-to-many** entre Reception y ServiceCategory.

Esto permite que una recepción pueda solicitar múltiples categorías de servicio.

---

# 2.7 Checklist de recepción

La recepción puede generar un checklist basado en las categorías de servicio seleccionadas.

Se aplican restricciones de modificación según el estado de la recepción.

Estados relevantes incluyen:

```text
in_progress
completed
delivered
```

Una recepción que ya avanzó en el flujo no debe permitir modificaciones incompatibles con su estado.

Esta lógica fue revisada durante la auditoría de excepciones.

> **Verificado (v1.2).** `ReceptionStatus` tiene `pending, in_progress, repair, completed, delivered, cancelled`.
> Ningún endpoint asignaba `in_progress`, `repair`, `completed` ni `cancelled`: la recepción nacía `pending` y solo la
> entrega (FASE 17) la pasaba a `delivered`. Ver sección 14.

## Estados de la recepción (2026-10-19)

Decisión: el estado **se deriva del flujo** en lugar de asignarse con un endpoint por transición, para que nunca quede
desincronizado de las OT. Solo la entrega (`delivered`) y la cancelación manual (`cancelled`) lo fijan.

```text
pending      --crear presupuesto-->        in_progress  (En proceso)
in_progress  --iniciar/reanudar una OT-->  repair       (En reparación; también con la OT pausada)
repair       --completar la(s) OT-->       completed    (Terminado: todas completadas o canceladas)
completed    --nueva OT (trabajo extra)--> in_progress
completed    --entregar-->                 delivered    (FASE 17; anular la entrega vuelve al estado anterior)
pending|in_progress|completed --cancelar (sin OT vigentes)--> cancelled
```

* `ReceptionStatusService::sync()` recalcula: alguna OT `in_progress|paused` → `repair`; alguna `pending` →
  `in_progress`; completadas (resto canceladas) → `completed`; sin OT vigentes → `in_progress` con presupuesto o
  `pending` sin él. Se llama al crear/eliminar presupuestos y en cada cambio de estado de una OT.
* Se eligió **crear el presupuesto** (y no el diagnóstico) como inicio del proceso: el checklist puede completarse
  durante el diagnóstico; al presupuestar se congela.
* "Procesada" = todo salvo `pending` (antes `in_progress|completed|delivered`, sin `repair` ni `cancelled`).
* `IN_PROGRESS` pasó a mostrarse "En proceso" (antes "En reparación", igual que `REPAIR`); el `value` no cambió.
* Cancelación (`receptions.cancel`, recepcionista y administrador): motivo obligatorio; columnas `cancelled_at`,
  `cancelled_by`, `cancellation_reason` (migración 000045, que además recalcula en SQL las recepciones existentes).
* Recepción cerrada: diagnósticos, presupuestos y OT nuevos → `RECEPTION_CLOSED`.

---

# 2.8 Diagnóstico

El diagnóstico permite registrar la evaluación técnica realizada sobre el vehículo.

El diagnóstico tiene relación con el mecánico responsable.

Existe:

```text
mechanic_id
```

como referencia al mecánico.

Esto prepara el sistema para posteriormente utilizar información del diagnóstico como entrada para el presupuesto.

---

# 2.9 Mecánicos

El módulo de mecánicos contempla información como:

```text
employee_code
user_id
specialty
hour_cost
commission_percentage
status
```

El estado del mecánico está representado mediante Enum.

El módulo permitirá posteriormente utilizar el costo/hora y otros datos para cálculo de mano de obra.

---

# 2.10 Categorías de servicio

Las categorías de servicio representan tipos generales de trabajos solicitados en el taller.

Participan principalmente en:

```text
Reception
Checklist
Budget
WorkOrder
```

La arquitectura utiliza categorías en lugar de codificar directamente todos los servicios dentro de Recepción.

---

# 2.11 Presupuestos

El módulo Budget está funcional.

Principales componentes:

```text
Budget
BudgetItem
BudgetService
BudgetException
BudgetStatus
BudgetItemType
BudgetController
BudgetResource
ShowBudgetResource
StoreBudgetRequest
UpdateBudgetRequest
```

---

## Estados de Budget

Actualmente:

```text
draft
sent
approved
rejected
cancelled
```

Flujo:

```text
draft
   ↓
sent
   ↓
approved
```

También:

```text
sent → rejected
sent → cancelled
draft → cancelled

rejected → draft
```

> **Verificado (v1.2):** `BudgetService::cancel()` acepta `draft` y `sent` (test `BudgetLifecycleTest`). `approved`,
> `rejected` y `cancelled` no se cancelan (`BUDGET_NOT_CANCELLABLE`).

---

## Reglas importantes

Un presupuesto:

* puede editarse únicamente en `draft`;
* puede enviarse;
* puede aprobarse únicamente cuando cumple las condiciones necesarias;
* puede rechazarse;
* puede cancelarse;
* puede reabrirse desde rechazado.

Para aprobar:

* debe tener items;
* se registra `approved_at`.

---

# 2.12 BudgetItem

Los tipos actuales son:

```text
labor
part
service
```

Los BudgetItems poseen:

```text
quantity
unit_price
total
```

Esto es importante para el futuro módulo de repuestos.

Actualmente el precio económico está correctamente representado en el presupuesto.

---

# 2.13 Orden de Trabajo

El módulo de WorkOrder está completamente desarrollado para el alcance actual.

Componentes principales:

```text
WorkOrder
WorkOrderItem
WorkOrderService
WorkOrderException
WorkOrderController
WorkOrderItemController
WorkOrderResource
ShowWorkOrderResource
WorkOrderItemResource
```

---

# 2.14 Creación de WorkOrder

Una orden de trabajo se crea desde un presupuesto aprobado.

Regla:

```text
Budget.status === approved
```

Además:

* el presupuesto debe tener al menos un item;
* el presupuesto no debe tener una WorkOrder existente.

La orden recibe un código:

```text
OT-000001
OT-000002
OT-000003
...
```

---

# 2.15 Estados de WorkOrder

El flujo actual es:

```text
pending
   ↓
in_progress
   ↓
completed
```

También existe:

```text
pending → cancelled
```

Y operaciones:

```text
in_progress → paused
paused → in_progress
```

El sistema evita transiciones inválidas.

> **Verificado (v1.2):** `WorkOrderStatus` definía también `waiting_parts` y `delivered`, pero ninguna operación los
> asignaba. **Eliminados el 2026-10-19** (deuda técnica #3): el enum queda `pending`, `in_progress`, `paused`,
> `completed` y `cancelled`. La entrega del vehículo (FASE 17) no cambia la OT: queda `completed`, porque el cierre
> económico, la facturación y los reportes dependen de ese estado. Si "esperando repuestos" se necesita, se diseña
> como transición propia (hoy lo cubren la pausa y las líneas de repuestos `pending`).

---

# 2.16 WorkOrderItem

Los items de la orden de trabajo derivan de BudgetItems.

Tipos:

```text
labor
part
service
```

Estados:

```text
pending
in_progress
completed
cancelled
```

Reglas principales:

* solamente se puede iniciar un item cuando la WorkOrder está `in_progress`;
* un item `pending` puede pasar a `in_progress`;
* un item `in_progress` puede completarse;
* un item pendiente puede cancelarse;
* el item debe pertenecer a la WorkOrder indicada.

---

# 2.17 Importante: WorkOrderItem no es actualmente inventario

Una decisión importante para futuras fases:

`WorkOrderItem` actualmente representa el trabajo/material incluido en la orden, pero **no es todavía un sistema de inventario de repuestos**.

Actualmente no almacena:

```text
unit_price
total
stock
warehouse
supplier
purchase cost
sale price
```

Los precios sí existen en:

```text
BudgetItem
```

Esto debe tenerse en cuenta al implementar el futuro módulo de repuestos.

---

# 2.18 Edición de WorkOrder

La WorkOrder solamente puede editarse mientras está:

```text
pending
```

La edición actual permite modificar principalmente:

```text
mechanic_id
notes
```

No debe permitirse alterar arbitrariamente:

```text
code
reception_id
budget_id
created_by
status
timestamps
```

---

# 2.19 Flujo completo validado

Se realizó una regresión completa del flujo:

```text
Budget
   ↓
Approved
   ↓
WorkOrder
   ↓
Start
   ↓
Start Items
   ↓
Complete Items
   ↓
Complete WorkOrder
```

Se validaron casos reales con órdenes:

```text
OT-000003
OT-000004
```

También se verificó que una orden completada no pueda reiniciarse.

---

# 2.20 API Response

Se implementó un contrato global.

## Éxito

Formato conceptual:

```json
{
    "success": true,
    "status": 200,
    "message": "Operación realizada correctamente.",
    "data": {},
    "errors": null,
    "meta": null,
    "timestamp": "..."
}
```

`meta` es `null` salvo en listados (`meta.pagination` con `total`, `perPage`, `currentPage`, `lastPage`) y algunos
reportes (por ejemplo `meta.counts` en alertas de mantenimiento). Fuente: `App\Traits\ApiResponse`.

## Error

Formato actual:

```json
{
    "success": false,
    "status": 403,
    "message": "No tienes permisos para realizar esta acción.",
    "data": null,
    "errors": null,
    "error_code": "FORBIDDEN"
}
```

El campo:

```text
error_code
```

es importante para que el frontend pueda reaccionar programáticamente.

---

# 2.21 Excepciones

Jerarquía: ver sección 1.4 (una subclase de `AppException` por módulo: Budget, WorkOrder, WorkOrderPart, Part,
Supplier, Inventory, Service, Invoice, Payment y VehicleDelivery).

`AppException` recibe:

```text
message
status
errorCode
errors
```

Conceptualmente:

```php
AppException(
    message,
    status,
    errorCode,
    errors
)
```

---

# 2.22 Códigos HTTP estandarizados

La auditoría de excepciones estableció respuestas para:

```text
401 UNAUTHENTICATED
403 FORBIDDEN
404 RESOURCE_NOT_FOUND
405 METHOD_NOT_ALLOWED
422 VALIDATION_ERROR
500 INTERNAL_SERVER_ERROR
503 SERVICE_UNAVAILABLE
```

También se trabajó el manejo de excepciones de infraestructura y HTTP.

> **Verificado (v1.2)** en `bootstrap/app.php`: además se mapean `400 BAD_REQUEST`, `406 NOT_ACCEPTABLE`,
> `415 UNSUPPORTED_MEDIA_TYPE` y `429 TOO_MANY_REQUESTS`. Los errores de dominio usan `422` o `409` (conflictos:
> duplicados, ya facturada, inmutables). Todo responde JSON solo si el request espera JSON (`Accept: application/json`).
> Desde el 2026-10-19 el middleware global `App\Http\Middleware\EnsureJsonResponse` fija ese header en toda ruta
> `api/*`, antes del ruteo (así también los 404/405 responden JSON); las rutas web no se tocan.

---

# 2.23 FASE 9 — Auditoría de excepciones

La auditoría avanzó por:

```text
9.1  Auditoría inicial
9.2  Definición del contrato
9.3  AppException
9.4  BudgetException
9.5  WorkOrderException
9.6  Transformación HTTP
9.7  Dominio / infraestructura
9.8  Validation
9.9  Authentication
9.10 Not Found
9.11 HTTP Status Codes
9.12 Error Codes
9.13 Integración de excepciones
9.14 Auditoría final de Controllers
```

La fase 9.14 quedó cerrada.

---

# 3. Decisiones de diseño y convenciones

# 3.1 No colocar lógica de negocio compleja en Controllers

Incorrecto:

```text
Controller
 ├── validaciones de negocio
 ├── cambios de estado
 ├── transacciones
 ├── creación de múltiples modelos
 └── reglas complejas
```

Preferido:

```text
Controller
    ↓
Service
    ↓
Models
```

---

# 3.2 Services para lógica de negocio

Los Services son obligatorios para procesos que:

* cambian estados;
* modifican varias entidades;
* requieren transacciones;
* contienen reglas de negocio;
* representan operaciones del dominio.

Ejemplo:

```text
WorkOrderService
```

con operaciones:

```text
createFromBudget()
start()
pause()
resume()
complete()
cancel()
startItem()
completeItem()
cancelItem()
update()
updateItem()
```

---

# 3.3 Transacciones

Las operaciones que afectan múltiples registros deben utilizar:

```php
DB::transaction(...)
```

Esto es especialmente importante para:

```text
crear WorkOrder + items
cancelar WorkOrder + items
operaciones sobre Budget
operaciones futuras de inventario
```

---

# 3.4 Estados mediante Enum

No introducir strings arbitrarios cuando existe un estado formal.

Ejemplo:

```php
BudgetStatus::APPROVED
```

en lugar de:

```php
'approved'
```

La persistencia sigue usando los valores correspondientes, pero la lógica PHP debe preferir Enums.

---

# 3.5 Naming

Modelos:

```text
PascalCase singular
```

Ejemplo:

```text
WorkOrder
BudgetItem
Vehicle
```

Tablas:

```text
snake_case plural
```

Ejemplo:

```text
work_orders
budget_items
```

Métodos:

```text
camelCase
```

Ejemplo:

```text
createFromBudget()
startItem()
completeItem()
```

Rutas:

```text
kebab-case
```

Ejemplo:

```text
/api/work-orders
```

---

# 3.6 Resources

Los Resources controlan la representación externa de las entidades.

No exponer directamente:

```php
$model->toArray()
```

como contrato público si existe un Resource.

---

# 3.7 Listado vs detalle

Se mantiene la separación:

```text
Resource
```

para listados.

```text
ShowResource
```

para detalles.

Ejemplo:

```text
BudgetResource
ShowBudgetResource
```

---

# 3.8 Nested routes

Se utiliza:

```php
scopeBindings()
```

para rutas anidadas.

Esto permite validar correctamente relaciones como:

```text
work-order → item
```

y evita aceptar un item perteneciente a otra orden.

---

# 3.9 PATCH para actualizaciones

Las actualizaciones utilizan:

```http
PATCH
```

cuando conceptualmente son parciales.

No cambiar esta convención sin una razón arquitectónica clara.

---

# 3.10 Middleware de permisos

Los Controllers utilizan:

```php
HasMiddleware
```

con permisos específicos.

Ejemplo conceptual:

```text
work-orders.index
work-orders.store
work-orders.show
work-orders.update
work-orders.start
work-orders.pause
work-orders.resume
work-orders.complete
work-orders.cancel
```

No reemplazar esto por una autorización global indiscriminada.

---

# 3.11 Contrato de errores

Todos los errores API deben utilizar el formato:

```json
{
    "success": false,
    "status": 422,
    "message": "...",
    "data": null,
    "errors": null,
    "error_code": "..."
}
```

No devolver directamente:

```text
exception
file
line
trace
```

en producción.

---

# 3.12 Error codes

Los mensajes son para humanos.

Los `error_code` son para el frontend.

Ejemplos:

```text
VALIDATION_ERROR
UNAUTHENTICATED
FORBIDDEN
RESOURCE_NOT_FOUND
METHOD_NOT_ALLOWED
SERVICE_UNAVAILABLE
```

Y códigos de dominio específicos:

```text
WORK_ORDER_NOT_CANCELLABLE
```

El frontend debe poder tomar decisiones utilizando `error_code`, no haciendo parsing del texto de `message`.

---

# 3.13 Mensajes

Los mensajes visibles de la API están en español.

Los nombres técnicos del código permanecen en inglés.

Ejemplo:

```text
Código:
WORK_ORDER_NOT_CANCELLABLE

Mensaje:
La orden de trabajo solo puede cancelarse cuando está pendiente.
```

---

# 3.14 No sobre-refactorizar

Una decisión importante durante el proyecto fue:

> No refactorizar arquitectura existente solamente por estilo.

Si una implementación funciona y respeta las convenciones actuales, debe mantenerse salvo que exista una razón funcional, de seguridad, rendimiento o mantenibilidad.

---

# 4. Variables de entorno y configuración

Nunca migrar secretos reales dentro del handoff.

> **Nota (verificado el 2026-10-19):** esta sección es una plantilla histórica. Los valores de referencia vigentes están
> en `.env.example` (por ejemplo `APP_URL=http://127.0.0.1:8000` y `ADMIN_PASSWORD`, la contraseña de los usuarios del
> seeder). `VITE_API_URL` pertenece al repositorio del frontend: este repositorio no la usa.

El nuevo entorno debe disponer de variables equivalentes a:

```env
APP_NAME=Presupuestador
APP_ENV=local
APP_KEY=<SECRET>
APP_DEBUG=true
APP_URL=http://localhost
```

Base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presupuestador
DB_USERNAME=<DB_USER>
DB_PASSWORD=<DB_PASSWORD>
```

Sesiones/cache/colas según configuración de Laravel:

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Mail:

```env
MAIL_MAILER=<MAILER>
MAIL_HOST=<MAIL_HOST>
MAIL_PORT=<MAIL_PORT>
MAIL_USERNAME=<MAIL_USERNAME>
MAIL_PASSWORD=<MAIL_PASSWORD>
MAIL_ENCRYPTION=<MAIL_ENCRYPTION>
MAIL_FROM_ADDRESS=<MAIL_FROM>
MAIL_FROM_NAME="${APP_NAME}"
```

Frontend:

```env
VITE_API_URL=<BACKEND_API_URL>
```

Los valores concretos deben configurarse en cada entorno.

---

# 4.1 Archivos que NO deben migrarse con secretos

No incluir públicamente:

```text
.env
```

ni:

```text
API keys
passwords
tokens
APP_KEY
credenciales SMTP
credenciales de base de datos
tokens de terceros
```

Debe migrarse:

```text
.env.example
```

con valores ficticios o vacíos.

---

# 4.2 Dependencias PHP relevantes

Entre las dependencias importantes:

```text
laravel/framework
laravel/sanctum
spatie/laravel-permission
```

Además de las dependencias normales de Laravel/Composer.

Antes de continuar el desarrollo en otra herramienta:

```bash
composer install
```

y:

```bash
npm install
```

> **Nota (verificado el 2026-10-19):** `npm install` es **opcional** en este repositorio: `package.json` solo trae el
> scaffold de Vite + Tailwind de Laravel, que la API no usa (`composer setup` y `composer dev` sí lo invocan). Ver
> README, "Requisitos".

---

# 5. Próximos módulos / Roadmap

> **Nota histórica:** esta sección es el roadmap original del handoff. Los módulos de repuestos, inventario,
> servicios, cierre económico, facturación, pagos y entrega (5.1–5.15) ya están implementados (FASES 10–17,
> secciones 2.24–2.30). Lo pendiente empieza en 5.16 (historial del vehículo); el punto exacto está en la sección 11.

## Diseño original del módulo de Repuestos (implementado en FASES 10–12)

No debía implementarse simplemente como un CRUD de `repuestos`, sino como un pequeño módulo de inventario
integrado con la WorkOrder.

---

# 5.1 Concepto recomendado

Separar:

```text
Repuesto / Producto
```

de:

```text
Movimiento de inventario
```

y de:

```text
Repuesto utilizado en WorkOrder
```

Esto evita mezclar catálogo, stock y consumo.

Arquitectura recomendada:

```text
Part
   │
   ├── Stock
   │
   ├── Movements
   │
   └── WorkOrder usage
```

---

# 5.2 Catálogo de repuestos

Entidad futura:

```text
Part
```

Información sugerida:

```text
id
code
sku
name
description
brand
part_number
category_id
unit
cost_price
sale_price
minimum_stock
status
```

Debe permitir identificar un repuesto independientemente de una WorkOrder.

---

# 5.3 Categorías de repuestos

Entidad futura:

```text
PartCategory
```

Ejemplos:

```text
Filtros
Frenos
Lubricantes
Suspensión
Motor
Transmisión
Eléctrico
Refrigeración
Neumáticos
```

---

# 5.4 Proveedores

Futuro módulo:

```text
Supplier
```

Permitirá relacionar:

```text
Supplier
   ↓
Part
```

Posteriormente permitirá compras.

---

# 5.5 Inventario

No se recomienda guardar simplemente:

```text
parts.stock
```

sin historial.

Se recomienda implementar:

```text
Inventory
InventoryMovement
```

o una arquitectura equivalente.

Los movimientos pueden ser:

```text
purchase
sale
work_order
adjustment
return
transfer
```

Cada movimiento debería registrar:

```text
part_id
quantity
type
reference
unit_cost
created_by
created_at
```

---

# 5.6 Consumo de repuestos en WorkOrder

Una WorkOrder debe poder registrar:

```text
repuesto utilizado
cantidad
precio
costo
mecánico/responsable
fecha
```

Pero conviene **no reutilizar ciegamente WorkOrderItem** para esto.

La recomendación profesional es separar:

```text
WorkOrderItem
```

de:

```text
WorkOrderPart
```

o una entidad equivalente.

Motivo:

`WorkOrderItem` representa actualmente el trabajo derivado del presupuesto.

Un consumo de inventario tiene características diferentes:

```text
stock
cost
sale_price
quantity_used
inventory movement
```

---

# 5.7 Flujo recomendado de repuestos

```text
Budget
   ↓
BudgetItem(type = part)
   ↓
WorkOrder
   ↓
WorkOrderPart
   ↓
Stock reservation / consumption
   ↓
InventoryMovement
   ↓
Stock updated
```

Esto permitirá que el presupuesto indique qué repuesto se presupuestó, mientras que la WorkOrder registra qué repuesto realmente se utilizó.

---

# 5.8 Diferencia entre presupuestado y utilizado

Este punto es fundamental.

Ejemplo:

Presupuesto:

```text
2 litros de aceite
```

Trabajo realizado:

```text
1.7 litros utilizados
```

El sistema debería poder distinguir:

```text
presupuestado
utilizado
facturado
```

No asumir que:

```text
BudgetItem.quantity === WorkOrderPart.quantity
```

---

# 5.9 Reserva de stock

En una versión profesional se puede incorporar:

```text
available_stock
reserved_stock
```

Flujo:

```text
Presupuesto aprobado
        ↓
WorkOrder creada
        ↓
Reservar repuestos
        ↓
Trabajo ejecutado
        ↓
Consumir stock
```

Pero esta funcionalidad puede implementarse después del MVP de inventario.

---

# 5.10 Compras

Después del inventario:

```text
Purchase
PurchaseItem
Supplier
```

Flujo:

```text
Proveedor
   ↓
Compra
   ↓
Ingreso de stock
   ↓
InventoryMovement
```

---

# 5.11 Módulo de servicios

Debe existir eventualmente un catálogo formal:

```text
Service
ServiceCategory
```

con:

```text
name
description
standard_price
estimated_hours
status
```

Esto permitirá presupuestos más consistentes.

---

# 5.12 Mano de obra

El sistema ya contempla:

```text
Mechanic.hour_cost
Mechanic.commission_percentage
```

El siguiente nivel será calcular:

```text
horas estimadas
horas reales
costo mano de obra
precio de venta
comisión
```

Esto permitirá obtener rentabilidad real de una WorkOrder.

---

# 5.13 Facturación

Después de cerrar el flujo operativo:

```text
WorkOrder
   ↓
Finalización
   ↓
Factura / Invoice
```

Debe diferenciarse:

```text
Budget
```

de:

```text
Invoice
```

El presupuesto es una propuesta.

La factura representa una obligación económica real.

---

# 5.14 Pagos

Posteriormente:

```text
Payment
```

Permitirá:

```text
cash
card
transfer
other
```

y:

```text
partial payment
full payment
```

---

# 5.15 Entrega del vehículo

Flujo futuro:

```text
WorkOrder completed
        ↓
Invoice
        ↓
Payment
        ↓
Vehicle delivered
```

La entrega debería quedar registrada formalmente.

---

# 5.16 Historial del vehículo

Uno de los módulos de mayor valor para el taller será:

```text
VehicleHistory
```

Debe permitir consultar:

```text
recepciones
diagnósticos
presupuestos
órdenes de trabajo
reparaciones
repuestos utilizados
kilometraje
costos
```

Esto permitirá mostrar al usuario:

> Historial completo del vehículo.

---

# 5.17 Mantenimiento preventivo

Posteriormente:

```text
MaintenanceSchedule
```

Ejemplos:

```text
Cambio de aceite
Rotación de neumáticos
Cambio de filtros
Distribución
Revisión de frenos
```

Basado en:

```text
kilometraje
fecha
tiempo transcurrido
```

---

# 5.18 Notificaciones

Futuro módulo:

```text
Notification
```

Eventos posibles:

```text
presupuesto enviado
presupuesto aprobado
vehículo listo
pago pendiente
mantenimiento próximo
```

---

# 5.19 Reportes

Futuro módulo:

```text
Reports
```

Reportes recomendados:

```text
ventas
presupuestos
presupuestos aprobados
órdenes completadas
órdenes canceladas
repuestos consumidos
stock bajo
rentabilidad
productividad por mecánico
```

---

# 5.20 Auditoría

A medida que el sistema crezca será recomendable implementar:

```text
AuditLog
```

para registrar:

```text
usuario
acción
entidad
registro
valor anterior
valor nuevo
fecha
IP
```

Especialmente para:

```text
precios
stock
presupuestos
facturación
pagos
```

---

# 6. Roadmap recomendado por fases

## FASE 10 — Catálogo de repuestos

```text
10.1 Part
10.2 PartCategory
10.3 marcas / fabricantes
10.4 unidades de medida
10.5 CRUD de repuestos
10.6 permisos
10.7 Resources
10.8 Requests
10.9 validaciones
10.10 excepciones
```

---

## FASE 11 — Inventario

```text
11.1 Inventory
11.2 InventoryMovement
11.3 entradas
11.4 salidas
11.5 ajustes
11.6 stock mínimo
11.7 stock disponible
11.8 historial
11.9 permisos
11.10 auditoría
```

---

## FASE 12 — Repuestos en WorkOrder

```text
12.1 WorkOrderPart
12.2 agregar repuesto
12.3 quitar repuesto
12.4 modificar cantidad
12.5 reservar
12.6 consumir
12.7 devolver
12.8 sincronización con inventario
12.9 costo
12.10 precio de venta
```

---

## FASE 13 — Catálogo de servicios y mano de obra

```text
13.1 Service
13.2 ServiceCategory
13.3 precio estándar
13.4 tiempo estimado
13.5 horas reales
13.6 costo de mecánico
13.7 comisión
```

---

## FASE 14 — Cierre económico de WorkOrder

```text
14.1 cálculo de costo
14.2 cálculo de venta
14.3 margen
14.4 diferencias presupuesto vs realidad
14.5 rentabilidad
```

---

## FASE 15 — Facturación

```text
15.1 Invoice
15.2 InvoiceItem
15.3 numeración
15.4 impuestos
15.5 descuentos
15.6 total
15.7 estados
```

---

## FASE 16 — Pagos

```text
16.1 Payment
16.2 métodos de pago
16.3 pagos parciales
16.4 saldo
16.5 conciliación
```

---

## FASE 17 — Entrega

```text
17.1 VehicleDelivery
17.2 comprobante
17.3 kilometraje final
17.4 observaciones
17.5 firma/aceptación
```

---

## FASE 18 — Historial del vehículo

```text
18.1 historial
18.2 reparaciones
18.3 repuestos
18.4 servicios
18.5 costos
18.6 kilometraje
```

---

## FASE 19 — Mantenimiento preventivo

```text
19.1 MaintenancePlan
19.2 intervalos
19.3 kilometraje
19.4 fechas
19.5 alertas
```

---

## FASE 20 — Reportes y dashboard

```text
20.1 dashboard
20.2 ventas
20.3 rentabilidad
20.4 productividad
20.5 inventario
20.6 clientes
20.7 vehículos
```

---

# 7. Memoria técnica relevante

## 7.1 No confundir BudgetItem con WorkOrderItem

Actualmente:

```text
BudgetItem
```

maneja información económica:

```text
quantity
unit_price
total
```

Mientras:

```text
WorkOrderItem
```

representa ejecución del trabajo.

No asumir que ambas entidades deben permanecer idénticas.

---

# 7.2 No convertir WorkOrderItem en inventario

Esta es una decisión arquitectónica importante.

El futuro inventario debe tener sus propias entidades.

Recomendado:

```text
Part
Inventory
InventoryMovement
WorkOrderPart
```

en lugar de sobrecargar:

```text
WorkOrderItem
```

---

# 7.3 Presupuesto aprobado como origen de WorkOrder

Actualmente una WorkOrder nace desde:

```text
Budget.status = approved
```

No romper esta regla sin rediseñar explícitamente el flujo.

---

# 7.4 Una sola WorkOrder por Budget

Actualmente se evita crear múltiples WorkOrders para el mismo presupuesto.

Esta regla protege la integridad del flujo:

```text
Budget
   ↓
WorkOrder
```

---

# 7.5 WorkOrder completada no vuelve atrás

Una orden:

```text
completed
```

no debe:

```text
start
pause
resume
```

ni regresar a:

```text
pending
```

Esto fue probado explícitamente.

---

# 7.6 Cancelación

Una WorkOrder solamente puede cancelarse mientras está:

```text
pending
```

La cancelación produce también el estado correspondiente de sus items pendientes/en progreso.

---

# 7.7 Edición

La WorkOrder es editable solamente cuando está:

```text
pending
```

Una vez iniciado el trabajo, las modificaciones deben tratarse como operaciones específicas y no como edición libre.

---

# 7.8 Scope bindings

Mantener:

```php
scopeBindings()
```

para relaciones anidadas.

Es especialmente importante para:

```text
WorkOrder → WorkOrderItem
```

---

# 7.9 Excepciones

No volver al patrón de devolver:

```text
500
```

para errores de negocio conocidos.

Ejemplo:

```text
No se puede cancelar una WorkOrder completada
```

es un error de negocio y debe responder:

```text
422
```

con su correspondiente `error_code`.

---

# 7.10 Seguridad

No exponer en producción:

```text
stack trace
archivo
línea
clase de excepción
```

Los errores deben transformarse al contrato público de la API.

---

# 7.11 Validación vs regla de negocio

Separar:

```text
Request validation
```

de:

```text
Business validation
```

Ejemplo:

```text
budget_id debe ser entero
```

→ Request.

Mientras:

```text
El presupuesto debe estar aprobado para crear una WorkOrder
```

→ Service / dominio.

---

# 7.12 Integridad de estados

Los estados deben tratarse como una máquina de estados implícita.

No permitir transiciones arbitrarias.

Antes de agregar un nuevo estado, documentar:

```text
estado actual
    ↓
acción
    ↓
estado nuevo
```

---

# 7.13 API pública

El frontend debe depender del contrato de la API:

```text
success
status
message
data
errors
error_code
```

No debe depender de:

```text
estructura interna de Exceptions
nombres de clases PHP
SQL
stack traces
```

---

# 7.14 No hacer refactors innecesarios al migrar

Al migrar el proyecto a otra herramienta, primero conseguir:

```text
backend funcionando
↓
tests/regresión
↓
misma API
↓
misma base de datos
↓
recién después nuevas funcionalidades
```

No aprovechar la migración para cambiar simultáneamente:

```text
arquitectura
naming
API
base de datos
framework
```

porque dificultaría detectar regresiones.

---

# 8. Checklist para continuar en otra herramienta

> Plantilla para configurar el proyecto en una máquina nueva (no es un estado del proyecto). Los pasos concretos de
> instalación están en el [`README.md`](../README.md). La sección "Frontend" aplica al repositorio del frontend, que no
> forma parte de este.

## Backend

```text
[ ] Laravel 12 instalado
[ ] PHP compatible
[ ] Composer instalado
[ ] MySQL configurado
[ ] .env configurado
[ ] APP_KEY generado
[ ] migrations ejecutadas
[ ] seeders ejecutados
[ ] Sanctum funcionando
[ ] Spatie Permission funcionando
[ ] API respondiendo
```

## Frontend

```text
[ ] Node.js instalado
[ ] npm install
[ ] VITE_API_URL configurada
[ ] login funcionando
[ ] token/session funcionando
[ ] consumo de API funcionando
```

## Validación

```text
[ ] Login
[ ] Permissions
[ ] Client
[ ] Vehicle
[ ] Reception
[ ] Diagnostic
[ ] Budget
[ ] BudgetItem
[ ] WorkOrder
[ ] WorkOrderItem
[ ] Exceptions
[ ] HTTP status codes
```

---

# 9. Regla principal para el nuevo desarrollador/agente

Antes de implementar cualquier módulo nuevo:

1. Leer este documento completo.
2. Revisar las entidades existentes.
3. Revisar los Enums.
4. Revisar Services.
5. Revisar Resources.
6. Revisar Requests.
7. Revisar permisos.
8. Revisar excepciones.
9. Revisar rutas existentes.
10. No modificar contratos existentes sin justificarlo.

---

# 10. Principio arquitectónico futuro

El sistema debe evolucionar desde:

```text
Presupuestador
```

hacia:

```text
Sistema integral de gestión de taller
```

La arquitectura objetivo es:

```text
                    ┌──────────────┐
                    │    Cliente   │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   Vehículo   │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  Recepción   │
                    └──────┬───────┘
                           │
             ┌─────────────┼─────────────┐
             │             │             │
       Diagnóstico    Presupuesto     Checklist
             │             │
             │        ┌────▼─────┐
             │        │ Aprobado │
             │        └────┬─────┘
             │             │
             │       ┌─────▼──────┐
             └──────►│ WorkOrder  │
                     └─────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
           Servicios     Mano obra   Repuestos
              │            │            │
              │            │       ┌────▼──────┐
              │            │       │ Inventario│
              │            │       └────┬──────┘
              │            │            │
              └────────────┼────────────┘
                           │
                    ┌──────▼───────┐
                    │   Cierre     │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  Facturación │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │    Pago      │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   Entrega    │
                    └──────────────┘
```

---

# 11. Punto exacto donde continuar

El proyecto actualmente se encuentra en:

```text
FASE 20 — REPORTES Y DASHBOARD — CERRADA
```

Con la FASE 20 se completa el roadmap original (FASES 10–20). Pendientes sin fase asignada, a priorizar:

```text
Compras (Purchase / PurchaseItem → ingreso de stock)
Notificaciones (presupuesto enviado, vehículo listo, pago pendiente, mantenimiento próximo)
Exportación de reportes (CSV / PDF) y comprobantes imprimibles (factura, recibo, entrega)
Auditoría avanzada (AuditLog de precios, stock, facturación y pagos)
Frontend React 19 (consumo de esta API)
```

El orden de trabajo de abajo se usó para las FASES 10 a 13 y sigue siendo la referencia.

Pero la implementación debe comenzar primero por el **diseño funcional y de datos**, no directamente por el CRUD.

Orden recomendado:

```text
10.1 Definir catálogo de repuestos
      ↓
10.2 Definir categorías
      ↓
10.3 Definir proveedores
      ↓
10.4 Definir modelo de inventario
      ↓
10.5 Definir movimientos
      ↓
10.6 Definir relación WorkOrder ↔ repuestos
      ↓
10.7 Definir impacto económico
      ↓
10.8 Implementar migrations
      ↓
10.9 Models / Enums
      ↓
10.10 Services
      ↓
10.11 Requests
      ↓
10.12 Resources
      ↓
10.13 Controllers
      ↓
10.14 Permissions
      ↓
10.15 Exceptions
      ↓
10.16 Tests / regresión
```

La prioridad es **no crear un CRUD aislado de repuestos**, sino diseñar correctamente el vínculo entre:

```text
Catálogo
    +
Inventario
    +
WorkOrder
    +
Costos
    +
Precio de venta
```

para que el sistema pueda crecer posteriormente hacia compras, facturación y rentabilidad.

---

# 12. Estado final del handoff

## Construido

```text
[✓] Autenticación
[✓] Usuarios
[✓] Roles
[✓] Permisos
[✓] Clientes
[✓] Vehículos
[✓] Modelos de vehículos
[✓] Recepciones
[✓] Categorías de servicio
[✓] Checklist
[✓] Diagnóstico
[✓] Mecánicos
[✓] Presupuestos
[✓] Items de presupuesto
[✓] Estados de presupuesto
[✓] Orden de trabajo
[✓] Items de orden de trabajo
[✓] Estados de WorkOrder
[✓] Transiciones
[✓] API Resources
[✓] API Response
[✓] Exceptions
[✓] Error Codes
[✓] HTTP status standardization
[✓] Auditoría de Controllers
[✓] FASE 9.14
[✓] Catálogo de repuestos (FASE 10)
[✓] Categorías de repuestos (FASE 10)
[✓] Proveedores (FASE 10)
[✓] Inventario (FASE 11)
[✓] Movimientos de inventario (FASE 11)
[✓] Repuestos utilizados en WorkOrder (FASE 12)
[✓] Catálogo de servicios (FASE 13)
[✓] Asignación de mecánicos y comisiones (FASE 13)
[✓] Tiempo estimado, horas reales y costo del mecánico — opcionales (FASE 13)
[✓] Cierre económico, margen y liquidación de comisiones (FASE 14)
[✓] Facturación con timbrado e IVA incluido (FASE 15)
[✓] Pagos, recibos y resumen de caja (FASE 16)
[✓] Entrega del vehículo con comprobante y conformidad (FASE 17)
[✓] Roles del taller: recepcionista, jefe-taller, cajero, depósito
[✓] Historial del vehículo con resumen, visitas, repuestos y kilometraje (FASE 18)
[✓] Mantenimiento preventivo con planes, km estimado y alertas (FASE 19)
[✓] Reportes y dashboard por área (FASE 20)
```

## Próximo

```text
[ ] Compras
[ ] Notificaciones
[ ] Exportación de reportes y comprobantes
[ ] Auditoría avanzada
```

---

# 13. Regla de oro del proyecto

> **No diseñar cada módulo como una funcionalidad aislada.**

Cada nueva entidad debe analizarse considerando:

```text
¿Quién la crea?
¿Quién la modifica?
¿Quién la utiliza?
¿En qué estado puede estar?
¿Qué entidades dependen de ella?
¿Qué impacto económico tiene?
¿Qué pasa si se cancela?
¿Qué pasa si se elimina?
¿Qué permisos requiere?
¿Qué error_code necesita?
¿Cómo afecta al flujo de la WorkOrder?
¿Cómo afectará a facturación e inventario?
```

El objetivo final no es simplemente tener muchos CRUDs.

El objetivo es construir un **sistema coherente de gestión de taller**, donde:

```text
Recepción
    ↓
Diagnóstico
    ↓
Presupuesto
    ↓
Aprobación
    ↓
Orden de trabajo
    ↓
Ejecución
    ↓
Repuestos + Mano de obra
    ↓
Cierre
    ↓
Facturación
    ↓
Pago
    ↓
Entrega
    ↓
Historial
```

mantenga integridad de datos, reglas de negocio claras y una API consistente durante toda la evolución del sistema.

---

# 14. Discrepancias conocidas y deuda técnica (verificado el 2026-10-17; reverificado el 2026-10-19)

Registradas durante la revisión documental de la v1.2; cada una se corrigió después con una decisión del usuario
(2026-10-19). La verificación del frontend se quitó de la lista: no está en este repositorio y no hace falta
comprobarlo desde aquí. Cada deuda nueva necesita una decisión antes de tocar el código.

| # | Tema | Evidencia | Efecto |
|---|---|---|---|
| 1 | ~~Validación de "recepción abierta" con estados inexistentes~~ **Resuelto el 2026-10-19** | Las Requests buscaban `pending, diagnosis, approved, in_progress, waiting_parts` (strings; tres no existen en `ReceptionStatus`, faltaban `repair` y `completed`) | Ahora usan `ReceptionStatus::openStatuses()` (todo salvo `delivered`/`cancelled`). Al editar solo se valida si la recepción editada está abierta. Además, anular una entrega que reabriría la recepción cuando el vehículo ya tiene otra abierta → `DELIVERY_VEHICLE_HAS_OPEN_RECEPTION` (409) |
| 2 | ~~Estados de recepción sin transiciones~~ **Resuelto el 2026-10-19** | Ningún endpoint asignaba `in_progress`, `repair`, `completed` ni `cancelled` | Ahora el estado se deriva de presupuestos y OT (`ReceptionStatusService::sync()`) y existe la cancelación manual (`POST receptions/{reception}/cancel`). Ver 2.7 |
| 3 | ~~Estados de OT sin uso~~ **Resuelto el 2026-10-19** | `WorkOrderStatus::WAITING_PARTS` y `::DELIVERED` no se asignaban en ningún Service | Eliminados del enum y de las listas de "OT abierta" de la entrega y los reportes. La columna es `string` y nunca guardó esos valores: sin migración. Ver 2.15 |
| 4 | ~~Errores sin `error_code`~~ **Resuelto el 2026-10-19** | `ReceptionController` (categorías de recepción procesada, eliminar recepción procesada), `ReceptionCheckListController` (checklist de recepción procesada) y `DiagnosticItemPhotoController` (actualizar sin datos) llamaban a `errorResponse(mensaje, 422)` sin código | Ahora responden `RECEPTION_CATEGORIES_NOT_EDITABLE`, `RECEPTION_NOT_DELETABLE`, `RECEPTION_CHECKLIST_NOT_EDITABLE` y `DIAGNOSTIC_ITEM_PHOTO_NOTHING_TO_UPDATE`; mismo `422` y mismo mensaje |
| 5 | ~~IVA del presupuesto con `float`~~ **Resuelto el 2026-10-19** | `BudgetService::recalculateBudget()`: `$tax = $subtotal * $taxRate` con `config('budget.tax_rate') = 0.10`; el total del ítem (`BudgetItemController`, `BudgetItemSeeder`) también era `cantidad * precio` en `float`. Dos fuentes de IVA: `config/budget.php` (0.10) y `config/invoice.php` (`default_tax_rate = 10`) | Ahora todo con BCMath y half-up a 2 decimales (`BudgetService::itemTotal()`); el IVA del presupuesto usa `invoice.default_tax_rate` y se eliminó `config/budget.php`. Los presupuestos existentes no se recalculan (la diferencia posible era de un centavo y los aprobados no deben cambiar) |
| 6 | ~~Middleware sin registrar~~ **Resuelto el 2026-10-19** | `App\Http\Middleware\EnsureJsonResponse` no aparecía en `bootstrap/app.php` ni en las rutas (y respondía `406 {"error": "JSON only"}`, fuera del contrato). Sin `Accept: application/json`: request sin token → `500` HTML (redirección a una ruta `login` inexistente), validación → `302`, ruta inexistente → `404` HTML | Ahora es middleware global: fija `Accept: application/json` en `api/*` y todo responde con el contrato (`401`, `422`, `404`, `405`…). Test: `tests/Feature/Http/JsonResponseTest` |
| 7 | ~~Seeders no idempotentes~~ **Resuelto el 2026-10-19** | `BrandSeeder` (`insert`), `VehicleModelSeeder`, `ClientsSeeder`, `VehiclesSeeder`, `ReceptionSeeder`, `DiagnosticItemSeeder`, `BudgetSeeder` y `BudgetItemSeeder` creaban sin verificar si el dato existía, y referenciaban marcas, clientes, vehículos, categorías y presupuestos por id fijo (`VehiclesSeeder` además combinaba marcas con modelos de otra marca, p. ej. Hyundai + Corolla Cross) | Ahora omiten lo existente por clave natural (nombre, documento, placa, código de categoría; recepción si el vehículo ya tiene, presupuesto si la recepción ya tiene, ítems si el presupuesto ya tiene) y resuelven las relaciones por esas claves. `BudgetSeeder` crea por `BudgetService::create()` (código y estado derivado de la recepción). `DatabaseSeederTest` corre todo dos veces y compara conteos |
| 8 | ~~Tests que dependen de la red~~ **Resuelto el 2026-10-19** | `Store/UpdateUserRequest` y `Store/UpdateClientRequest` usan `email:rfc,dns`; los de usuario además `Password::uncompromised()` (simulado con `Http::fake` en los tests) | Los tests necesitaban resolver DNS (`gmail.com`). Ahora la consulta DNS depende de `config('validation.email_dns')` (`EMAIL_DNS_CHECK`, `true` por defecto); `phpunit.xml` la desactiva y los tests corren sin red. `ClientTest` verifica ambos modos con un dominio `.invalid` |
| 9 | ~~Rama remota antigua~~ **Resuelto el 2026-10-17** | `origin/feature/diagnostic-item-seeders-controllers-migrations-models` (commit `795d830519b28d1abc22ba7fee92da04e50af4a5`, 2026-08-12) sin mergear | Eliminada a pedido del usuario: sus archivos existían en `main` en versiones posteriores. Ese día también se eliminaron todas las ramas ya integradas en `main` (locales y remotas); su contenido sigue en el historial de `main` |
