# CLAUDE.md — Presupuestador

Instrucciones permanentes para trabajar en este repositorio sin romper lo existente.

| Documento | Para qué |
|---|---|
| `CLAUDE.md` (este) | Reglas operativas, convenciones y reglas de negocio vigentes |
| [`README.md`](README.md) | Instalación, configuración, comandos y estado general |
| [`docs/handoff.md`](docs/handoff.md) | Historia y decisiones de diseño por fase (FASES 9–20), roadmap original y **deuda técnica conocida (sección 14)** |
| [`docs/testing/guia-postman.md`](docs/testing/guia-postman.md) | Pruebas manuales de la API, encadenadas por fase |

Si una regla de este archivo contradice al código, **el código manda**: registrar la discrepancia (handoff, sección 14)
y avisar, en lugar de "corregir" por cuenta propia.

---

## 1. Estado actual (verificado el 2026-10-17, `main` @ `8b3027a`)

* **Backend/API:** Laravel 12.56, roadmap original completo (FASES 10–20). 190 rutas API, 235 tests en verde.
* **Frontend:** **no está en este repositorio.** El handoff prevé React 19, pero no hay código que lo confirme; solo
  CORS y Sanctum están configurados para `http://localhost:5173`. `package.json` y `resources/` son el scaffold de
  Laravel (Vite + Tailwind) sin uso. No asumir tecnología ni estructura del frontend.
* **Pendiente (sin diseño ni fase asignada):** compras, notificaciones, exportación de reportes y comprobantes
  imprimibles, auditoría avanzada, frontend. Ninguno tiene código hoy.

---

## 2. Metodología de trabajo

Antes de implementar cualquier cambio:

1. Inspeccionar el código existente relacionado (Service, Request, Resource, Enum, excepción, rutas, permisos, tests).
2. Identificar las reglas de negocio y los módulos afectados (ver sección 6 y la "regla de oro" del handoff, sección 13).
3. Proponer el cambio mínimo y explicar su motivo. Las decisiones de negocio se consultan, no se suponen.
4. Implementar solo el alcance autorizado.
5. Ejecutar los tests pertinentes y la suite completa antes de dar algo por terminado.
6. Verificar que no cambien contratos, permisos, `error_code` ni códigos HTTP existentes.
7. Informar archivos modificados, tests ejecutados y resultados.
8. No declarar una fase cerrada sin evidencia (código + tests).

En fases nuevas: primero diseño funcional y de datos (con las preguntas de negocio), después código.

### Git

* Nunca trabajar ni commitear en `main`. Una rama por tarea desde `origin/main`: `feature/…`, `fix/…`, `docs/…`, `test/…`.
* Commit y push solo cuando el usuario lo pide. El merge a `main` lo hace el usuario (salvo pedido explícito).
* Mensajes de commit en español, describiendo el porqué.

### Documentación por cambio

Cada fase o cambio de API actualiza, en la misma rama: este archivo (reglas), `docs/handoff.md` (decisiones e
historia), `README.md` (si cambia instalación, módulos o estado) y `docs/testing/guia-postman.md` (requests, bodies,
resultados esperados y casos de error).

---

## 3. Convenciones obligatorias

* La lógica de negocio va en **Services**; los Controllers solo reciben, autorizan, llaman al Service y devuelven
  un Resource con `ApiResponse`.
* Reutilizar Requests, Resources, Enums y excepciones existentes antes de crear nuevos.
* Mantener el **contrato público de la API** (formato de respuesta, nombres de campos, `error_code`, códigos HTTP).
* Estados y tipos formales siempre con **Enum** (`BudgetStatus::APPROVED`, nunca `'approved'`). Ojo: comparar enums
  con enums (`in_array($x, [Enum::A], true)`), nunca contra strings.
* Errores de negocio: excepción de dominio con `error_code` y `422` (o `409` en conflictos). Nunca `500` ni trazas.
* No cambiar rutas, permisos ni contratos sin justificación funcional.
* No refactorizar código estable por estilo. No modificar módulos terminados sin necesidad demostrable.
* No agregar dependencias sin evaluar necesidad y compatibilidad.
* No cambiar el esquema ni crear migraciones por iniciativa propia: proponerlas.
* **Las migraciones no llaman a código de la aplicación** (Services/Models): solo estructura.
* Dinero y cantidades con **BCMath** sobre strings (`App\Support\Decimal::round`), nunca `float`.
* Operaciones sobre varios registros en `DB::transaction`; concurrencia con `lockForUpdate`.
  Orden de bloqueo: OT → línea → `part_stocks`; factura → pago; recepción → entregas.
