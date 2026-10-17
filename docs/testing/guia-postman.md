# Guía de pruebas con Postman — Presupuestador

**Alcance:** API al cierre de la FASE 16 (pagos, recibos y resumen de caja), con la FASE 15 (facturación con timbrado e IVA incluido), la FASE 14 (cierre económico de la OT y liquidación de comisiones), incluidos los ajustes posteriores a la FASE 12 (rutas de vehículos por cliente, permisos de ítems de presupuesto, `error_code` del login y validaciones de texto) y la corrección de `roles/permissions-grouped-by-module`.
**Uso:** ejecutar los requests en el orden indicado; cada sección parte de los datos creados en las anteriores.

---

## 0. Configuración de Postman

### 0.1 Environment

| Variable | Valor inicial |
|---|---|
| `base_url` | `http://localhost:8000/api` |
| `admin_email` | `admin@mail.com.py` |
| `mechanic_email` | `jperez@mail.com.py` |
| `password` | valor de `ADMIN_PASSWORD` en `.env` |
| `token` | (vacío; se completa con el token del login) |
| `token_admin` | (vacío) |
| `token_mec` | (vacío) |

Variables de IDs que se irán completando con las respuestas: `client_id`, `client2_id`, `brand_id`, `model_id`, `vehicle_id`, `vehicle2_id`, `reception_id`, `budget_id`, `budget_item_id`, `mechanic_id`, `work_order_id`, `work_order_item_id`, `category_id`, `supplier_id`, `part_id`, `part_unit_id`, `part_status_id`, `movement_id`, `budget2_id`, `work_order2_id`, `work_order3_id`, `work_order4_id`, `line_id`, `extra_line_id`, `pending_line_id`, `mec1_id`, `mec2_id`, `service_category_id`, `service_id`, `service_unused_id`, `budget5_id`, `work_order5_id`, `labor_item_id`, `service_item_id`, `work_order6_id`, `work_order7_id`, `stamp_id`, `invoice_id`, `invoice_service_item_id`, `invoice_labor_item_id`, `invoice2_id`, `invoice3_id`, `invoice4_id`, `payment1_id`, `payment2_id`, `payment3_id`, `payment4_id`.

### 0.2 Headers comunes

Configurar a nivel **colección**:

| Header | Valor |
|---|---|
| `Accept` | `application/json` |
| `Content-Type` | `application/json` |
| `Authorization` | `Bearer {{token}}` |

> El login es el único request **sin** `Authorization`.
> Para actuar como mecánico, asignar `token = {{token_mec}}`; para volver, `token = {{token_admin}}`.

### 0.3 Formato de respuesta a verificar

Éxito:

```json
{ "success": true, "status": 200, "message": "...", "data": {}, "errors": null, "meta": null, "timestamp": "..." }
```

Error:

```json
{ "success": false, "status": 422, "message": "...", "data": null, "errors": null, "error_code": "..." }
```

En todos los casos: `status` del cuerpo = código HTTP; nunca aparecen `exception`, `file`, `line` ni `trace`.

### 0.4 Datos precargados por el seeder

| Dato | Valor |
|---|---|
| Mecánicos | `MEC-001`, `MEC-002` |
| Categorías de servicio | IDs `1`, `2`, … |
| Repuestos | `FIL-ACE-001`, `FIL-AIR-001`, `FRE-PAS-001`, `LUB-5W30-001`, `REF-REF-001` |
| Stock inicial | 12, 8, 4, 60 y 3.5 (`REF-REF-001` bajo mínimo) |

---

## FASE BASE — Autenticación y permisos

### B.1 Login administrador

| | |
|---|---|
| **Método** | `POST` |
| **Endpoint** | `{{base_url}}/login` |
| **Headers** | `Accept: application/json`, `Content-Type: application/json` |

Body éxito:

```json
{
  "email": "{{admin_email}}",
  "password": "{{password}}"
}
```

Esperado: `200`, `data.token`, `data.user.roles` incluye `administrador`. Copiar `data.token` en `token_admin` y en `token`.

Casos de error:

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Contraseña incorrecta | `{ "email": "{{admin_email}}", "password": "incorrecta" }` | 401 | `UNAUTHENTICATED` (`message: "Credenciales inválidas."`, `errors.email: ["Email incorrecto."]`) |
| Email mal formado | `{ "email": "admin", "password": "x" }` | 422 | `VALIDATION_ERROR` |
| Body vacío | `{}` | 422 | `VALIDATION_ERROR` |

### B.2 Login mecánico

| | |
|---|---|
| **Método** | `POST` |
| **Endpoint** | `{{base_url}}/login` |

```json
{
  "email": "{{mechanic_email}}",
  "password": "{{password}}"
}
```

Esperado: `200`. Copiar `data.token` en `token_mec`.

### B.3 Accesos sin autenticación

| Caso | Método / Endpoint | Headers | HTTP | error_code |
|---|---|---|---|---|
| Sin token | `GET {{base_url}}/parts` | sin `Authorization` | 401 | `UNAUTHENTICATED` |
| Token inválido | `GET {{base_url}}/parts` | `Authorization: Bearer xxx` | 401 | `UNAUTHENTICATED` |

### B.4 Logout

| | |
|---|---|
| **Método** | `POST` |
| **Endpoint** | `{{base_url}}/logout` |
| **Body** | ninguno |

Esperado: `200`. Repetir cualquier request con el mismo token → `401 UNAUTHENTICATED`. Después, volver a ejecutar B.1 y B.2.

### B.5 Permisos agrupados por módulo

`GET {{base_url}}/roles/permissions-grouped-by-module`

Esperado: `200`, `data` es una lista de `{ "module": "...", "list": [ { "id", "name" } ] }` (incluye los módulos `roles` y `work-orders`).

| Caso | Token | HTTP | error_code |
|---|---|---|---|
| Administrador | `{{token_admin}}` | 200 | — (antes de la corrección devolvía `404 RESOURCE_NOT_FOUND`) |
| Mecánico | `{{token_mec}}` | 403 | `FORBIDDEN` |

---

## FASE BASE — Datos maestros

Token: administrador.

### M.1 Crear cliente

`POST {{base_url}}/clients`

```json
{
  "document_number": "QA-1001",
  "first_name": "Lucia",
  "last_name": "Benitez",
  "email": "qa.lucia.benitez@gmail.com",
  "phone": "+595981000111"
}
```

Esperado: `201`. Guardar `data.id` → `client_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Documento repetido | mismo body | 422 | `VALIDATION_ERROR` (`errors.document_number`) |
| Teléfono inválido | `{ ..., "document_number": "QA-1003", "email": "qa3@gmail.com", "phone": "abc" }` | 422 | `VALIDATION_ERROR` (`errors.phone`) |
| Email sin dominio real | `{ ..., "email": "qa@dominio-inexistente.zz" }` | 422 | `VALIDATION_ERROR` (regla `email:dns`) |

Segundo cliente (para las pruebas de vehículos por cliente):

```json
{
  "document_number": "QA-1002",
  "first_name": "Pedro",
  "last_name": "Rojas",
  "email": "qa.pedro.rojas@gmail.com",
  "phone": "+595981000222"
}
```

Esperado: `201` → `client2_id`.

### M.2 Crear marca

`POST {{base_url}}/brands`

```json
{ "name": "Marca QA" }
```

Esperado: `201` → `brand_id`.

### M.3 Crear modelo

`POST {{base_url}}/vehicle-models`

```json
{ "name": "Modelo QA 1", "brand_id": {{brand_id}} }
```

Esperado: `201` → `model_id`.

### M.4 Crear vehículo

`POST {{base_url}}/vehicles`

```json
{
  "client_id": {{client_id}},
  "brand_id": {{brand_id}},
  "vehicle_model_id": {{model_id}},
  "plate": "QAA123",
  "no_plate": false,
  "year": 2020,
  "color": "white",
  "fuel_type": "gasoline",
  "transmission": "manual",
  "mileage": 45000
}
```

Esperado: `201` → `vehicle_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Sin chapa con `no_plate: false` | quitar `plate` | 422 | `VALIDATION_ERROR` (`errors.plate`) |
| Color fuera del enum | `"color": "fucsia"` | 422 | `VALIDATION_ERROR` (`errors.color`) |
| Chapa repetida | mismo body | 422 | `VALIDATION_ERROR` (`errors.plate`) |

### M.5 Vehículos por cliente (rutas anidadas)

Complementan a `/vehicles`. El propietario se toma de la URL.

**M.5.1 Alta anidada**

`POST {{base_url}}/clients/{{client_id}}/vehicles`

```json
{
  "client_id": {{client2_id}},
  "brand_id": {{brand_id}},
  "vehicle_model_id": {{model_id}},
  "plate": "QAB456",
  "no_plate": false,
  "year": 2021
}
```

Esperado: `201` → `vehicle2_id`. El vehículo queda con `client_id` = `{{client_id}}`: el `client_id` del body se ignora.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Chapa repetida | mismo body | 422 | `VALIDATION_ERROR` (`errors.plate`) |
| Body vacío | `{}` | 422 | `VALIDATION_ERROR` (`brand_id`, `vehicle_model_id`, `no_plate`; **no** `client_id`) |
| Modelo de otra marca | `vehicle_model_id` de un modelo que no pertenece a `brand_id` | 422 | `VALIDATION_ERROR` (`errors.vehicle_model_id`) |

**M.5.2 Listado del cliente**

`GET {{base_url}}/clients/{{client_id}}/vehicles`

Esperado: `200`, solo los vehículos del cliente (`QAA123`, `QAB456`), con `meta.pagination`.

`GET {{base_url}}/clients/{{client_id}}/vehicles?search=QAB` → `200`, solo `QAB456`.

**M.5.3 Detalle**

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Vehículo del cliente | `GET {{base_url}}/clients/{{client_id}}/vehicles/{{vehicle2_id}}` | 200 | — |
| Vehículo de otro cliente | `GET {{base_url}}/clients/{{client2_id}}/vehicles/{{vehicle2_id}}` | 404 | `RESOURCE_NOT_FOUND` |
| Cliente inexistente | `GET {{base_url}}/clients/999999/vehicles` | 404 | `RESOURCE_NOT_FOUND` |
| Editar por ruta anidada | `PATCH {{base_url}}/clients/{{client_id}}/vehicles/{{vehicle2_id}}` | 405 | `METHOD_NOT_ALLOWED` (editar se hace por `/vehicles/{id}`) |

**M.5.4 Permisos del mecánico** (token = `{{token_mec}}`)

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Listar | `GET {{base_url}}/clients/{{client_id}}/vehicles` | 200 | — |
| Detalle | `GET {{base_url}}/clients/{{client_id}}/vehicles/{{vehicle2_id}}` | 200 | — |
| Alta | `POST {{base_url}}/clients/{{client_id}}/vehicles` (body de M.5.1 con `"plate": "QAC789"`) | 403 | `FORBIDDEN` |

### M.6 Crear recepción

`POST {{base_url}}/receptions`

```json
{
  "client_id": {{client_id}},
  "vehicle_id": {{vehicle_id}},
  "service_category_ids": [1, 2],
  "reception_date": "2026-10-10",
  "fuel_level": "half",
  "mileage": 45000,
  "problem_description": "Cambio de aceite y revision general"
}
```

