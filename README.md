# Presupuestador — Sistema de gestión para taller mecánico

API REST para gestionar el flujo completo de atención de un vehículo en un taller mecánico: desde la recepción
hasta el pago de la factura.

```text
Cliente → Vehículo → Recepción → Diagnóstico → Presupuesto → Aprobación → Orden de trabajo
       → Ejecución (servicios, mano de obra, repuestos) → Cierre económico → Factura → Pago → Entrega
```

> El documento de referencia del proyecto (arquitectura, reglas de negocio y decisiones por fase) es
> [`CLAUDE.md`](CLAUDE.md). La guía de pruebas manuales está en
> [`docs/testing/guia-postman.md`](docs/testing/guia-postman.md).

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Autenticación | Laravel Sanctum (tokens Bearer) |
| Roles y permisos | spatie/laravel-permission (guard `api`) |
| Base de datos | MySQL (desarrollo y producción), SQLite en memoria (tests) |
| Frontend | React 19 (repositorio aparte), consume esta API |
| Entorno local | Laragon (Windows) |

---

## Módulos

| Módulo | Qué cubre |
|---|---|
| Autenticación | Login / logout con token Sanctum |
| Usuarios, roles y permisos | Roles `administrador`, `mechanic`, `recepcionista`, `jefe-taller`, `cajero` y `deposito`; permisos por operación |
| Clientes y vehículos | Clientes, marcas, modelos, vehículos (también anidados por cliente) |
| Recepción | Ingreso del vehículo, categorías de servicio, checklist y fotos |
| Diagnóstico | Diagnóstico técnico con ítems y fotos |
| Presupuestos | Ítems de mano de obra, servicio y repuesto; estados `draft → sent → approved` |
| Órdenes de trabajo | Nace de un presupuesto aprobado; estados, ítems, mecánico responsable |
| Repuestos e inventario | Catálogo, proveedores, stock con movimientos inmutables, reservas y consumo en la OT |
| Servicios y mano de obra | Catálogo de servicios, reparto entre mecánicos, horas reales, comisiones |
| Cierre económico | Al completar la OT se congelan venta, costos, margen y comisiones; liquidación de comisiones |
| Facturación | Timbrado, numeración `001-001-0000001`, IVA 10% / 5% / exento, contado o crédito |
| Pagos | Pagos parciales, medios de pago, vuelto en efectivo, recibos para crédito, saldo y resumen de caja |
| Entrega del vehículo | Comprobante `ENT-000001`, kilometraje final, quién retira, conformidad y firma; exige factura emitida y pagada si es al contado |

Próximas fases: historial del vehículo, mantenimiento preventivo, compras, reportes.

---

## Requisitos

- PHP 8.2 o superior con las extensiones `pdo_mysql`, `bcmath` y `mbstring` (y `pdo_sqlite` para los tests)
- Composer
- MySQL 8
- Node.js y npm (solo para los assets de Vite)

---

## Instalación local

```bash
git clone https://github.com/rubenminarro/presupuestador.git
cd presupuestador

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configurar en `.env` la base de datos y la contraseña de los usuarios de prueba:

```env
APP_NAME=Presupuestador
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presupuestador
DB_USERNAME=root
DB_PASSWORD=

# Contraseña que el seeder asigna a los usuarios de prueba
ADMIN_PASSWORD=
```

Crear la base `presupuestador` en MySQL y luego:

```bash
php artisan migrate --seed
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000/api`.

### Usuarios del seeder

| Email | Rol | Mecánico |
|---|---|---|
| `admin@mail.com.py` | administrador | — |
| `jperez@mail.com.py` | mechanic | MEC-001 (comisión 10%, costo/hora 45.000) |
| `cgomez@mail.com.py` | mechanic | MEC-002 (comisión 8,5%, costo/hora 35.000) |

Todos usan la contraseña definida en `ADMIN_PASSWORD`.

El seeder también crea los roles `recepcionista`, `jefe-taller`, `cajero` y `deposito` (sin usuarios asignados). Qué
puede hacer cada uno está en la sección 2.3 de [`CLAUDE.md`](CLAUDE.md).

> Después de agregar permisos nuevos (por ejemplo al pasar a una fase nueva) hay que volver a ejecutar
> `php artisan db:seed --class=RolesAndPermissionsSeeder`.

---

## Uso de la API

Todas las rutas (salvo `POST /api/login`) requieren el header `Authorization: Bearer {token}` y
`Accept: application/json`.

```http
POST /api/login
Content-Type: application/json

{ "email": "admin@mail.com.py", "password": "..." }
```

### Formato de respuesta

Éxito:

```json
{ "success": true, "status": 200, "message": "...", "data": {}, "errors": null, "meta": {}, "timestamp": "..." }
```

Error:

```json
{ "success": false, "status": 422, "message": "...", "data": null, "errors": null, "error_code": "WORK_ORDER_NOT_CANCELLABLE" }
```

- `message` está en español y es para el usuario.
- `error_code` es estable y es el que debe usar el frontend para decidir qué hacer.
- Los errores de negocio responden `422` (o `409` en conflictos), nunca `500`.

Códigos HTTP estándar: `401 UNAUTHENTICATED`, `403 FORBIDDEN`, `404 RESOURCE_NOT_FOUND`,
`405 METHOD_NOT_ALLOWED`, `422 VALIDATION_ERROR`.

### Importes

- Los precios internos (presupuesto, OT, cierre) son **sin IVA**; la factura los expresa **con IVA incluido**.
- La facturación y los pagos usan **guaraníes enteros**.

---

## Tests

Los tests usan SQLite en memoria (no tocan la base de desarrollo):

```bash
php artisan test
# o
vendor/bin/phpunit
```

Si `pdo_sqlite` no está habilitado en el `php.ini` de Laragon:

```bash
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

Para pruebas manuales con Postman, seguir [`docs/testing/guia-postman.md`](docs/testing/guia-postman.md): está
organizada por fase y cada sección reutiliza los datos de la anterior.

---

## Arquitectura

```text
Controller (HasMiddleware: permiso por operación)
    ↓
Form Request (validación de entrada)
    ↓
Service (reglas de negocio, transacciones, cambios de estado)
    ↓
Model / Eloquent
    ↓
API Resource (Resource para listados, ShowResource para detalle)
```

| Carpeta | Contenido |
|---|---|
| `app/Http/Controllers/Api` | Endpoints |
| `app/Http/Requests` | Validación |
| `app/Services` | Lógica de negocio |
| `app/Models` | Modelos Eloquent |
| `app/Http/Resources` | Contrato JSON público |
| `app/Enums` | Estados y tipos |
| `app/Exceptions` | Excepciones de dominio (`AppException` y derivadas, con `error_code`) |
| `app/Traits/ApiResponse.php` | Respuestas estándar |
| `routes/api.php` | Rutas de la API |
| `tests/Feature` | Tests por módulo |

Convenciones principales:

- La lógica de negocio va en Services, no en Controllers.
- Los estados se modelan con Enums; no se permiten transiciones arbitrarias.
- Las operaciones sobre varios registros usan `DB::transaction` y `lockForUpdate` cuando hay concurrencia.
- Cálculos de dinero y cantidades con BCMath, nunca con `float`.
- Rutas anidadas con `scopeBindings()`; actualizaciones parciales con `PATCH`.
- Las migraciones no llaman a código de la aplicación.

---

## Flujo de trabajo

- No se trabaja directamente sobre `main`: cada fase o cambio va en su propia rama (`feature/...`, `fix/...`,
  `docs/...`) y se integra a `main` con merge manual.
- Cada fase actualiza `CLAUDE.md` y la guía de Postman.