* Documentos emitidos (cierre económico, factura emitida, pago, entrega, movimiento de inventario) son **inmutables a
  nivel de modelo**: solo se anulan o se corrigen con otro registro.
* Rutas anidadas con `scopeBindings()` (grupo global en `routes/api.php`); si el parámetro hijo **no** es una relación
  del padre, usar `->withoutScopedBindings()` en esa ruta.
* Actualizaciones parciales con `PATCH`.
* Resources: `XResource` para listados, `ShowXResource` para detalle. `$this->when()` no se resuelve dentro de
  colecciones anidadas (`->map()`): agregar claves condicionales a mano.
* Mensajes al usuario en español; código, clases, campos y `error_code` en inglés.

### Permisos

* Cada Controller implementa `HasMiddleware` con `permission:<recurso>.<acción>` por método.
* El nombre del permiso del middleware debe existir **exactamente** en `RolesAndPermissionsSeeder`. El rol
  `administrador` pasa todo por `Gate::before` (`AppServiceProvider`), así que un nombre mal escrito no se nota con el
  admin: probar con un rol propio (ver `tests/Feature/Access`).
* Roles del taller en `RolesAndPermissionsSeeder::workshopRoles()`; `WorkshopRolesTest` verifica que todos sus
  permisos existan.

### Entorno (Windows / Laragon)

* Tests: `php artisan test` o `vendor/bin/phpunit` (SQLite en memoria). Si `pdo_sqlite` está deshabilitado:
  `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit`. Los tests de usuarios y clientes necesitan
  red (`email:rfc,dns`).
* No editar archivos con `Get-Content`/`Set-Content` de PowerShell 5.1: corrompen UTF-8 y agregan BOM. Usar las
  herramientas de edición.
* No ejecutar `migrate:fresh`, `db:wipe` ni seeders de datos de ejemplo sobre la base de desarrollo sin pedido
  explícito (ver README, sección de base de datos).

---

## 4. Arquitectura

```text
Controller (HasMiddleware) → Form Request → Service → Model/Eloquent → API Resource → ApiResponse
```

| Carpeta | Contenido |
|---|---|
| `app/Http/Controllers/Api` | 41 controllers |
| `app/Http/Requests` | Validación (mensajes en español) |
| `app/Services` | Reglas de negocio (18 services) |
| `app/Models` | Modelos Eloquent (muchos con `SoftDeletes`) |
| `app/Http/Resources` | Contrato JSON |
| `app/Enums` | Estados y tipos |
| `app/Exceptions` | `AppException` y una subclase por módulo |
| `app/Support/Decimal.php` | Redondeo BCMath |
| `app/Traits/ApiResponse.php` | `successResponse` / `errorResponse` |
| `bootstrap/app.php` | Mapeo de excepciones a JSON |
| `config/budget.php`, `invoice.php`, `maintenance.php` | Parámetros de negocio |

### Contrato de respuesta

```json
{ "success": true, "status": 200, "message": "...", "data": {}, "errors": null, "meta": null, "timestamp": "..." }
{ "success": false, "status": 422, "message": "...", "data": null, "errors": null, "error_code": "..." }
```

* Listados: `meta.pagination` (`total`, `perPage`, `currentPage`, `lastPage`).
* `bootstrap/app.php` mapea: 400 `BAD_REQUEST`, 401 `UNAUTHENTICATED`, 403 `FORBIDDEN`, 404 `RESOURCE_NOT_FOUND`,
  405 `METHOD_NOT_ALLOWED`, 406 `NOT_ACCEPTABLE`, 415 `UNSUPPORTED_MEDIA_TYPE`, 422 `VALIDATION_ERROR`,
  429 `TOO_MANY_REQUESTS`, 500 `INTERNAL_SERVER_ERROR`, 503 `SERVICE_UNAVAILABLE`, y cualquier `AppException` con su
  propio `status` y `error_code`. Solo si el request espera JSON (`Accept: application/json`).
* Excepciones de dominio: `BudgetException`, `WorkOrderException`, `WorkOrderPartException`, `PartException`,
  `SupplierException`, `InventoryException`, `ServiceException`, `InvoiceException`, `PaymentException`,
  `VehicleDeliveryException`, `MaintenanceException` (todas extienden `AppException(message, status, errorCode, errors)`).

### Autenticación

Sanctum con tokens Bearer: `POST /api/login` (público) devuelve `data.token`; todo lo demás está bajo
`auth:sanctum`. `POST /api/logout` revoca el token actual. Los tokens no expiran (`sanctum.expiration = null`).

---

## 5. Roles