Esperado: `201` → `reception_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Sin categorías | `"service_category_ids": []` | 422 | `VALIDATION_ERROR` |
| Entrega anterior al ingreso | `"estimated_delivery_date": "2026-10-01"` | 422 | `VALIDATION_ERROR` |
| Recepción inexistente | `GET {{base_url}}/receptions/999999` | 404 | `RESOURCE_NOT_FOUND` |

### M.7 Obtener mecánico

`GET {{base_url}}/mechanics`

Esperado: `200`. Guardar `data[0].id` → `mechanic_id`.

---

## FASE BASE — Presupuestos

Token: administrador.

### P.1 Crear presupuesto

`POST {{base_url}}/budgets`

```json
{ "reception_id": {{reception_id}} }
```

Esperado: `201`, `status` `draft`, código `BUD-XXXXXX` → `budget_id`.

### P.2 Agregar ítem de mano de obra

`POST {{base_url}}/budgets/{{budget_id}}/items`

```json
{
  "type": "labor",
  "description": "Mano de obra cambio de aceite",
  "quantity": 1,
  "unit_price": 500
}
```

Esperado: `201` → `budget_item_id`; el presupuesto recalcula `subtotal`, `tax` y `total`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Cantidad cero | `{ ..., "quantity": 0 }` | 422 | `VALIDATION_ERROR` |
| Tipo inválido | `{ ..., "type": "otro" }` | 422 | `VALIDATION_ERROR` |

Permisos del mecánico (token = `{{token_mec}}`):

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Listar ítems | `GET {{base_url}}/budgets/{{budget_id}}/items` | — | 403 | `FORBIDDEN` |
| Ver ítem | `GET {{base_url}}/budgets/{{budget_id}}/items/{{budget_item_id}}` | — | 403 | `FORBIDDEN` |
| Crear ítem | `POST {{base_url}}/budgets/{{budget_id}}/items` | body de P.2 | 403 | `FORBIDDEN` |
| Editar ítem | `PATCH {{base_url}}/budgets/{{budget_id}}/items/{{budget_item_id}}` | `{ "unit_price": 1 }` | 403 | `FORBIDDEN` |
| Eliminar ítem | `DELETE {{base_url}}/budgets/{{budget_id}}/items/{{budget_item_id}}` | — | 403 | `FORBIDDEN` |

### P.3 Estados del presupuesto

| Paso | Método / Endpoint | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| Aprobar en `draft` | `POST {{base_url}}/budgets/{{budget_id}}/approve` | — | 422 | `BUDGET_NOT_APPROVABLE` |
| Rechazar en `draft` | `POST {{base_url}}/budgets/{{budget_id}}/reject` | — | 422 | `BUDGET_NOT_REJECTABLE` |
| Reabrir no rechazado | `POST {{base_url}}/budgets/{{budget_id}}/reopen` | — | 422 | `BUDGET_NOT_REOPENABLE` |
| Enviar | `POST {{base_url}}/budgets/{{budget_id}}/send` | — | 200 | `sent` |
| Enviar de nuevo | `POST {{base_url}}/budgets/{{budget_id}}/send` | — | 422 | `BUDGET_NOT_SENDABLE` |
| Agregar ítem con `sent` | `POST {{base_url}}/budgets/{{budget_id}}/items` | body de P.2 | 422 | `BUDGET_NOT_EDITABLE` |
| Aprobar | `POST {{base_url}}/budgets/{{budget_id}}/approve` | — | 200 | `approved`, `approved_at` informado |
| Cancelar aprobado | `POST {{base_url}}/budgets/{{budget_id}}/cancel` | — | 422 | `BUDGET_NOT_CANCELLABLE` |

Caso adicional (presupuesto nuevo y vacío): crear con P.1 y enviar sin ítems → `422 BUDGET_WITHOUT_ITEMS`; el presupuesto sigue en `draft`.

Ciclo de rechazo (presupuesto nuevo con un ítem): enviar → `reject` (200, `rejected`) → `reopen` (200, `draft`).

---

## FASE BASE — Órdenes de trabajo

Token: administrador.

### O.1 Crear OT

`POST {{base_url}}/work-orders`

```json
{
  "budget_id": {{budget_id}},
  "mechanic_id": {{mechanic_id}}
}
```

Esperado: `201`, código `OT-XXXXXX`, `status.value` `pending`, un ítem por ítem del presupuesto → `work_order_id`, `data.items[0].id` → `work_order_item_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Mismo presupuesto otra vez | mismo body | 422 | `WORK_ORDER_ALREADY_EXISTS` |
| Presupuesto no aprobado | `budget_id` de un presupuesto en `draft` | 422 | `WORK_ORDER_BUDGET_NOT_APPROVED` |
| Mecánico inexistente | `"mechanic_id": 999999` | 422 | `VALIDATION_ERROR` |

### O.2 Editar OT

`PATCH {{base_url}}/work-orders/{{work_order_id}}`

```json
{ "notes": "Cliente espera en el taller" }
```

Esperado: `200`.

| Caso | Body | HTTP | Resultado / error_code |
|---|---|---|---|
| Intentar cambiar código/estado | `{ "code": "HACK", "status": "completed" }` | 200 | `code` y `status` sin cambios |
| Editar OT en progreso | `{ "notes": "x" }` (después de O.3) | 422 | `WORK_ORDER_NOT_EDITABLE` |

### O.3 Estados de la OT

Body vacío en todos los casos.

| Paso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Pausar en `pending` | `POST {{base_url}}/work-orders/{{work_order_id}}/pause` | 422 | `WORK_ORDER_NOT_PAUSABLE` |
| Iniciar ítem con OT `pending` | `POST {{base_url}}/work-orders/{{work_order_id}}/items/{{work_order_item_id}}/start` | 422 | `WORK_ORDER_NOT_IN_PROGRESS` |
| Iniciar | `POST {{base_url}}/work-orders/{{work_order_id}}/start` | 200 | `in_progress` |
| Iniciar otra vez | `POST {{base_url}}/work-orders/{{work_order_id}}/start` | 409 | `WORK_ORDER_NOT_STARTABLE` |
| Cancelar en progreso | `POST {{base_url}}/work-orders/{{work_order_id}}/cancel` | 422 | `WORK_ORDER_NOT_CANCELLABLE` |
| Reanudar sin pausar | `POST {{base_url}}/work-orders/{{work_order_id}}/resume` | 422 | `WORK_ORDER_NOT_RESUMABLE` |
| Pausar | `POST {{base_url}}/work-orders/{{work_order_id}}/pause` | 200 | `paused` |
| Completar pausada | `POST {{base_url}}/work-orders/{{work_order_id}}/complete` | 422 | `WORK_ORDER_NOT_COMPLETABLE` |
| Reanudar | `POST {{base_url}}/work-orders/{{work_order_id}}/resume` | 200 | `in_progress` |
| Completar con ítems pendientes | `POST {{base_url}}/work-orders/{{work_order_id}}/complete` | 422 | `WORK_ORDER_HAS_PENDING_ITEMS` |

### O.4 Ítems de la OT

| Paso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Completar ítem `pending` | `POST {{base_url}}/work-orders/{{work_order_id}}/items/{{work_order_item_id}}/complete` | 422 | `WORK_ORDER_ITEM_NOT_COMPLETABLE` |
| Iniciar ítem | `POST .../items/{{work_order_item_id}}/start` | 200 | `in_progress` |
| Iniciar de nuevo | `POST .../items/{{work_order_item_id}}/start` | 422 | `WORK_ORDER_ITEM_NOT_STARTABLE` |
| Cancelar ítem en progreso | `POST .../items/{{work_order_item_id}}/cancel` | 422 | `WORK_ORDER_ITEM_NOT_CANCELLABLE` |
| Completar ítem | `POST .../items/{{work_order_item_id}}/complete` | 200 | `completed` |
| Ítem de otra OT en la URL | `POST {{base_url}}/work-orders/{{work_order_id}}/items/999999/start` | 404 | `RESOURCE_NOT_FOUND` |

Completar todos los ítems y luego:

| Paso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Completar OT | `POST {{base_url}}/work-orders/{{work_order_id}}/complete` | 200 | `completed`, `completed_at` informado |
| Iniciar OT completada | `POST {{base_url}}/work-orders/{{work_order_id}}/start` | 409 | `WORK_ORDER_NOT_STARTABLE` |
| Pausar OT completada | `POST {{base_url}}/work-orders/{{work_order_id}}/pause` | 422 | `WORK_ORDER_NOT_PAUSABLE` |

---

## FASE 10 — Catálogo de repuestos, categorías y proveedores

Token: administrador.

### 10.1 Crear categoría de repuestos

`POST {{base_url}}/part-categories`

```json
{
  "name": "QA Lubricantes",
  "description": "Categoria de pruebas"
}
```

Esperado: `201` → `category_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Nombre duplicado | mismo body | 422 | `VALIDATION_ERROR` |

### 10.2 Crear proveedor

`POST {{base_url}}/suppliers`

```json
{
  "name": "Proveedor QA",
  "tax_id": "80099999-9",
  "contact_name": "Mario Rojas",
  "phone": "+595 21 555 000",
  "email": "ventas@proveedorqa.com"
}
```

Esperado: `201` → `supplier_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| `tax_id` duplicado | mismo body | 422 | `VALIDATION_ERROR` |
| Nombre con carácter no permitido | `{ "name": "Proveedor @QA" }` | 422 | `VALIDATION_ERROR` (`errors.name`) |
| Dirección con carácter no permitido | `{ "name": "Proveedor Dir", "address": "Calle <falsa> 123" }` | 422 | `VALIDATION_ERROR` (`errors.address`) |
| Contacto con números | `{ "name": "Proveedor Contacto", "contact_name": "Juan 123" }` | 422 | `VALIDATION_ERROR` (`errors.contact_name`) |

Caracteres admitidos (además de letras, números y espacios): nombre y dirección `. , # - / ° º & ( ) '`; nombre de contacto solo letras, espacios y `. ' -`.

Caso válido:

```json
{
  "name": "Garcia & Hijos (Repuestos) N° 2",
  "tax_id": "80099998-8",
  "contact_name": "Maria-Jose O'Neill",
  "address": "Av. Mcal. Lopez 1234 (frente al banco) - Km 5"
}
```

Esperado: `201`.

### 10.3 Crear repuesto (litros)

> Caracteres admitidos (además de letras, números y espacios):
> nombre `. , - / ( ) " ' + %` · marca `& . - /` · descripción `. , ; : ( ) - / # % + & " ' ° º`.

`POST {{base_url}}/parts`

```json
{
  "sku": "QA-ACE-001",
  "name": "Aceite QA",
  "brand": "Shell",
  "part_category_id": {{category_id}},
  "unit": "liter",
  "cost_price": 100,
  "sale_price": 150,
  "minimum_stock": 5
}
```

Esperado: `201`, `code` `REP-XXXXXX`, `status.value` `active`, `stock.on_hand` `"0.000"`, `stock.is_below_minimum` `true` → `part_id`.

### 10.4 Crear repuesto (unidades)

`POST {{base_url}}/parts`

```json
{
  "sku": "QA-FIL-001",
  "name": "Filtro QA",
  "brand": "Mann",
  "part_category_id": {{category_id}},
  "unit": "unit",
  "cost_price": 35000,
  "sale_price": 50000,
  "minimum_stock": 2
}
```

Esperado: `201` → `part_unit_id`.

Casos de error de creación:

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| SKU duplicado | body de 10.3 | 422 | `VALIDATION_ERROR` (`errors.sku`) |
| Unidad inválida | `{ ..., "sku": "QA-X-1", "unit": "barrel" }` | 422 | `VALIDATION_ERROR` (`errors.unit`) |
| Nombre con carácter no permitido | `{ ..., "sku": "QA-X-2", "name": "Aceite @oferta" }` | 422 | `VALIDATION_ERROR` (`errors.name`) |
| Marca con carácter no permitido | `{ ..., "sku": "QA-X-4", "brand": "Bosch!" }` | 422 | `VALIDATION_ERROR` (`errors.brand`) |
| Descripción con carácter no permitido | `{ ..., "sku": "QA-X-6", "description": "<script>" }` | 422 | `VALIDATION_ERROR` (`errors.description`) |
| Nombre con números y símbolos (válido) | `{ ..., "sku": "QA-X-5", "name": "Aceite 5W-30 (sintetico) 100%" }` | 201 | — |
| Marca y descripción reales (válido) | `{ ..., "sku": "QA-X-7", "brand": "K&N", "description": "Para motor 1.6 (2015-2020), 4 cil." }` | 201 | — |
| Precio negativo | `{ ..., "sku": "QA-X-3", "cost_price": -1 }` | 422 | `VALIDATION_ERROR` |
| Categoría inactiva | `part_category_id` de una categoría con `is_active: false` | 422 | `PART_CATEGORY_INACTIVE` |
| Body vacío | `{}` | 422 | `VALIDATION_ERROR` |

### 10.5 Editar repuesto (PATCH parcial)

`PATCH {{base_url}}/parts/{{part_id}}`

```json
{ "sale_price": 160 }
```

Esperado: `200`, `sale_price` `"160.00"`; el resto de los campos sin cambios.

| Caso | Body | HTTP | Resultado / error_code |
|---|---|---|---|
| Intentar cambiar código/estado | `{ "code": "HACK-1", "status": "discontinued" }` | 200 | `code` y `status` sin cambios |

### 10.6 Consultar repuestos

