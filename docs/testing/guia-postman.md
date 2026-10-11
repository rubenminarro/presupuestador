# Guía de pruebas con Postman — Presupuestador

**Alcance:** API al cierre de la FASE 12, más los ajustes posteriores (rutas de vehículos por cliente, permisos de ítems de presupuesto y `error_code` del login).
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

Variables de IDs que se irán completando con las respuestas: `client_id`, `client2_id`, `brand_id`, `model_id`, `vehicle_id`, `vehicle2_id`, `reception_id`, `budget_id`, `budget_item_id`, `mechanic_id`, `work_order_id`, `work_order_item_id`, `category_id`, `supplier_id`, `part_id`, `part_unit_id`, `part_status_id`, `movement_id`, `budget2_id`, `work_order2_id`, `work_order3_id`, `work_order4_id`, `line_id`, `extra_line_id`, `pending_line_id`.

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

Caso adicional (presupuesto nuevo y vacío): crear con P.1, enviar, aprobar → `422 BUDGET_WITHOUT_ITEMS`.

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
| Nombre con números | `{ "name": "Proveedor 123" }` | 422 | `VALIDATION_ERROR` |

### 10.3 Crear repuesto (litros)

> El nombre del repuesto solo admite letras y espacios.

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
| Nombre con números | `{ ..., "sku": "QA-X-2", "name": "Aceite 5W30" }` | 422 | `VALIDATION_ERROR` (`errors.name`) |
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

## Errores HTTP genéricos

| Caso | Método / Endpoint | Body | HTTP | error_code |
|---|---|---|---|---|
| Ruta inexistente | `GET {{base_url}}/no-existe` | — | 404 | `RESOURCE_NOT_FOUND` |
| Recurso inexistente | `GET {{base_url}}/work-orders/999999` | — | 404 | `RESOURCE_NOT_FOUND` |
| Método no permitido | `DELETE {{base_url}}/inventory/stocks` | — | 405 | `METHOD_NOT_ALLOWED` |
| Body vacío | `POST {{base_url}}/parts` | `{}` | 422 | `VALIDATION_ERROR` |
| Sin permiso (mecánico) | `POST {{base_url}}/parts` | `{}` | 403 | `FORBIDDEN` (el permiso se evalúa antes que la validación) |

Ningún error de negocio de esta guía debe devolver `500`.