| Rol | Función | Accesos clave |
|---|---|---|
| `administrador` | Todo (`Gate::before`) | Exclusivo: usuarios/roles/permisos, costos y margen (`work-orders.view-economics`), liquidar comisiones, anular entregas, timbrados, ABM de planes de mantenimiento, reporte de rentabilidad |
| `mechanic` | Ejecuta la OT | Diagnóstico, iniciar/pausar/completar OT e ítems, consumir/devolver repuestos, sus horas y comisiones, historial, estado de mantenimiento, dashboard (bloque taller) |
| `recepcionista` | Atiende al cliente | Clientes, vehículos, recepciones, checklist, presupuestos completos, registrar entregas, alertas de mantenimiento, reporte de presupuestos |
| `jefe-taller` | Organiza el trabajo | OT completas, ítems, repuestos en OT, asignación y horas de todos, diagnóstico, intervalos de mantenimiento por vehículo, reporte de productividad |
| `cajero` | Cobra | Facturas, pagos, resumen de caja, reportes de ventas y clientes |
| `deposito` | Stock | Repuestos, proveedores, inventario, repuestos en OT, reporte de inventario |

El rol del mecánico se llama `mechanic` (no `mecanico`). Ningún usuario del seeder tiene los cuatro últimos roles.

---

## 6. Reglas de negocio vigentes

Fuente: Services y tests. El detalle de diseño y su historia por fase está en el handoff (secciones 2.24–2.33).

### 6.1 Clientes, vehículos y datos maestros

* Al eliminar (soft delete) clientes, usuarios, marcas, modelos, vehículos y ítems de checklist se agrega
  `//deleted_<timestamp>` a sus campos únicos para liberarlos.
* Cliente: `document_number` único (letras, números, guiones), `email` único `email:rfc,dns`, teléfono `+?[1-9]\d{7,14}`.
* Vehículo: `plate` obligatoria salvo `no_plate = true` (entonces `chassis` obligatorio); placa y chasis únicos;
  `year` ≤ año actual; color, combustible y transmisión por enum. Rutas anidadas `clients/{client}/vehicles` (index,
  store, show; el `client_id` sale de la URL); editar y eliminar solo en `vehicles/{vehicle}`. La tabla es `vehicles`
  (el vehículo es una entidad propia; su dueño puede cambiar).
* Usuario: contraseña ≥ 12 con mayúsculas, minúsculas, números, símbolos y `uncompromised()`.

### 6.2 Recepción y checklist