| Caso | Método / Endpoint | HTTP | Resultado |
|---|---|---|---|
| Búsqueda | `GET {{base_url}}/parts?search=QA` | 200 | Solo repuestos QA |
| Por categoría | `GET {{base_url}}/parts?category_id={{category_id}}` | 200 | Filtrado |
| Por estado | `GET {{base_url}}/parts?status=active` | 200 | Filtrado |
| Detalle | `GET {{base_url}}/parts/{{part_id}}` | 200 | Incluye `category`, `suppliers`, `stock` |
| Inexistente | `GET {{base_url}}/parts/999999` | 404 | `RESOURCE_NOT_FOUND` |

### 10.7 Estados del repuesto

Crear un repuesto descartable (body de 10.4 con `"sku": "QA-EST-001"`, `"name": "Repuesto Estados"`) → `part_status_id`. Body vacío en todos los casos.

| Paso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Desactivar | `POST {{base_url}}/parts/{{part_status_id}}/deactivate` | 200 | `inactive` |
| Desactivar de nuevo | `POST {{base_url}}/parts/{{part_status_id}}/deactivate` | 422 | `PART_INVALID_STATUS_TRANSITION` |
| Activar | `POST {{base_url}}/parts/{{part_status_id}}/activate` | 200 | `active` |
| Descontinuar | `POST {{base_url}}/parts/{{part_status_id}}/discontinue` | 200 | `discontinued` |
| Activar descontinuado | `POST {{base_url}}/parts/{{part_status_id}}/activate` | 422 | `PART_DISCONTINUED` |
| Editar descontinuado | `PATCH {{base_url}}/parts/{{part_status_id}}` con `{ "sale_price": 1 }` | 422 | `PART_DISCONTINUED` |

### 10.8 Vincular proveedor a repuesto

`POST {{base_url}}/parts/{{part_id}}/suppliers`

```json
{
  "supplier_id": {{supplier_id}},
  "supplier_part_code": "SH-5W30",
  "last_cost": 95,
  "is_preferred": true
}
```

Esperado: `201`, `suppliers[0].pivot.is_preferred` `true`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Mismo proveedor otra vez | mismo body | 409 | `PART_SUPPLIER_ALREADY_ATTACHED` |
| Proveedor inactivo | `supplier_id` de un proveedor con `is_active: false` | 422 | `SUPPLIER_INACTIVE` |
| Proveedor inexistente | `"supplier_id": 999999` | 422 | `VALIDATION_ERROR` |

### 10.9 Editar vínculo

`PATCH {{base_url}}/parts/{{part_id}}/suppliers/{{supplier_id}}`

```json
{ "last_cost": 98 }
```

Esperado: `200`, `pivot.last_cost` `"98.00"`.

### 10.10 Eliminaciones con integridad

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Proveedor con repuestos vinculados | `DELETE {{base_url}}/suppliers/{{supplier_id}}` | 409 | `SUPPLIER_HAS_PARTS` |
| Categoría con repuestos | `DELETE {{base_url}}/part-categories/{{category_id}}` | 409 | `PART_CATEGORY_HAS_PARTS` |
| Desvincular proveedor | `DELETE {{base_url}}/parts/{{part_id}}/suppliers/{{supplier_id}}` | 200 | — |

> Volver a ejecutar 10.8 después de desvincular: se usa en la FASE 11.

### 10.11 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ver repuestos | `GET {{base_url}}/parts` | — | 200 | — |
| Crear repuesto | `POST {{base_url}}/parts` | body de 10.4 | 403 | `FORBIDDEN` |
| Cambiar estado | `POST {{base_url}}/parts/{{part_id}}/deactivate` | — | 403 | `FORBIDDEN` |
| Ver proveedores | `GET {{base_url}}/suppliers` | — | 403 | `FORBIDDEN` |

---

## FASE 11 — Inventario

Token: administrador. Repuesto de referencia: `{{part_id}}` (litros).

### 11.1 Stock inicial

`POST {{base_url}}/inventory/entries`

```json
{
  "part_id": {{part_id}},
  "type": "initial",
  "quantity": 10,
  "unit_cost": 100
}
```

Esperado: `201`, `type.value` `initial`, `quantity` `"10.000"`, `total_cost` `"1000.00"`, `balance_after` `"10.000"`, `average_cost_after` `"100.0000"`, `warehouse.code` `DEP-01`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Segundo stock inicial | mismo body | 409 | `INVENTORY_INITIAL_ALREADY_REGISTERED` |

### 11.2 Compra

`POST {{base_url}}/inventory/entries`

```json
{
  "part_id": {{part_id}},
  "type": "purchase",
  "quantity": 5,
  "unit_cost": 130,
  "supplier_id": {{supplier_id}},
  "document_number": "A-0001-00000123"
}
```

Esperado: `201`, `balance_after` `"15.000"`, `average_cost_after` `"110.0000"` ((10×100 + 5×130) / 15). En `GET {{base_url}}/parts/{{part_id}}`, `suppliers[0].pivot.last_cost` pasa a `"130.00"`. Guardar `data.id` → `movement_id`.

Casos de error de entradas:

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Tipo no permitido | `{ "part_id": {{part_id}}, "type": "work_order_return", "quantity": 1, "unit_cost": 1 }` | 422 | `VALIDATION_ERROR` (`errors.type`) |
| Cantidad cero / costo negativo | `{ "part_id": {{part_id}}, "type": "purchase", "quantity": 0, "unit_cost": -1 }` | 422 | `VALIDATION_ERROR` |
| Más de 3 decimales | `{ "part_id": {{part_id}}, "type": "purchase", "quantity": 1.2345, "unit_cost": 100 }` | 422 | `VALIDATION_ERROR` (`errors.quantity`) |
| Fracción en repuesto por unidad | `{ "part_id": {{part_unit_id}}, "type": "purchase", "quantity": 1.5, "unit_cost": 100 }` | 422 | `INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED` |
| Proveedor inactivo | `{ ..., "supplier_id": <proveedor inactivo> }` | 422 | `SUPPLIER_INACTIVE` |
| Repuesto descontinuado | `{ "part_id": {{part_status_id}}, "type": "purchase", "quantity": 1, "unit_cost": 100 }` | 422 | `PART_DISCONTINUED_NO_ENTRY` |
| Repuesto inexistente | `{ "part_id": 999999, "type": "purchase", "quantity": 1, "unit_cost": 100 }` | 422 | `VALIDATION_ERROR` |

### 11.3 Stock del repuesto por unidad (para la FASE 12)

`POST {{base_url}}/inventory/entries`

```json
{
  "part_id": {{part_unit_id}},
  "type": "initial",
  "quantity": 4,
  "unit_cost": 35000
}
```

Esperado: `201`.

### 11.4 Salida (baja)

`POST {{base_url}}/inventory/exits`

```json
{
  "part_id": {{part_id}},
  "type": "loss",
  "quantity": 0.5,
  "reason": "Derrame en deposito"
}
```

Esperado: `201`, `quantity` `"-0.500"`, `unit_cost` `"110.0000"`, `total_cost` `"-55.00"`, `balance_after` `"14.500"`, `average_cost_after` sin cambios.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Más que el disponible | `{ "part_id": {{part_id}}, "type": "loss", "quantity": 1000, "reason": "Prueba" }` | 422 | `INSUFFICIENT_STOCK` (`errors.quantity` indica el disponible) |
| Devolución sin proveedor | `{ "part_id": {{part_id}}, "type": "supplier_return", "quantity": 1, "reason": "Fallado" }` | 422 | `VALIDATION_ERROR` (`errors.supplier_id`) |
| Sin motivo | `{ "part_id": {{part_id}}, "type": "loss", "quantity": 1 }` | 422 | `VALIDATION_ERROR` (`errors.reason`) |
| Tipo no permitido | `{ "part_id": {{part_id}}, "type": "work_order_out", "quantity": 1, "reason": "x" }` | 422 | `VALIDATION_ERROR` (`errors.type`) |

Devolución a proveedor válida:

```json
{
  "part_id": {{part_id}},
  "type": "supplier_return",
  "quantity": 0.5,
  "supplier_id": {{supplier_id}},
  "reason": "Envase defectuoso"
}
```

Esperado: `201`, `type.direction` `out`, `balance_after` `"14.000"`.

### 11.5 Ajuste por conteo físico

`POST {{base_url}}/inventory/adjustments`

```json
{
  "part_id": {{part_id}},
  "counted_quantity": 13.5,
  "reason": "Conteo fisico mensual"
}
```

Esperado: `201`, `type.value` `adjustment_out`, `quantity` `"-0.500"`, `balance_after` `"13.500"`.

Ajuste positivo con costo:

```json
{
  "part_id": {{part_id}},
  "counted_quantity": 15,
  "unit_cost": 110,
  "reason": "Sobrante encontrado"
}
```

Esperado: `201`, `type.value` `adjustment_in`, `quantity` `"1.500"`, `balance_after` `"15.000"`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Sin diferencia | `{ "part_id": {{part_id}}, "counted_quantity": 15, "reason": "Conteo" }` | 422 | `INVENTORY_NO_DIFFERENCE` |
| Conteo negativo | `{ "part_id": {{part_id}}, "counted_quantity": -1, "reason": "Conteo" }` | 422 | `VALIDATION_ERROR` |
| Sin motivo | `{ "part_id": {{part_id}}, "counted_quantity": 14 }` | 422 | `VALIDATION_ERROR` (`errors.reason`) |
| Por debajo de lo reservado | `counted_quantity` menor al reservado (ver FASE 12, paso 12.2) | 422 | `STOCK_BELOW_RESERVED` |

### 11.6 Consultas

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Stock general | `GET {{base_url}}/inventory/stocks` | 200 | `quantity_on_hand`, `quantity_reserved`, `quantity_available`, `average_cost`, `stock_value`, `is_below_minimum`, `part` |
| Búsqueda | `GET {{base_url}}/inventory/stocks?search=QA` | 200 | Solo repuestos QA |
| Bajo mínimo | `GET {{base_url}}/inventory/stocks?below_minimum=1` | 200 | Incluye `REF-REF-001` |
| Con stock por categoría | `GET {{base_url}}/inventory/stocks?with_stock=1&category_id={{category_id}}` | 200 | Filtrado |
| Movimientos por tipo | `GET {{base_url}}/inventory/movements?type=purchase` | 200 | Solo compras |
| Movimientos por fecha | `GET {{base_url}}/inventory/movements?date_from=2026-10-10&date_to=2026-10-10` | 200 | Filtrado |
| Historial del repuesto | `GET {{base_url}}/parts/{{part_id}}/movements` | 200 | Del más nuevo al más viejo |
| Detalle de movimiento | `GET {{base_url}}/inventory/movements/{{movement_id}}` | 200 | Incluye `supplier`, `warehouse`, `document_number` |
| Fecha mal formada | `GET {{base_url}}/inventory/movements?date_from=ayer` | 422 | `VALIDATION_ERROR` |
| Rango invertido | `GET {{base_url}}/inventory/movements?date_from=2026-10-10&date_to=2026-10-01` | 422 | `VALIDATION_ERROR` |
| Tipo inexistente | `GET {{base_url}}/inventory/movements?type=otro` | 422 | `VALIDATION_ERROR` |
| Movimiento inexistente | `GET {{base_url}}/inventory/movements/999999` | 404 | `RESOURCE_NOT_FOUND` |
| Editar movimiento | `PATCH {{base_url}}/inventory/movements/{{movement_id}}` | 405 | `METHOD_NOT_ALLOWED` |
| Eliminar movimiento | `DELETE {{base_url}}/inventory/movements/{{movement_id}}` | 405 | `METHOD_NOT_ALLOWED` |

### 11.7 Integridad

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Eliminar repuesto con stock | `DELETE {{base_url}}/parts/{{part_id}}` | 409 | `PART_HAS_STOCK` |

### 11.8 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ver stock | `GET {{base_url}}/inventory/stocks` | — | 200 | — |
| Ver movimientos | `GET {{base_url}}/inventory/movements` | — | 200 | — |
| Registrar entrada | `POST {{base_url}}/inventory/entries` | body de 11.2 | 403 | `FORBIDDEN` |
| Registrar salida | `POST {{base_url}}/inventory/exits` | body de 11.4 | 403 | `FORBIDDEN` |
| Registrar ajuste | `POST {{base_url}}/inventory/adjustments` | body de 11.5 | 403 | `FORBIDDEN` |

---

## FASE 12 — Repuestos en la Orden de Trabajo

