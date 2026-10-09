# Project Handoff Document

## Presupuestador — Sistema de Gestión para Taller Mecánico

**Versión del handoff:** 1.0
**Fecha:** 2026-10-07
**Estado:** Backend/API funcional — Fases iniciales y auditoría de excepciones completadas
**Stack principal:** Laravel 12 + React 19 + MySQL
**Entorno local:** Laragon / Windows
**Base de datos:** `presupuestador`

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

* React 19
* Consumo de API REST
* Autenticación mediante Sanctum

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

Actualmente existe una jerarquía orientada a errores de dominio:

```text
AppException
├── BudgetException
└── WorkOrderException
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
│   └── WorkOrderException.php
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
* `reserve()`/`release()` existen sin endpoint; los usará la FASE 12.
* Cantidades de inventario en `decimal(12,3)`; `budget_items`/`work_order_items` siguen en `(10,2)`.

Endpoints: `GET inventory/stocks`, `GET inventory/movements[/{id}]`, `GET parts/{part}/movements`,
`POST inventory/entries|exits|adjustments`. Permisos: `inventory.index|show|entry|exit|adjust`
(mecánico: solo `index` y `show`).

Tests: los tests usan SQLite en memoria; en Laragon `pdo_sqlite` está deshabilitado en php.ini, ejecutar con:

```bash
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

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
mecanico
```

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

rejected → draft
```

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
    "meta": {},
    "timestamp": "..."
}
```

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

Jerarquía:

```text
AppException
├── BudgetException
└── WorkOrderException
```

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

---

# 5. Próximos módulos / Roadmap

## Prioridad inmediata: módulo de Repuestos

Este es el próximo gran módulo recomendado.

Sin embargo, no debe implementarse simplemente como un CRUD de `repuestos`.

El sistema debe evolucionar hacia un pequeño módulo de inventario integrado con la WorkOrder.

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
FASE 11 — INVENTARIO — CERRADA
```

El siguiente gran paso recomendado es:

```text
FASE 12 — REPUESTOS EN WORKORDER (WorkOrderPart + reserve/consume/release vía InventoryService)
```

El orden de trabajo de abajo se usó para las FASES 10 y 11 y sigue siendo la referencia.

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
```

## Próximo

```text
[ ] Repuestos utilizados en WorkOrder
[ ] Compras
[ ] Servicios
[ ] Mano de obra avanzada
[ ] Cierre económico
[ ] Facturación
[ ] Pagos
[ ] Entrega
[ ] Historial del vehículo
[ ] Mantenimiento preventivo
[ ] Notificaciones
[ ] Reportes
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