* El vehículo debe pertenecer al cliente; no puede haber otra recepción abierta del vehículo (ver deuda técnica #1).
* Al menos una categoría de servicio; el checklist se genera con los ítems de esas categorías (sin repetir).
* Cambiar categorías solo con la recepción `pending` (regenera el checklist).
* Recepción "procesada" (`in_progress`, `completed`, `delivered`): no se cambian categorías ni checklist, no se elimina.
* Valor del checklist según el tipo del ítem: `boolean` → booleano, `number` → numérico, `text` → `good|bad|na`.
  El estado del checklist se calcula (`pending`, `in_progress`, `completed`).
* Estados: hoy solo la entrega cambia la recepción (`delivered`); ver handoff 2.7 y deuda técnica #2.
* Fotos en el disco `public` (`receptions/`), con scoped binding a la recepción.

### 6.3 Diagnóstico

* `mechanic_id` referencia `mechanics` (no `users`). Nace `pending`.
* Ítems con `severity` y `status` por enum; fotos en `diagnostic-items/{id}`; eliminar el ítem elimina sus fotos.
* Fotos de diagnóstico con permisos `diagnostic-item-photos.*`.

### 6.4 Presupuestos

* Código `BUD-…`. Estados: `draft → sent → approved`; `sent → rejected → draft` (reabrir, borra `approved_at`);
  cancelar desde `draft` o `sent`.
* Solo `draft` es editable (cabecera e ítems): `BUDGET_NOT_EDITABLE`. Enviar y aprobar exigen ítems
  (`BUDGET_WITHOUT_ITEMS`). Transición inválida: `BUDGET_NOT_SENDABLE|APPROVABLE|REJECTABLE|CANCELLABLE|REOPENABLE`.
* Totales: `subtotal` = suma de ítems; `tax` = subtotal × `config('budget.tax_rate')` (0.10); precios sin IVA.
* Ítems `labor | service | part`. `part_id` solo en `part` (repuesto `inactive` → `PART_NOT_USABLE`; unidades enteras
  → `INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED`). `service_id` solo en `service` (sin `unit_price` toma
  `standard_price`; inactivo → `SERVICE_NOT_USABLE`). `estimated_hours`: service = catálogo × cantidad si no se informa;
  labor opcional; part prohibido.

### 6.5 Orden de trabajo

* Nace de un presupuesto `approved` con ítems (`WORK_ORDER_BUDGET_NOT_APPROVED`, `WORK_ORDER_BUDGET_WITHOUT_ITEMS`);
  una por presupuesto (`WORK_ORDER_ALREADY_EXISTS`). Código `OT-000001` (máximo id + 1, incluye eliminadas).
* Un `WorkOrderItem` por `BudgetItem`. `WorkOrderItem` es ejecución, no inventario ni precios (los precios están en
  `BudgetItem`; los repuestos usados en `WorkOrderPart`).
* Estados: `pending → in_progress ⇄ paused`, `in_progress → completed`, `pending → cancelled`. Nunca vuelve atrás.
  `waiting_parts` y `delivered` existen en el enum pero no se usan.
* Editar (`mechanic_id`, `notes`) solo en `pending` (`WORK_ORDER_NOT_EDITABLE`); cambiar el responsable reasigna sus
  trabajos al 100%.
* Cancelar solo en `pending`: cancela ítems y líneas de repuestos abiertas (libera reservas).
* Completar: sin ítems `pending|in_progress` (`WORK_ORDER_HAS_PENDING_ITEMS`), sin repuestos `pending|reserved`
  (`WORK_ORDER_HAS_UNRESOLVED_PARTS`); en la misma transacción congela el cierre económico (6.9) y registra
  mantenimientos (6.13).
* Ítems: iniciar/completar/cancelar solo con la OT `in_progress`; iniciar desde `pending`, completar desde
  `in_progress`, cancelar solo `pending` (cancela sus líneas de repuestos). Permisos `work-order-items.*`. La respuesta
  es la OT completa.

### 6.6 Catálogo de repuestos e inventario

* `Part` (código `REP-…`, SKU único, unidad por enum, estados `active | inactive | discontinued`), `PartCategory`,
  `Supplier` y pivote `part_supplier` (`last_cost` se actualiza con las compras).
* Un depósito (`DEP-01`). `part_stocks` (on_hand, reserved, average_cost) cambia solo por
  `InventoryService::applyMovement()` con `lockForUpdate`.
* Movimientos inmutables (`INVENTORY_MOVEMENT_IMMUTABLE`) con signo; `balance_after` acumulado. Costo promedio
  ponderado (4 decimales) recalculado solo en entradas. Sin stock negativo: salidas contra disponible
  (`INSUFFICIENT_STOCK`). Ajuste = conteo físico (`INVENTORY_NO_DIFFERENCE`, `STOCK_BELOW_RESERVED`).
* Descontinuado: sin entradas (`PART_DISCONTINUED_NO_ENTRY`). `unit|set|kit` sin fracciones. Repuesto con stock no se
  elimina (`PART_HAS_STOCK`). Cantidades de inventario `decimal(12,3)`.

### 6.7 Repuestos en la OT (`WorkOrderPart`)

* Al crear la OT, una línea por `BudgetItem` `part` con `part_id`; intenta reservar (sin stock queda `pending`).
* Ciclo: `pending → reserved → consumed → returned` (si el neto llega a 0); `pending|reserved → cancelled`.
* Agregar/editar/reservar/cancelar con OT `pending|in_progress|paused`; consumir/devolver solo `in_progress`.
  OT cerrada: `WORK_ORDER_PARTS_NOT_EDITABLE`. Un consumo por línea; lo usado puede diferir de lo reservado.
* `unit_cost` sale del movimiento de consumo; `unit_price` del `BudgetItem` o del `sale_price`. Totales sobre la
  cantidad neta (consumida − devuelta), no se persisten.

### 6.8 Servicios, mecánicos y comisiones

* Catálogo `Service` (`SRV-…`, `standard_price`, `estimated_hours` opcional, `active|inactive`); usado no se elimina
  (`SERVICE_IN_USE`).
* Cada ítem `labor|service` nace asignado 100% al responsable. `PUT …/items/{item}/mechanics` reemplaza la asignación:
  sin porcentajes → partes iguales; con porcentajes → todos y suman 100 (`WORK_ORDER_ITEM_SHARES_INVALID`). Solo
  mecánicos `active`; solo `labor|service`; no con OT cerrada o ítem cancelado.
* `commission_percentage` y `hour_cost` se copian al asignar/cargar y se conservan. Comisión = total del ítem (sin IVA)
  × participación × %.
* Horas opcionales (`PATCH …/mechanics/{mechanic}/hours`, OT `in_progress|paused`). Sin horas el costo de mano de obra
  es `null` (desconocido), nunca cero.

### 6.9 Cierre económico

* Al completar la OT se crea `WorkOrderClosing` (inmutable, `WORK_ORDER_CLOSING_IMMUTABLE`) y las
  `WorkOrderCommission` congeladas.
* Venta = labor/service no cancelados + repuestos usados (neto × precio, incluye extras) + repuestos fuera de catálogo.
  Costo = repuestos + mano de obra conocida + comisiones. Si falta algún costo, el margen usa los conocidos e
  `is_cost_complete = false`. Importes sin IVA.
* `economic_summary` solo con `work-orders.view-economics`. Liquidación de comisiones por período
  (`MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE`).

### 6.10 Facturación

* Una factura vigente (`draft|issued`) por OT completada (`WORK_ORDER_ALREADY_INVOICED`, `WORK_ORDER_NOT_INVOICEABLE`).
  Líneas = lo realmente usado; debe coincidir con la venta del cierre (`INVOICE_TOTAL_MISMATCH`).
* Precios con IVA incluido (sistema sin IVA × (100 + tasa) / 100), guaraníes enteros (`config/invoice.php`).
  IVA 10% = total ÷ 11; 5% = total ÷ 21. Descuento solo por línea.
* `draft → issued → cancelled`. Emitir exige RUC/CI y, a crédito, vencimiento ≥ hoy; toma el número del timbrado
  activo y vigente con `lockForUpdate` (`001-001-0000001`); números no se reutilizan. Emitida es inmutable
  (`INVOICE_NOT_EDITABLE`). Con pagos vigentes no se anula (`INVOICE_HAS_PAYMENTS`).
* Fuera de alcance hoy: SIFEN, notas de crédito, PDF.

### 6.11 Pagos

* Solo facturas `issued` (`INVOICE_NOT_PAYABLE`); varios pagos hasta el total (`PAYMENT_EXCEEDS_BALANCE`,
  `INVOICE_ALREADY_PAID`). Monto entero; fecha no futura ni anterior a la emisión.
* Medios `cash|card|transfer|check`; referencia obligatoria salvo efectivo; banco en cheque. Vuelto solo en efectivo
  (`amount_received` ≥ `amount`; se guarda `change_amount`).
* Recibo `REC-000001` solo para facturas a crédito. El pago no se edita: se anula con motivo.
* Saldo y estado de cobro (`unpaid|partial|paid`) se calculan; resumen de caja por medio de pago.

### 6.12 Entrega del vehículo

* Una entrega vigente por recepción (`DELIVERY_ALREADY_REGISTERED`, 409). Ninguna OT abierta, al menos una completada,
  cada completada con factura emitida y, si es al contado, sin saldo.
* Km final ≥ km de la recepción; conformidad obligatoria; firma opcional (imagen). Comprobante `ENT-000001`.
* La recepción pasa a `delivered`; `vehicles.mileage` solo avanza; la OT sigue `completed`. Anular con motivo devuelve
  la recepción a su estado anterior.

### 6.13 Historial y mantenimiento preventivo

* Historial (`vehicles/{vehicle}/history`, `/parts`, `/mileage`): solo lectura, sin tablas propias; costos solo con
  `work-orders.view-economics`. Lecturas de km de recepciones y entregas vigentes, con `decreased` si bajan.
* Planes (`maintenance_plans`) por km y/o días; con seguimiento no se eliminan. Seguimiento por vehículo
  (`vehicle_maintenances`) con intervalos propios opcionales.
* Registro automático al completar una OT con el servicio del plan (fecha de cierre, km de la recepción); manual sin
  fechas anteriores ni km menores al último.
* Km actual estimado por promedio km/día entre lecturas. Estado `unknown|ok|due_soon|overdue` (umbral en
  `config/maintenance.php`); alertas solo de planes activos con registro.

### 6.14 Reportes y dashboard

* Solo lectura, JSON. Período por defecto: mes en curso. Ventas y clientes por fecha de emisión (con IVA);
  rentabilidad y productividad por cierre de la OT (sin IVA).
* Permiso por reporte (`reports.sales|budgets|profitability|productivity|inventory|clients`); el dashboard
  (`dashboard.view`) muestra solo los bloques permitidos.

---

## 7. Deuda técnica conocida

Ver `docs/handoff.md`, sección 14 (estados de recepción sin transiciones, validación de recepción abierta con estados
inexistentes, errores sin `error_code`, IVA del presupuesto con `float`, middleware `EnsureJsonResponse` sin
registrar, seeders no idempotentes, entre otros). No corregir sin decisión previa del usuario.