Token: administrador salvo indicación. Stock de partida de `{{part_id}}`: 15 L a promedio 110.

> En las rutas `.../parts/{{line_id}}`, el ID es el de la **línea de la OT**, no el del repuesto.

### 12.1 Presupuesto con repuesto vinculado al catálogo

1. `POST {{base_url}}/budgets` con `{ "reception_id": {{reception_id}} }` → `budget2_id`.
2. `POST {{base_url}}/budgets/{{budget2_id}}/items`:

```json
{
  "type": "part",
  "part_id": {{part_id}},
  "description": "Aceite QA",
  "quantity": 2,
  "unit_price": 150
}
```

Esperado: `201`, `part_id` = `{{part_id}}`, `part.code` informado.

3. `POST {{base_url}}/budgets/{{budget2_id}}/items`:

```json
{
  "type": "labor",
  "description": "Mano de obra",
  "quantity": 1,
  "unit_price": 500
}
```

Casos de error en ítems de presupuesto (sobre `{{budget2_id}}` en `draft`):

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| `part_id` en ítem que no es repuesto | `{ "type": "labor", "part_id": {{part_id}}, "description": "Mano de obra", "quantity": 1, "unit_price": 100 }` | 422 | `VALIDATION_ERROR` (`errors.part_id`) |
| Repuesto inexistente | `{ "type": "part", "part_id": 999999, "description": "X", "quantity": 1, "unit_price": 100 }` | 422 | `VALIDATION_ERROR` |
| Repuesto inactivo | `{ "type": "part", "part_id": <repuesto inactivo>, "description": "X", "quantity": 1, "unit_price": 100 }` | 422 | `PART_NOT_USABLE` |
| Fracción en repuesto por unidad | `{ "type": "part", "part_id": {{part_unit_id}}, "description": "Filtro", "quantity": 1.5, "unit_price": 100 }` | 422 | `INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED` |
| Ítem repuesto sin catálogo (válido) | `{ "type": "part", "description": "Repuesto especial", "quantity": 1, "unit_price": 100 }` | 201 | — (`part_id: null`) |

> Si se creó el ítem sin catálogo, eliminarlo con `DELETE {{base_url}}/budgets/{{budget2_id}}/items/{id}` para mantener los valores esperados de los pasos siguientes. Al cambiar un ítem a `"type": "service"` con `PATCH`, el `part_id` vuelve a `null`.

4. `POST {{base_url}}/budgets/{{budget2_id}}/send` → `200`.
5. `POST {{base_url}}/budgets/{{budget2_id}}/approve` → `200`.

### 12.2 Crear OT con reserva automática

`POST {{base_url}}/work-orders`

```json
{
  "budget_id": {{budget2_id}},
  "mechanic_id": {{mechanic_id}}
}
```

Esperado: `201` → `work_order2_id`; `data.parts[0].id` → `line_id`.

| Campo de `data.parts[0]` | Valor esperado |
|---|---|
| `status.value` | `reserved` |
| `is_budgeted` | `true` |
| `quantity` | `"2.000"` |
| `unit_price` | `"150.00"` |
| `work_order_item_id` | ID del ítem de la OT de tipo `part` |

Verificar en `GET {{base_url}}/parts/{{part_id}}`: `stock.reserved` `"2.000"`, `stock.available` `"13.000"`, `stock.on_hand` `"15.000"`.

Caso de borde — ajuste por debajo de lo reservado:

`POST {{base_url}}/inventory/adjustments`

```json
{
  "part_id": {{part_id}},
  "counted_quantity": 1,
  "reason": "Conteo"
}
```

Esperado: `422 STOCK_BELOW_RESERVED`.

### 12.3 Listar repuestos de la OT

`GET {{base_url}}/work-orders/{{work_order2_id}}/parts`

Esperado: `200`, una línea.

### 12.4 Modificar cantidad (OT `pending`)

`PATCH {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}`

```json
{ "quantity": 3 }
```

Esperado: `200`, `quantity` `"3.000"`; `stock.reserved` del repuesto pasa a `"3.000"`.

Volver a 2:

```json
{ "quantity": 2, "notes": "Ajuste de cantidad" }
```

Esperado: `200`; `stock.reserved` `"2.000"`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Más que el disponible | `{ "quantity": 1000 }` | 422 | `INSUFFICIENT_STOCK` |
| Cantidad cero | `{ "quantity": 0 }` | 422 | `VALIDATION_ERROR` |
| Más de 3 decimales | `{ "quantity": 1.2345 }` | 422 | `VALIDATION_ERROR` |

### 12.5 Reglas de estado antes de iniciar

Body vacío salvo indicación.

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Reservar línea ya reservada | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}/reserve` | — | 422 | `WORK_ORDER_PART_INVALID_STATUS_TRANSITION` |
| Consumir con OT `pending` | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}/consume` | `{ "quantity_used": 2 }` | 422 | `WORK_ORDER_NOT_IN_PROGRESS` |
| Devolver con OT `pending` | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}/return` | `{ "quantity": 1, "reason": "Prueba" }` | 422 | `WORK_ORDER_NOT_IN_PROGRESS` |
| Línea inexistente / de otra OT | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/999999/cancel` | — | 404 | `RESOURCE_NOT_FOUND` |

### 12.6 Agregar repuesto extra (no presupuestado)

`POST {{base_url}}/work-orders/{{work_order2_id}}/parts`

```json
{
  "part_id": {{part_unit_id}},
  "quantity": 1
}
```

Esperado: `201`, `work_order_item_id` `null`, `is_budgeted` `false`, `unit_price` `"50000.00"` (precio de catálogo), `status.value` `reserved` → `extra_line_id`.

Con precio informado:

```json
{
  "part_id": {{part_unit_id}},
  "quantity": 1,
  "unit_price": 45000,
  "notes": "Precio acordado con el cliente"
}
```

Esperado: `201`, `unit_price` `"45000.00"`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Repuesto inactivo | `{ "part_id": <repuesto inactivo>, "quantity": 1 }` | 422 | `PART_NOT_USABLE` |
| Fracción en repuesto por unidad | `{ "part_id": {{part_unit_id}}, "quantity": 1.5 }` | 422 | `INVENTORY_FRACTIONAL_QUANTITY_NOT_ALLOWED` |
| Repuesto inexistente | `{ "part_id": 999999, "quantity": 1 }` | 422 | `VALIDATION_ERROR` |
| Sin cantidad | `{ "part_id": {{part_unit_id}} }` | 422 | `VALIDATION_ERROR` |
| Precio negativo | `{ "part_id": {{part_unit_id}}, "quantity": 1, "unit_price": -5 }` | 422 | `VALIDATION_ERROR` |

### 12.7 Línea sin stock (pendiente) y reserva manual

`POST {{base_url}}/work-orders/{{work_order2_id}}/parts`

```json
{
  "part_id": {{part_unit_id}},
  "quantity": 10
}
```

Esperado: `201`, `status.value` `pending` (no hay 10 unidades disponibles) → `pending_line_id`.

| Paso | Método / Endpoint | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| Reservar sin stock | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{pending_line_id}}/reserve` | — | 422 | `INSUFFICIENT_STOCK` |
| Bajar cantidad a 1 | `PATCH {{base_url}}/work-orders/{{work_order2_id}}/parts/{{pending_line_id}}` | `{ "quantity": 1 }` | 200 | Sigue `pending` |
| Reservar | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{pending_line_id}}/reserve` | — | 200 | `reserved` |
| Cancelar línea | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{pending_line_id}}/cancel` | — | 200 | `cancelled`; se libera la reserva |
| Cancelar de nuevo | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{pending_line_id}}/cancel` | — | 422 | `WORK_ORDER_PART_INVALID_STATUS_TRANSITION` |

### 12.8 Iniciar la OT

`POST {{base_url}}/work-orders/{{work_order2_id}}/start` → `200`, `in_progress`.

### 12.9 Consumir (token = `{{token_mec}}`)

`POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}/consume`

```json
{
  "quantity_used": 1.7,
  "notes": "Se uso menos de lo presupuestado"
}
```

Esperado: `200`.

| Campo | Valor esperado |
|---|---|
| `status.value` | `consumed` |
| `quantity_consumed` | `"1.700"` |
| `quantity_net` | `"1.700"` |
| `unit_cost` | `"110.0000"` |
| `total_cost` | `"187.00"` |
| `total_price` | `"255.00"` |

Verificar:

- `GET {{base_url}}/parts/{{part_id}}` → `stock.on_hand` `"13.300"`, `stock.reserved` `"0.000"`.
- `GET {{base_url}}/parts/{{part_id}}/movements` → el más reciente es `work_order_out`, `quantity` `"-1.700"`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Consumir de nuevo | `{ "quantity_used": 1 }` | 422 | `WORK_ORDER_PART_INVALID_STATUS_TRANSITION` |
| Sin cantidad | `{}` | 422 | `VALIDATION_ERROR` |
| Cantidad cero | `{ "quantity_used": 0 }` | 422 | `VALIDATION_ERROR` |

Consumo mayor al disponible (sobre `{{extra_line_id}}`, reservada con 1 unidad):

```json
{ "quantity_used": 100 }
```

Esperado: `422 INSUFFICIENT_STOCK`; la reserva y el stock no cambian.

### 12.10 Devolver (token = `{{token_mec}}`)

`POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}/return`

```json
{
  "quantity": 0.2,
  "reason": "Sobrante"
}
```

Esperado: `200`, `status.value` `consumed`, `quantity_returned` `"0.200"`, `quantity_net` `"1.500"`, `total_cost` `"165.00"`, `total_price` `"225.00"`. El movimiento `work_order_return` usa `unit_cost` `"110.0000"` (costo de la línea).

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Devolver más que el neto | `{ "quantity": 5, "reason": "Sobrante" }` | 422 | `WORK_ORDER_PART_RETURN_EXCEEDS_CONSUMED` |
| Sin motivo | `{ "quantity": 0.1 }` | 422 | `VALIDATION_ERROR` (`errors.reason`) |
| Devolver una línea no consumida | sobre `{{extra_line_id}}`: `{ "quantity": 1, "reason": "x" }` | 422 | `WORK_ORDER_PART_INVALID_STATUS_TRANSITION` |

### 12.11 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ver repuestos de la OT | `GET {{base_url}}/work-orders/{{work_order2_id}}/parts` | — | 200 | — |
| Agregar repuesto | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts` | `{ "part_id": {{part_unit_id}}, "quantity": 1 }` | 403 | `FORBIDDEN` |
| Modificar línea | `PATCH {{base_url}}/work-orders/{{work_order2_id}}/parts/{{extra_line_id}}` | `{ "quantity": 2 }` | 403 | `FORBIDDEN` |
| Reservar | `POST .../parts/{{extra_line_id}}/reserve` | — | 403 | `FORBIDDEN` |
| Cancelar | `POST .../parts/{{extra_line_id}}/cancel` | — | 403 | `FORBIDDEN` |

### 12.12 Completar la OT con repuestos sin resolver

Token: administrador. Iniciar y completar cada ítem de la OT (`.../items/{id}/start` y `.../items/{id}/complete`).

| Paso | Método / Endpoint | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| Completar con `{{extra_line_id}}` reservada | `POST {{base_url}}/work-orders/{{work_order2_id}}/complete` | — | 422 | `WORK_ORDER_HAS_UNRESOLVED_PARTS` |
| Cancelar la línea extra | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts/{{extra_line_id}}/cancel` | — | 200 | `cancelled` |
| Completar | `POST {{base_url}}/work-orders/{{work_order2_id}}/complete` | — | 200 | `completed` |

> Si en 12.6 se creó también la línea con precio informado, cancelarla o consumirla antes de completar.

Verificar en la respuesta de completar: `parts_summary.total_cost` `"165.00"`, `parts_summary.total_price` `"225.00"`, `parts_summary.unresolved_count` `0`.

### 12.13 OT completada: repuestos congelados

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Agregar repuesto | `POST {{base_url}}/work-orders/{{work_order2_id}}/parts` | `{ "part_id": {{part_id}}, "quantity": 1 }` | 422 | `WORK_ORDER_PARTS_NOT_EDITABLE` |
| Modificar línea | `PATCH {{base_url}}/work-orders/{{work_order2_id}}/parts/{{line_id}}` | `{ "notes": "x" }` | 422 | `WORK_ORDER_PARTS_NOT_EDITABLE` |
| Devolver | `POST .../parts/{{line_id}}/return` | `{ "quantity": 0.1, "reason": "x" }` | 422 | `WORK_ORDER_NOT_IN_PROGRESS` |

### 12.14 Cancelación de la OT libera reservas

1. Crear presupuesto nuevo con un ítem `part` de `{{part_id}}` (cantidad 3), enviar y aprobar (como 12.1).
2. Crear la OT (como 12.2) → `work_order3_id`. Verificar `stock.reserved` `"3.000"`.
3. `POST {{base_url}}/work-orders/{{work_order3_id}}/cancel` → `200`, `parts[0].status.value` `cancelled`.
4. Verificar `stock.reserved` `"0.000"` y `stock.on_hand` sin cambios.

### 12.15 Cancelación de un ítem libera su repuesto

1. Repetir 12.14 pasos 1–2 → `work_order4_id`.
2. `POST {{base_url}}/work-orders/{{work_order4_id}}/start` → `200`.
3. `POST {{base_url}}/work-orders/{{work_order4_id}}/items/{id del ítem part}/cancel` → `200`.
4. `GET {{base_url}}/work-orders/{{work_order4_id}}/parts` → la línea vinculada está `cancelled`; `stock.reserved` vuelve al valor previo.

### 12.16 OT sin stock al crearse

1. Presupuesto con ítem `part` de `{{part_unit_id}}`, cantidad mayor al disponible (por ejemplo 50), enviar y aprobar.
2. Crear la OT → `201` (se crea igual).
3. Esperado: `parts[0].status.value` `pending`, `parts_summary.unresolved_count` `1`.

### 12.17 Devolución de repuesto descontinuado

1. Sobre una OT en progreso con una línea consumida, descontinuar el repuesto: `POST {{base_url}}/parts/{id}/discontinue`.
2. `POST .../parts/{línea}/return` con `{ "quantity": 1, "reason": "Sobrante" }` → `200` (permitido).

---

## FASE 13 — Servicios y mano de obra (comisiones)

Token: administrador salvo indicación.

> Comisión de cada mecánico = **total del ítem (sin IVA) × % del trabajo × % de comisión del mecánico**. Solo los ítems `labor` y `service` generan comisión.
> Tiempo estimado, horas reales y costo del mecánico son **opcionales**: sin horas cargadas, el costo de mano de obra es `null` (desconocido), nunca cero.
> Costo por hora del seeder: `MEC-001` **45.000**, `MEC-002` **35.000**.

Mecánicos del seeder: `MEC-001` (usuario `jperez`, comisión **10%**) y `MEC-002` (usuario `cgomez`, comisión **8.5%**). En `GET {{base_url}}/mechanics` guardar sus IDs en `mec1_id` y `mec2_id`.

### 13.1 Categorías de servicio

`GET {{base_url}}/service-categories`

Esperado: `200`, lista con `id`, `code`, `name`, `services_count` (General, Mecánica, Chapería, Electricidad, Neumáticos). Guardar el ID de "Mecánica" → `service_category_id`.

### 13.2 Crear servicio

`POST {{base_url}}/services`

```json
{
  "service_category_id": {{service_category_id}},
  "name": "Alineacion QA",
  "description": "Incluye 4 ruedas (hasta R17).",
  "standard_price": 120000,
  "estimated_hours": 1.5
}
```

Esperado: `201`, `code` `SRV-XXXXXX`, `status.value` `active`, `standard_price` `"120000.00"`, `estimated_hours` `"1.50"` → `service_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Body vacío | `{}` | 422 | `VALIDATION_ERROR` (`service_category_id`, `name`, `standard_price`) |
| Nombre con carácter no permitido | `{ "service_category_id": {{service_category_id}}, "name": "Servicio @promo", "standard_price": 1 }` | 422 | `VALIDATION_ERROR` (`errors.name`) |
| Precio negativo | `{ "service_category_id": {{service_category_id}}, "name": "Servicio QA", "standard_price": -1 }` | 422 | `VALIDATION_ERROR` |
| Horas estimadas negativas | `{ "service_category_id": {{service_category_id}}, "name": "Servicio QA", "standard_price": 1, "estimated_hours": -1 }` | 422 | `VALIDATION_ERROR` (`errors.estimated_hours`) |
| Categoría inexistente | `{ "service_category_id": 999999, "name": "Servicio QA", "standard_price": 1 }` | 422 | `VALIDATION_ERROR` |

Crear un segundo servicio descartable (mismo body con `"name": "Servicio Descartable QA"`) → `service_unused_id`.

### 13.3 Consultar, editar y cambiar estado

| Caso | Método / Endpoint | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| Listar | `GET {{base_url}}/services?search=QA` | — | 200 | Servicios QA con `meta.pagination` |
| Filtrar por categoría | `GET {{base_url}}/services?category_id={{service_category_id}}` | — | 200 | Filtrado |
| Detalle | `GET {{base_url}}/services/{{service_id}}` | — | 200 | Incluye `description` y `category` |
| Editar (PATCH parcial) | `PATCH {{base_url}}/services/{{service_id}}` | `{ "standard_price": 125000 }` | 200 | `standard_price` `"125000.00"`, el resto sin cambios |
| Volver al precio original | `PATCH {{base_url}}/services/{{service_id}}` | `{ "standard_price": 120000 }` | 200 | — |
| Activar uno activo | `POST {{base_url}}/services/{{service_unused_id}}/activate` | — | 422 | `SERVICE_INVALID_STATUS_TRANSITION` |
| Desactivar | `POST {{base_url}}/services/{{service_unused_id}}/deactivate` | — | 200 | `inactive` |
| Eliminar sin uso | `DELETE {{base_url}}/services/{{service_unused_id}}` | — | 200 | — |
| Inexistente | `GET {{base_url}}/services/999999` | — | 404 | `RESOURCE_NOT_FOUND` |

### 13.4 Permisos del mecánico sobre el catálogo (token = `{{token_mec}}`)

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ver categorías | `GET {{base_url}}/service-categories` | — | 200 | — |
| Ver servicios | `GET {{base_url}}/services` | — | 200 | — |
| Crear | `POST {{base_url}}/services` | body de 13.2 | 403 | `FORBIDDEN` |
| Editar | `PATCH {{base_url}}/services/{{service_id}}` | `{ "standard_price": 1 }` | 403 | `FORBIDDEN` |
| Desactivar | `POST {{base_url}}/services/{{service_id}}/deactivate` | — | 403 | `FORBIDDEN` |

### 13.5 Presupuesto con servicio del catálogo

1. `POST {{base_url}}/budgets` con `{ "reception_id": {{reception_id}} }` → `budget5_id`.
2. Servicio **sin precio** (toma el de lista):

`POST {{base_url}}/budgets/{{budget5_id}}/items`

```json
{
  "type": "service",
  "service_id": {{service_id}},
  "description": "Alineacion y balanceo",
  "quantity": 1
}
```

Esperado: `201`, `service.code` informado, `unit_price` `"120000.00"`, `total` `"120000.00"`, `estimated_hours` `"1.50"` (catálogo × cantidad).

3. Mano de obra fuera de catálogo:

```json
{
  "type": "labor",
  "description": "Cambio de embrague",
  "quantity": 1,
  "unit_price": 200000,
  "estimated_hours": 3
}
```

Esperado: `201`, `estimated_hours` `"3.00"` (opcional en mano de obra).

Casos de error y borde (sobre `{{budget5_id}}` en `draft`):

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Servicio con precio propio (válido) | `{ "type": "service", "service_id": {{service_id}}, "description": "Con descuento", "quantity": 1, "unit_price": 100000 }` | 201 | — (`unit_price` `"100000.00"`) |
| `service_id` en ítem que no es servicio | `{ "type": "labor", "service_id": {{service_id}}, "description": "Mano de obra", "quantity": 1, "unit_price": 100 }` | 422 | `VALIDATION_ERROR` (`errors.service_id`) |
| Servicio sin catálogo ni precio | `{ "type": "service", "description": "Trabajo especial", "quantity": 1 }` | 422 | `VALIDATION_ERROR` (`errors.unit_price`) |
| Repuesto con horas estimadas | `{ "type": "part", "description": "Filtro", "quantity": 1, "unit_price": 1, "estimated_hours": 1 }` | 422 | `VALIDATION_ERROR` (`errors.estimated_hours`) |
| Servicio con horas propias (válido) | `{ "type": "service", "service_id": {{service_id}}, "description": "Compleja", "quantity": 1, "estimated_hours": 2.25 }` | 201 | — (`estimated_hours` `"2.25"`) |
| Servicio inactivo | crear y desactivar un servicio; `{ "type": "service", "service_id": <inactivo>, "description": "X", "quantity": 1 }` | 422 | `SERVICE_NOT_USABLE` |
| Eliminar un servicio usado | `DELETE {{base_url}}/services/{{service_id}}` | 409 | `SERVICE_IN_USE` |

> Eliminar los ítems "Con descuento" y "Compleja" si se crearon, para mantener los valores esperados (`DELETE {{base_url}}/budgets/{{budget5_id}}/items/{id}`). Al cambiar un ítem a `"type": "labor"` con `PATCH`, el `service_id` vuelve a `null`.

4. `POST {{base_url}}/budgets/{{budget5_id}}/send` → `200`; `POST .../approve` → `200`.

### 13.6 OT con asignación automática al responsable

`POST {{base_url}}/work-orders`

```json
{
  "budget_id": {{budget5_id}},
  "mechanic_id": {{mec1_id}}
}
```

Esperado: `201` → `work_order5_id`. Guardar el ID del ítem `labor` → `labor_item_id` y del ítem `service` → `service_item_id`.

| Verificación | Valor esperado |
|---|---|
| `items[labor].mechanics[0]` | `mechanic_id` = `{{mec1_id}}`, `share_percentage` `"100.00"`, `commission_percentage` `"10.00"`, `commission_amount` `"20000.00"` |
| `items[service].mechanics[0].commission_amount` | `"12000.00"` |
| `commission_summary.total_commission` | `"32000.00"` |
| `commission_summary.is_final` | `false` |
| `items[labor].estimated_hours` / `items[service].estimated_hours` | `"3.00"` / `"1.50"` |
| `labor_summary` | `estimated_hours` `"4.50"`, `actual_hours` `null`, `labor_cost` `null`, `assignments_without_hours` `2`, `is_complete` `false` |

Cargar horas con la OT `pending`: `PATCH {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics/{{mec1_id}}/hours` con `{ "hours": 1 }` → `422 WORK_ORDER_ITEM_HOURS_NOT_EDITABLE`.

### 13.7 Cambio de responsable (OT `pending`)

`PATCH {{base_url}}/work-orders/{{work_order5_id}}`

```json
{ "mechanic_id": {{mec2_id}} }
```

Esperado: `200`; los dos trabajos pasan a `{{mec2_id}}` con `commission_percentage` `"8.50"`.

Volver al responsable original: `{ "mechanic_id": {{mec1_id}} }` → `200`, trabajos otra vez en `{{mec1_id}}` con `"10.00"`.

### 13.8 Asignar varios mecánicos a un trabajo

Ver la asignación actual: `GET {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics` → `200`, un mecánico.

**Partes iguales** (sin porcentajes):

`PUT {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics`

```json
{
  "mechanics": [
    { "mechanic_id": {{mec1_id}} },
    { "mechanic_id": {{mec2_id}} }
  ]
}
```

Esperado: `200`; `{{mec1_id}}` 50% → `commission_amount` `"10000.00"`; `{{mec2_id}}` 50% → `"8500.00"`.

**Reparto personalizado:**

```json
{
  "mechanics": [
    { "mechanic_id": {{mec1_id}}, "share_percentage": 70 },
    { "mechanic_id": {{mec2_id}}, "share_percentage": 30 }
  ]
}
```

Esperado: `200`; `{{mec1_id}}` → `"14000.00"` (200.000 × 70% × 10%); `{{mec2_id}}` → `"5100.00"` (200.000 × 30% × 8.5%).
En `GET {{base_url}}/work-orders/{{work_order5_id}}`: `commission_summary.total_commission` `"31100.00"`.

Casos de error (la asignación 70/30 no debe cambiar):

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| No suman 100 | `{ "mechanics": [ { "mechanic_id": {{mec1_id}}, "share_percentage": 60 }, { "mechanic_id": {{mec2_id}}, "share_percentage": 30 } ] }` | 422 | `WORK_ORDER_ITEM_SHARES_INVALID` |
| Porcentaje solo en algunos | `{ "mechanics": [ { "mechanic_id": {{mec1_id}}, "share_percentage": 60 }, { "mechanic_id": {{mec2_id}} } ] }` | 422 | `WORK_ORDER_ITEM_SHARES_INVALID` |
| Mecánico repetido | `{ "mechanics": [ { "mechanic_id": {{mec1_id}} }, { "mechanic_id": {{mec1_id}} } ] }` | 422 | `VALIDATION_ERROR` |
| Lista vacía | `{ "mechanics": [] }` | 422 | `VALIDATION_ERROR` |
| Mecánico inexistente | `{ "mechanics": [ { "mechanic_id": 999999 } ] }` | 422 | `VALIDATION_ERROR` |
| Mecánico no activo | `PATCH {{base_url}}/mechanics/{{mec2_id}}` con `{ "status": "on_leave" }`, luego asignarlo | 422 | `MECHANIC_NOT_ACTIVE` (después volver `{ "status": "active" }`) |
| Ítem de tipo repuesto | `PUT {{base_url}}/work-orders/{{work_order4_id}}/items/{id del ítem part}/mechanics` (OT de 12.15, en progreso) con `{ "mechanics": [ { "mechanic_id": {{mec1_id}} } ] }` | 422 | `WORK_ORDER_ITEM_NOT_COMMISSIONABLE` |
| Ítem de otra OT | `PUT {{base_url}}/work-orders/{{work_order5_id}}/items/999999/mechanics` | 404 | `RESOURCE_NOT_FOUND` |

**Comisión copiada al asignar:** `PATCH {{base_url}}/mechanics/{{mec1_id}}` con `{ "commission_percentage": 50 }` → la asignación existente sigue mostrando `"10.00"`. Volver a `{ "commission_percentage": 10 }`.

### 13.9 Permisos del mecánico sobre las asignaciones (token = `{{token_mec}}`)

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ver asignación | `GET {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics` | — | 200 | — |
| Cambiar asignación | `PUT .../items/{{labor_item_id}}/mechanics` | `{ "mechanics": [ { "mechanic_id": {{mec1_id}} } ] }` | 403 | `FORBIDDEN` |

### 13.10 Completar la OT y consultar comisiones

Token: administrador.

1. `POST {{base_url}}/work-orders/{{work_order5_id}}/start` → `200`.

**Horas reales (opcional)** — `PATCH {{base_url}}/work-orders/{{work_order5_id}}/items/{item}/mechanics/{mechanic}/hours`:

| Token | Ítem / mecánico | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| `{{token_mec}}` (MEC-001) | `{{labor_item_id}}` / `{{mec1_id}}` | `{ "hours": 2.5 }` | 200 | `hours` `"2.50"`, `hour_cost` `"45000.00"`, `labor_cost` `"112500.00"` |
| `{{token_mec}}` (MEC-001) | `{{labor_item_id}}` / `{{mec2_id}}` | `{ "hours": 1 }` | 403 | `FORBIDDEN` (solo carga las propias) |
| `{{token_admin}}` | `{{labor_item_id}}` / `{{mec2_id}}` | `{ "hours": 1 }` | 200 | `labor_cost` `"35000.00"` |
| `{{token_admin}}` | `{{service_item_id}}` / `{{mec1_id}}` | `{ "hours": 1 }` | 200 | `labor_cost` `"45000.00"` |
| `{{token_admin}}` | `{{service_item_id}}` / `{{mec2_id}}` (no asignado) | `{ "hours": 1 }` | 404 | `RESOURCE_NOT_FOUND` |
| `{{token_admin}}` | `{{labor_item_id}}` / `{{mec1_id}}` | `{ "hours": 0 }` | 422 | `VALIDATION_ERROR` |
| `{{token_admin}}` | `{{labor_item_id}}` / `{{mec1_id}}` | `{}` | 422 | `VALIDATION_ERROR` (`hours` es obligatorio; `null` lo borra) |

Verificar en `GET {{base_url}}/work-orders/{{work_order5_id}}`: `labor_summary` → `estimated_hours` `"4.50"`, `actual_hours` `"4.50"`, `labor_cost` `"192500.00"`, `assignments_without_hours` `0`, `is_complete` `true`.

> Borrar horas: el mismo `PATCH` con `{ "hours": null }` deja `hours` y `labor_cost` en `null`. Si se prueba, volver a cargarlas para mantener los valores esperados.

2. Iniciar y completar cada ítem (`.../items/{id}/start` y `.../items/{id}/complete`).
3. `POST {{base_url}}/work-orders/{{work_order5_id}}/complete` → `200`, `commission_summary.is_final` `true`, `total_commission` `"31100.00"`.

**Reporte de comisiones** (por defecto, el mes en curso):

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| MEC-001 | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions` | 200 | `total_commission` `"26000.00"` (14.000 mano de obra + 12.000 servicio), 2 líneas con `work_order_code`, `item_total`, `share_percentage`, `commission_amount` |
| MEC-002 | `GET {{base_url}}/mechanics/{{mec2_id}}/commissions` | 200 | `total_commission` `"5100.00"`, 1 línea |
| Período sin OT | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?date_from=2020-01-01&date_to=2020-01-31` | 200 | `lines` vacío, `total_commission` `"0.00"` |
| Fecha mal formada | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?date_from=ayer` | 422 | `VALIDATION_ERROR` |
| Rango invertido | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?date_from=2026-02-01&date_to=2026-01-01` | 422 | `VALIDATION_ERROR` |
| Mecánico inexistente | `GET {{base_url}}/mechanics/999999/commissions` | 404 | `RESOURCE_NOT_FOUND` |

> Los totales esperados asumen que no hay otras OT completadas en el mes con trabajos de MEC-001 o MEC-002. Las OT de las fases anteriores (por ejemplo `{{work_order_id}}` y `{{work_order2_id}}`) quedaron asignadas a su responsable por la migración: si alguna es de MEC-001 o MEC-002, sus líneas también aparecen y suman al total.

**Mecánico consultando** (token = `{{token_mec}}`, usuario `jperez` = MEC-001):

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Sus comisiones | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions` | 200 | — |
| Comisiones de otro | `GET {{base_url}}/mechanics/{{mec2_id}}/commissions` | 403 | `FORBIDDEN` |

### 13.11 OT completada: asignaciones y horas congeladas

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Cambiar asignación | `PUT {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics` | `{ "mechanics": [ { "mechanic_id": {{mec1_id}} } ] }` | 422 | `WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE` |
| Cargar horas | `PATCH {{base_url}}/work-orders/{{work_order5_id}}/items/{{labor_item_id}}/mechanics/{{mec1_id}}/hours` | `{ "hours": 5 }` | 422 | `WORK_ORDER_ITEM_HOURS_NOT_EDITABLE` |

### 13.12 Trabajo cancelado no genera comisión

1. Repetir 13.5 y 13.6 con un presupuesto nuevo → `work_order6_id`.
2. Iniciar la OT y cancelar el ítem `service`: `POST .../items/{id}/cancel` → `200`.
3. `PUT` de mecánicos sobre ese ítem → `422 WORK_ORDER_ITEM_MECHANICS_NOT_EDITABLE`.
4. Completar el ítem `labor` y la OT → `commission_summary.total_commission` `"20000.00"` (el servicio cancelado no suma).

---

## FASE 14 — Cierre económico de la OT

Token: administrador salvo indicación. Parte de las OT `{{work_order5_id}}` (13.10) y `{{work_order6_id}}` (13.12), ya completadas.

> Importes **sin IVA**. Al completar la OT se congelan venta, costos, margen y comisiones.
> **Venta** = mano de obra/servicios no cancelados (total presupuestado) + repuestos **usados** (cantidad neta × `unit_price`, incluidos los extras) + repuestos fuera de catálogo no cancelados.
> **Costo** = repuestos (neta × `unit_cost`) + mano de obra (horas × costo por hora) + comisiones.
> Si falta algún costo (horas sin cargar, repuesto fuera de catálogo), el margen se calcula con los costos conocidos y `is_cost_complete` es `false`.
> `economic_summary` solo aparece con el permiso `work-orders.view-economics` (el mecánico no lo ve).

### 14.1 Resumen provisional de una OT abierta

Repetir 13.5 y 13.6 con un presupuesto nuevo (servicio 120.000 + mano de obra 200.000, responsable `{{mec1_id}}`) → `work_order7_id`. No iniciarla.

`GET {{base_url}}/work-orders/{{work_order7_id}}`

| Campo de `data.economic_summary` | Valor esperado |
|---|---|
| `is_final` / `closed_at` | `false` / `null` |
| `budgeted` | `labor` `"320000.00"`, `parts` `"0.00"`, `total` `"320000.00"` |
| `sale.total` / `variance` | `"320000.00"` / `"0.00"` |
| `cost` | `parts` `"0.00"`, `labor` `null` (sin horas), `commissions` `"32000.00"`, `total` `"32000.00"` |
| `gross_margin` / `margin_percentage` | `"288000.00"` / `"90.00"` |
| `is_cost_complete` | `false` |

### 14.2 Cierre congelado de una OT completada

`GET {{base_url}}/work-orders/{{work_order5_id}}`

| Campo de `data.economic_summary` | Valor esperado |
|---|---|
| `is_final` / `closed_at` | `true` / fecha de cierre (= `completed_at`) |
| `budgeted.total` / `sale.total` / `variance` | `"320000.00"` / `"320000.00"` / `"0.00"` |
| `cost.labor` | `"192500.00"` (2,5 h × 45.000 + 1 h × 35.000 + 1 h × 45.000) |
| `cost.commissions` | `"31100.00"` (14.000 + 5.100 + 12.000) |
| `cost.total` | `"223600.00"` |
| `gross_margin` / `margin_percentage` | `"96400.00"` / `"30.13"` |
| `is_cost_complete` | `true` |

**Congelado:** `PATCH {{base_url}}/mechanics/{{mec1_id}}` con `{ "commission_percentage": 50, "hour_cost": 99999 }` → repetir el `GET`: el `economic_summary` no cambia. Volver a `{ "commission_percentage": 10, "hour_cost": 45000 }`.

### 14.3 Trabajo cancelado y sin horas

`GET {{base_url}}/work-orders/{{work_order6_id}}`

| Campo de `data.economic_summary` | Valor esperado |
|---|---|
| `budgeted.total` / `sale.total` | `"320000.00"` / `"200000.00"` (el servicio cancelado no se vende) |
| `variance` | `"-120000.00"` |
| `cost` | `labor` `null`, `commissions` `"20000.00"`, `total` `"20000.00"` |
| `gross_margin` / `margin_percentage` | `"180000.00"` / `"90.00"` |
| `is_cost_complete` | `false` (no se cargaron horas) |

### 14.4 Repuestos en el cierre (opcional)

Sobre `{{work_order2_id}}` (FASE 12, completada): `sale.parts` = suma de `parts[].total_price` (cantidad neta usada, incluido el extra) y `cost.parts` = suma de `parts[].total_cost`; ambos coinciden con `parts_summary.total_price` / `total_cost`. Un ítem `part` sin catálogo suma su total presupuestado a `sale.parts` y deja `is_cost_complete` en `false`.

### 14.5 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Ver la OT | `GET {{base_url}}/work-orders/{{work_order5_id}}` | 200 | Sin la clave `economic_summary` (sí `commission_summary` y `labor_summary`) |
| Liquidar comisiones | `POST {{base_url}}/mechanics/{{mec1_id}}/commissions/settle` (body de 14.7) | 403 | `FORBIDDEN` |

### 14.6 Reporte de comisiones con estado

El reporte lee las comisiones congeladas al completar cada OT. Nuevos campos: `total_pending`, `total_settled` y `lines[].settled_at`.

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| MEC-001 | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions` | 200 | `total_commission` `"46000.00"` (26.000 de `{{work_order5_id}}` + 20.000 de `{{work_order6_id}}`), `total_pending` `"46000.00"`, `total_settled` `"0.00"`, 3 líneas con `settled_at` `null` |
| MEC-002 | `GET {{base_url}}/mechanics/{{mec2_id}}/commissions` | 200 | `total_commission` `"5100.00"`, pendiente |
| Solo pendientes | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?status=pending` | 200 | 3 líneas |
| Estado inválido | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?status=pagado` | 422 | `VALIDATION_ERROR` (`errors.status`) |

> `{{work_order7_id}}` no suma: está abierta. Como en 13.10, si OT anteriores de MEC-001 se completaron en el mes, también aparecen.

### 14.7 Liquidar comisiones

`POST {{base_url}}/mechanics/{{mec1_id}}/commissions/settle`

```json
{
  "date_from": "2026-10-01",
  "date_to": "2026-10-31"
}
```

(Usar el primer y el último día del mes en curso.)

Esperado: `200`, `settled_count` `3`, `settled_total` `"46000.00"`, `settled_at` informado.

| Caso | Método / Endpoint | Body | HTTP | Resultado / error_code |
|---|---|---|---|---|
| Liquidar otra vez | mismo request | mismo body | 422 | `MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE` |
| Período sin comisiones | mismo endpoint | `{ "date_from": "2020-01-01", "date_to": "2020-01-31" }` | 422 | `MECHANIC_COMMISSIONS_NOTHING_TO_SETTLE` |
| Sin fechas | mismo endpoint | `{}` | 422 | `VALIDATION_ERROR` (`date_from`, `date_to`) |
| Rango invertido | mismo endpoint | `{ "date_from": "2026-10-31", "date_to": "2026-10-01" }` | 422 | `VALIDATION_ERROR` |
| Mecánico inexistente | `POST {{base_url}}/mechanics/999999/commissions/settle` | body válido | 404 | `RESOURCE_NOT_FOUND` |

Verificación posterior:

| Caso | Método / Endpoint | HTTP | Resultado |
|---|---|---|---|
| Liquidadas | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?status=settled` | 200 | 3 líneas con `settled_at`, `total_settled` `"46000.00"`, `total_pending` `"0.00"` |
| Pendientes | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions?status=pending` | 200 | `lines` vacío |
| Otro mecánico | `GET {{base_url}}/mechanics/{{mec2_id}}/commissions` | 200 | `total_pending` `"5100.00"` (no se liquidó) |
| Mecánico consultando las suyas | `GET {{base_url}}/mechanics/{{mec1_id}}/commissions` con `{{token_mec}}` | 200 | Ve `settled_at` de sus líneas |

---

## FASE 15 — Facturación

Token: administrador salvo indicación.

> Precios **con IVA incluido**, importes en **guaraníes enteros**. Los precios del sistema son sin IVA: la factura los convierte (precio sin IVA × 1,10 al 10%).
> Liquidación: **IVA 10% = total gravado 10% ÷ 11**, **IVA 5% = total gravado 5% ÷ 21**.
> Numeración `001-001-0000001` (establecimiento − punto de expedición − correlativo), asignada **al emitir**.

### 15.1 Timbrado

`POST {{base_url}}/invoice-stamps`

```json
{
  "stamp_number": "12345678",
  "establishment": "001",
  "expedition_point": "001",
  "valid_from": "2026-10-01",
  "valid_until": "2027-09-30"
}
```

Esperado: `201`, `next_number` `1`, `next_invoice_number` `"001-001-0000001"`, `is_active` `true`, `is_valid_today` `true` → `stamp_id`.

> Si la base se sembró con `InvoiceStampSeeder`, el timbrado ya existe: `GET {{base_url}}/invoice-stamps` y guardar su `id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Otro activo en el mismo establecimiento y punto | `{ "stamp_number": "87654321", "establishment": "001", "expedition_point": "001", "valid_from": "2026-10-01", "valid_until": "2027-09-30" }` | 409 | `INVOICE_STAMP_OVERLAP` |
| Número de timbrado inválido | `{ ..., "stamp_number": "1234" }` | 422 | `VALIDATION_ERROR` (`errors.stamp_number`) |
| Establecimiento inválido | `{ ..., "establishment": "1" }` | 422 | `VALIDATION_ERROR` (`errors.establishment`) |
| Vigencia invertida | `{ ..., "valid_from": "2027-01-01", "valid_until": "2026-01-01" }` | 422 | `VALIDATION_ERROR` (`errors.valid_until`) |
| Próximo número fuera de rango | `{ ..., "next_number": 10000000 }` | 422 | `VALIDATION_ERROR` (`errors.next_number`) |

Listar: `GET {{base_url}}/invoice-stamps` → `200`.

### 15.2 Generar la factura en borrador

`POST {{base_url}}/work-orders/{{work_order5_id}}/invoice` (sin body)

Esperado: `201` → `invoice_id`.

| Campo | Valor esperado |
|---|---|
| `status.value` / `number` | `draft` / `null` |
| `customer.name` / `customer.tax_id` | `"Lucia Benitez"` / `"QA-1001"` (copiados del cliente) |
| `condition.value` | `cash` |
| Línea del servicio | `base_unit_price` `"120000.00"`, `tax_rate.value` `10`, `unit_price` `"132000.00"`, `total` `"132000.00"` |
| Línea de mano de obra | `base_unit_price` `"200000.00"`, `unit_price` `"220000.00"`, `total` `"220000.00"` |
| `totals` | `taxed_10` `"352000.00"`, `iva_10` `"32000.00"`, `exempt` `"0.00"`, `total` `"352000.00"` |

Guardar los IDs de las líneas en `invoice_service_item_id` y `invoice_labor_item_id`.

`GET {{base_url}}/work-orders/{{work_order5_id}}` → `data.invoice` con `status` `draft` y `total` `"352000.00"`.

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Facturar de nuevo la misma OT | `POST {{base_url}}/work-orders/{{work_order5_id}}/invoice` | 409 | `WORK_ORDER_ALREADY_INVOICED` |
| OT no completada | `POST {{base_url}}/work-orders/{{work_order7_id}}/invoice` (14.1, abierta) | 422 | `WORK_ORDER_NOT_INVOICEABLE` |
| OT inexistente | `POST {{base_url}}/work-orders/999999/invoice` | 404 | `RESOURCE_NOT_FOUND` |

> En OT con repuestos de catálogo se factura la **cantidad neta usada** (por ejemplo 1,7 L), no la presupuestada; los repuestos fuera de catálogo se facturan por lo presupuestado.

### 15.3 Editar líneas (tasa y descuento)

`PATCH {{base_url}}/invoices/{{invoice_id}}/items/{{invoice_service_item_id}}`

```json
{ "tax_rate": 5 }
```

Esperado: `200`; servicio `unit_price` `"126000.00"`; `totals.taxed_5` `"126000.00"`, `iva_5` `"6000.00"`, `taxed_10` `"220000.00"`, `iva_10` `"20000.00"`, `total` `"346000.00"`.

`PATCH {{base_url}}/invoices/{{invoice_id}}/items/{{invoice_labor_item_id}}`

```json
{ "discount": 22000, "description": "Cambio de embrague completo" }
```

Esperado: `200`; mano de obra `total` `"198000.00"`; `totals.iva_10` `"18000.00"`, `discount` `"22000.00"`, `total` `"324000.00"`.

| Caso | Línea / Body | HTTP | error_code |
|---|---|---|---|
| Exenta | servicio: `{ "tax_rate": 0 }` | 200 | `unit_price` `"120000.00"` (vuelve al precio sin IVA), `totals.exempt` `"120000.00"` |
| Tasa inválida | `{ "tax_rate": 7 }` | 422 | `VALIDATION_ERROR` (`errors.tax_rate`) |
| Descuento mayor al importe | mano de obra: `{ "discount": 300000 }` | 422 | `INVOICE_DISCOUNT_EXCEEDS_AMOUNT` |
| Descuento negativo | `{ "discount": -1 }` | 422 | `VALIDATION_ERROR` |

**Volver a los valores originales** para los pasos siguientes: servicio `{ "tax_rate": 10 }` y mano de obra `{ "discount": 0 }` → `totals.total` `"352000.00"`.

### 15.4 Editar la cabecera

`PATCH {{base_url}}/invoices/{{invoice_id}}`

```json
{
  "customer_name": "Transportes del Sur S.A.",
  "customer_tax_id": "80012345-6",
  "customer_address": "Av. Espana 1234"
}
```

Esperado: `200`, `customer` actualizado.

| Caso | Body | HTTP | Resultado / error_code |
|---|---|---|---|
| Crédito con vencimiento | `{ "condition": "credit", "due_date": "<hoy + 30 días>" }` | 200 | `condition.value` `credit` |
| Volver a contado | `{ "condition": "cash" }` | 200 | `due_date` `null` (se descarta) |
| Condición inválida | `{ "condition": "otro" }` | 422 | `VALIDATION_ERROR` |
| RUC con caracteres inválidos | `{ "customer_tax_id": "RUC 800!" }` | 422 | `VALIDATION_ERROR` |

### 15.5 Emitir

`POST {{base_url}}/invoices/{{invoice_id}}/issue` (body opcional: `{ "invoice_stamp_id": {{stamp_id}} }`)

Esperado: `200`, `status.value` `issued`, `number` `"001-001-0000001"`, `stamp.stamp_number` `"12345678"`, `issue_date` = hoy, `issued_at` informado.
En `GET {{base_url}}/invoice-stamps`: `next_number` `2`.

Casos de error (usar `{{invoice2_id}}`, generado desde `{{work_order6_id}}`: `POST {{base_url}}/work-orders/{{work_order6_id}}/invoice`):

| Caso | Preparación | HTTP | error_code |
|---|---|---|---|
| Sin RUC/CI | `PATCH .../invoices/{{invoice2_id}}` con `{ "customer_tax_id": null }` | 422 | `INVOICE_CUSTOMER_TAX_ID_REQUIRED` |
| Crédito sin vencimiento | `{ "customer_tax_id": "QA-1001", "condition": "credit" }` | 422 | `INVOICE_DUE_DATE_REQUIRED` |
| Crédito vencido | `{ "due_date": "<ayer>" }` | 422 | `INVOICE_DUE_DATE_REQUIRED` |
| Timbrado desactivado | Primero `{ "due_date": "<hoy + 30 días>" }` (el vencimiento se valida antes que el timbrado); luego `PATCH {{base_url}}/invoice-stamps/{{stamp_id}}` con `{ "is_active": false }` | 422 | `INVOICE_STAMP_NOT_AVAILABLE` (después volver `{ "is_active": true }`) |
| Timbrado inexistente | body `{ "invoice_stamp_id": 999999 }` | 422 | `VALIDATION_ERROR` |
| Emitir una factura ya emitida | `POST .../invoices/{{invoice_id}}/issue` | 422 | `INVOICE_NOT_ISSUABLE` |

Emisión a crédito válida: con el timbrado activo y `due_date` futura, emitir `{{invoice2_id}}` → `200`, `number` `"001-001-0000002"`, `condition.value` `credit`.

### 15.6 Factura emitida: inmutable

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Editar cabecera | `PATCH {{base_url}}/invoices/{{invoice_id}}` | `{ "customer_name": "Otro" }` | 422 | `INVOICE_NOT_EDITABLE` |
| Editar línea | `PATCH {{base_url}}/invoices/{{invoice_id}}/items/{{invoice_labor_item_id}}` | `{ "discount": 1 }` | 422 | `INVOICE_NOT_EDITABLE` |
| Eliminar | `DELETE {{base_url}}/invoices/{{invoice_id}}` | — | 422 | `INVOICE_NOT_EDITABLE` |

### 15.7 Anular y refacturar

`POST {{base_url}}/invoices/{{invoice2_id}}/cancel`

```json
{ "reason": "RUC del cliente incorrecto" }
```

Esperado: `200`, `status.value` `cancelled`, `number` `"001-001-0000002"` (se conserva), `cancellation_reason` informado.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Sin motivo | `{}` | 422 | `VALIDATION_ERROR` (`errors.reason`) |
| Anular otra vez | `{ "reason": "x" }` | 422 | `INVOICE_NOT_CANCELLABLE` |
| Anular un borrador | sobre un borrador | 422 | `INVOICE_NOT_CANCELLABLE` |

Después de anular:
1. `GET {{base_url}}/work-orders/{{work_order6_id}}` → `data.invoice` `null`.
2. `POST {{base_url}}/work-orders/{{work_order6_id}}/invoice` → `201` (se puede refacturar) → `invoice3_id`.
3. Emitir `{{invoice3_id}}` → `number` `"001-001-0000003"` (el número anulado no se reutiliza).

### 15.8 Descartar un borrador

Con un borrador (por ejemplo, generar uno nuevo para otra OT completada): `DELETE {{base_url}}/invoices/{id}` → `200`. No consume número y la OT queda libre para facturar.

### 15.9 Listado

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Todas | `GET {{base_url}}/invoices` | 200 | Con `meta.pagination` |
| Emitidas | `GET {{base_url}}/invoices?status=issued` | 200 | Solo emitidas |
| Por número o cliente | `GET {{base_url}}/invoices?search=001-001-0000001` | 200 | Filtrado |
| Por fecha de emisión | `GET {{base_url}}/invoices?date_from=<hoy>&date_to=<hoy>` | 200 | Emitidas hoy |
| Estado inválido | `GET {{base_url}}/invoices?status=pagada` | 422 | `VALIDATION_ERROR` |
| Detalle | `GET {{base_url}}/invoices/{{invoice_id}}` | 200 | `items`, `totals`, `customer`, `stamp` |

### 15.10 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Listar facturas | `GET {{base_url}}/invoices` | 403 | `FORBIDDEN` |
| Generar | `POST {{base_url}}/work-orders/{{work_order5_id}}/invoice` | 403 | `FORBIDDEN` |
| Timbrados | `GET {{base_url}}/invoice-stamps` | 403 | `FORBIDDEN` |
| Ver la OT | `GET {{base_url}}/work-orders/{{work_order5_id}}` | 200 | Sin la clave `invoice` |

---

## FASE 16 — Pagos

Token: administrador salvo indicación.

> Se paga **solo una factura emitida**, en uno o varios pagos (contado o crédito), hasta cubrir su total. Montos en **guaraníes enteros**.
> Medios: `cash` (efectivo), `card` (tarjeta), `transfer` (transferencia), `check` (cheque). Tarjeta, transferencia y cheque exigen `reference`; el cheque también `bank`.
> Los pagos de facturas **a crédito** llevan recibo numerado (`REC-000001`…). Un pago no se edita: **se anula** (con motivo) y el saldo vuelve a estar disponible.
> La factura muestra `payment_summary`: `paid_total`, `balance` y `status` (`unpaid`, `partial`, `paid`).
> **Vuelto** (solo efectivo): enviar `amount_received` (lo entregado por el cliente); se guarda `change_amount`. Lo aplicado a la factura es siempre `amount`.

### 16.1 Pagos parciales al contado

Sobre `{{invoice_id}}` (15.5: contado, emitida, total `"352000.00"`).

Casos de error (antes de pagar):

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Body vacío | `{}` | 422 | `VALIDATION_ERROR` (`amount`, `method`) |
| Monto con decimales | `{ "amount": 1000.5, "method": "cash" }` | 422 | `VALIDATION_ERROR` (`errors.amount`) |
| Monto cero | `{ "amount": 0, "method": "cash" }` | 422 | `VALIDATION_ERROR` (`errors.amount`) |
| Medio inválido | `{ "amount": 1000, "method": "bitcoin" }` | 422 | `VALIDATION_ERROR` (`errors.method`) |
| Tarjeta sin referencia | `{ "amount": 1000, "method": "card" }` | 422 | `VALIDATION_ERROR` (`errors.reference`) |
| Cheque sin banco | `{ "amount": 1000, "method": "check", "reference": "0012345" }` | 422 | `VALIDATION_ERROR` (`errors.bank`) |
| Fecha futura | `{ "amount": 1000, "method": "cash", "payment_date": "<mañana>" }` | 422 | `VALIDATION_ERROR` (`errors.payment_date`) |
| Fecha anterior a la emisión | `{ "amount": 1000, "method": "cash", "payment_date": "<día anterior a issue_date>" }` | 422 | `PAYMENT_DATE_BEFORE_ISSUE` |
| Supera el saldo | `{ "amount": 352001, "method": "cash" }` | 422 | `PAYMENT_EXCEEDS_BALANCE` |
| Entregado menor al monto | `{ "amount": 1000, "method": "cash", "amount_received": 500 }` | 422 | `VALIDATION_ERROR` (`errors.amount_received`) |
| Entregado en otro medio | `{ "amount": 1000, "method": "card", "reference": "V-1", "amount_received": 2000 }` | 422 | `VALIDATION_ERROR` (`errors.amount_received`) |

**Primer pago (efectivo):**

`POST {{base_url}}/invoices/{{invoice_id}}/payments`

```json
{ "amount": 100000, "method": "cash" }
```

Esperado: `201` → `payment1_id`.

| Campo | Valor esperado |
|---|---|
| `status.value` / `method.value` | `registered` / `cash` |
| `payment_date` | hoy (si no se informa) |
| `receipt_number` | `null` (al contado la factura es el comprobante) |
| `amount_received` / `change_amount` | `null` / `null` (pago exacto, sin vuelto) |
| `invoice.paid_total` / `invoice.balance` | `"100000.00"` / `"252000.00"` |
| `invoice.payment_status` | `partial` |

**Segundo pago (tarjeta, el resto):**

```json
{ "amount": 252000, "method": "card", "reference": "VOUCHER-123" }
```

Esperado: `201`, `reference` `"VOUCHER-123"`, `invoice.balance` `"0.00"`, `invoice.payment_status` `paid` → `payment2_id`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Pagar una factura ya pagada | `{ "amount": 1, "method": "cash" }` | 422 | `INVOICE_ALREADY_PAID` |

Verificar `GET {{base_url}}/invoices/{{invoice_id}}`: `payment_summary` → `paid_total` `"352000.00"`, `balance` `"0.00"`, `status` `paid`; `payments` con 2 pagos.

> En efectivo la `reference` se descarta (queda `null`) aunque se envíe.

### 16.2 Facturas que no admiten pagos

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Factura anulada | `POST {{base_url}}/invoices/{{invoice2_id}}/payments` (15.7) | `{ "amount": 1000, "method": "cash" }` | 422 | `INVOICE_NOT_PAYABLE` |
| Factura en borrador | `POST {{base_url}}/invoices/{id de un borrador}/payments` | `{ "amount": 1000, "method": "cash" }` | 422 | `INVOICE_NOT_PAYABLE` |
| Factura inexistente | `POST {{base_url}}/invoices/999999/payments` | `{ "amount": 1000, "method": "cash" }` | 404 | `RESOURCE_NOT_FOUND` |

### 16.3 Factura a crédito con recibos

1. `POST {{base_url}}/work-orders/{{work_order_id}}/invoice` (OT de la fase base: mano de obra 500) → `201` → `invoice4_id`, `totals.total` `"550.00"`.
2. `PATCH {{base_url}}/invoices/{{invoice4_id}}` con `{ "condition": "credit", "due_date": "<hoy + 30 días>" }` → `200`.
3. `POST {{base_url}}/invoices/{{invoice4_id}}/issue` → `200`, `number` `"001-001-0000004"`, `payment_summary.status` `unpaid`.

`POST {{base_url}}/invoices/{{invoice4_id}}/payments`

```json
{ "amount": 200, "method": "cash", "amount_received": 500 }
```

Esperado: `201`, `receipt_number` `"REC-000001"`, `amount` `"200.00"`, `amount_received` `"500.00"`, `change_amount` `"300.00"` (vuelto), `invoice.balance` `"350.00"`, `invoice.payment_status` `partial` → `payment3_id`.

```json
{ "amount": 350, "method": "transfer", "reference": "OP-998", "bank": "Banco Continental" }
```

Esperado: `201`, `receipt_number` `"REC-000002"`, `bank` `"Banco Continental"`, `invoice.payment_status` `paid` → `payment4_id`.

### 16.4 Anular un pago

`POST {{base_url}}/payments/{{payment4_id}}/cancel`

```json
{ "reason": "Transferencia rechazada" }
```

Esperado: `200`, `status.value` `cancelled`, `cancellation_reason` informado, `receipt_number` `"REC-000002"` (se conserva), `invoice.balance` `"350.00"`, `invoice.payment_status` `partial`.

| Caso | Body | HTTP | error_code |
|---|---|---|---|
| Sin motivo | `{}` | 422 | `VALIDATION_ERROR` (`errors.reason`) |
| Anular otra vez | `{ "reason": "x" }` | 422 | `PAYMENT_NOT_CANCELLABLE` |
| Pago inexistente | `POST {{base_url}}/payments/999999/cancel` con `{ "reason": "x" }` | 404 | `RESOURCE_NOT_FOUND` |

Volver a pagar el saldo: `POST {{base_url}}/invoices/{{invoice4_id}}/payments` con `{ "amount": 350, "method": "cash" }` → `201`, `receipt_number` `"REC-000003"` (el recibo anulado no se reutiliza), `invoice.payment_status` `paid`.

### 16.5 Anular una factura con pagos

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Factura con pagos vigentes | `POST {{base_url}}/invoices/{{invoice_id}}/cancel` | `{ "reason": "Error" }` | 422 | `INVOICE_HAS_PAYMENTS` |

> Para anularla primero hay que anular sus pagos (16.4) y después la factura (15.7).

### 16.6 Listado y resumen de caja

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Todos | `GET {{base_url}}/payments` | 200 | 5 pagos con `meta.pagination` |
| Por factura | `GET {{base_url}}/payments?invoice_id={{invoice4_id}}` | 200 | 3 pagos |
| Por medio | `GET {{base_url}}/payments?method=card` | 200 | 1 pago |
| Anulados | `GET {{base_url}}/payments?status=cancelled` | 200 | 1 pago (`REC-000002`) |
| Por recibo o factura | `GET {{base_url}}/payments?search=REC-000001` | 200 | Filtrado |
| Detalle | `GET {{base_url}}/payments/{{payment4_id}}` | 200 | `bank`, `notes`, `invoice` (con `balance`), datos de anulación |
| Medio inválido | `GET {{base_url}}/payments?method=bitcoin` | 422 | `VALIDATION_ERROR` |
| Facturas con saldo | `GET {{base_url}}/invoices` | 200 | Cada factura con `balance` y `payment_status` |

**Resumen de caja** (por defecto, el día de hoy; acepta `date_from` y `date_to`):

`GET {{base_url}}/payments/summary`

| Campo | Valor esperado |
|---|---|
| `payments_count` / `total` | `4` / `"352550.00"` (sin el pago anulado) |
| `by_method` efectivo | `payments_count` `3`, `total` `"100550.00"` (100.000 + 200 + 350) |
| `by_method` tarjeta | `payments_count` `1`, `total` `"252000.00"` |
| `by_method` transferencia / cheque | `"0.00"` / `"0.00"` |
| `cash.received` / `cash.change` | `"100850.00"` / `"300.00"` (en 16.3 se entregaron 500 por un pago de 200) |
| `cancelled_count` | `1` |

| Caso | Método / Endpoint | HTTP | Resultado / error_code |
|---|---|---|---|
| Período sin pagos | `GET {{base_url}}/payments/summary?date_from=2020-01-01&date_to=2020-01-31` | 200 | `total` `"0.00"` |
| Rango invertido | `GET {{base_url}}/payments/summary?date_from=2026-02-01&date_to=2026-01-01` | 422 | `VALIDATION_ERROR` |

> Los totales asumen que no hubo otros pagos ese día.

### 16.7 Permisos del mecánico (token = `{{token_mec}}`)

| Caso | Método / Endpoint | HTTP | error_code |
|---|---|---|---|
| Registrar pago | `POST {{base_url}}/invoices/{{invoice4_id}}/payments` | 403 | `FORBIDDEN` |
| Listar pagos | `GET {{base_url}}/payments` | 403 | `FORBIDDEN` |
| Resumen de caja | `GET {{base_url}}/payments/summary` | 403 | `FORBIDDEN` |
| Anular pago | `POST {{base_url}}/payments/{{payment1_id}}/cancel` | 403 | `FORBIDDEN` |

---

## Errores HTTP genéricos

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ruta inexistente | `GET {{base_url}}/no-existe` | — | 404 | `RESOURCE_NOT_FOUND` |
| Recurso inexistente | `GET {{base_url}}/work-orders/999999` | — | 404 | `RESOURCE_NOT_FOUND` |
| Método no permitido | `DELETE {{base_url}}/inventory/stocks` | — | 405 | `METHOD_NOT_ALLOWED` |
| Body vacío | `POST {{base_url}}/parts` | `{}` | 422 | `VALIDATION_ERROR` |
| Sin permiso (mecánico) | `POST {{base_url}}/parts` | `{}` | 403 | `FORBIDDEN` (el permiso se evalúa antes que la validación) |

Ningún error de negocio de esta guía debe devolver `500`.
