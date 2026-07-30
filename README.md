# Instalación del Proyecto

## Instalar las dependencias de Composer

```bash
composer install
```

## Crear el archivo de configuración

```bash
cp .env.example .env
```

## Configurar el archivo `.env`

Completa los datos necesarios, como:

- Nombre de la aplicación
- Conexión a la base de datos
- Usuario y contraseña de la base de datos
- Otras variables de entorno requeridas

## Generar la clave de la aplicación

```bash
php artisan key:generate
```

## Ejecutar las migraciones

```bash
php artisan migrate
```

# Prueba Técnica – API REST de Ventas en Laravel

## Objetivo

Desarrollar una API REST en Laravel para gestionar un flujo básico de ventas.

La prueba evaluará:

- Uso de relaciones entre modelos.
- Validación de datos.
- Manejo de transacciones.
- Control de stock.
- Lógica de negocio.
- Respuestas JSON y códigos HTTP.
- Pruebas mediante Postman u otro cliente HTTP.

---

## Estado inicial del proyecto

El proyecto ya incluye:

- Laravel configurado como API.
- Migraciones listas.
- Seeders con productos cargados.
- Modelos base: `Product`, `Sale` y `SaleDetail`.

---

## Fuera de alcance

No se debe implementar mantenimiento de productos.

Por lo tanto, no corresponde:

- Crear productos.
- Modificar productos.
- Eliminar productos.

Solo se deben utilizar los productos existentes.

---

## Requerimientos

### 1. Listar productos

**Endpoint**

```http
GET /api/products
```

Debe retornar:

- `id`
- `name`
- `price`
- `stock`

**Ejemplo**

```json
[
  {
    "id": 1,
    "name": "Laptop Lenovo",
    "price": 7500,
    "stock": 10
  }
]
```

---

### 2. Crear una venta

Usar el usuario estático:

```text
user_id = 1
```

**Endpoint**

```http
POST /api/sales
```

**Request**

```json
{
  "user_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ]
}
```

La operación debe:

1. Validar que exista al menos un producto.
2. Validar que cada producto exista.
3. Validar que la cantidad sea mayor que cero.
4. Verificar que exista stock suficiente.
5. Crear la venta.
6. Crear los detalles de la venta.
7. Calcular el subtotal de cada producto.
8. Calcular el total de la venta.
9. Descontar el stock vendido.

Los precios deben obtenerse desde la base de datos y no desde el request.

La creación de la venta, sus detalles y el descuento de stock deben ejecutarse dentro de una transacción.

**Cálculos**

```text
subtotal = price × quantity
total = suma de subtotales
```

**Ejemplo de respuesta**

```json
{
  "message": "Venta creada correctamente",
  "sale_id": 1,
  "total": 16500
}
```

Si no existe stock suficiente, la venta no debe registrarse parcialmente.

---

### 3. Listar ventas

**Endpoint**

```http
GET /api/sales
```

Debe retornar:

- `id`
- `total`
- `created_at`
- Detalle de productos vendidos

**Ejemplo**

```json
[
  {
    "id": 1,
    "total": 16500,
    "created_at": "2026-01-21",
    "details": [
      {
        "product": "Laptop Lenovo",
        "quantity": 2,
        "subtotal": 15000
      }
    ]
  }
]
```

---

### 4. Eliminar una venta

**Endpoint**

```http
DELETE /api/sales/{id}
```

Debe eliminar la venta seleccionada.

**Ejemplo de respuesta**

```json
{
  "message": "Venta eliminada correctamente"
}
```

Si la venta no existe, debe devolverse una respuesta de error adecuada.

---

## Reglas de negocio

- Una venta debe contener al menos un producto.
- No se permiten cantidades iguales o menores que cero.
- No se puede vender más stock del disponible.
- El precio debe obtenerse desde el producto registrado.
- El subtotal se calcula por producto.
- El total corresponde a la suma de los subtotales.
- La creación debe ejecutarse dentro de una transacción.
- Ante cualquier error, no deben quedar datos parciales.
- Las respuestas deben estar en formato JSON.

---

## Resultado esperado

Al finalizar la prueba, la API debe permitir:

- Consultar productos existentes.
- Registrar una venta con sus detalles.
- Calcular subtotales y total.
- Descontar stock.
- Consultar ventas.
- Eliminar ventas.

# PRUEBA TÉCNICA PARA EL PUESTO DE DESARROLLADOR FULL STACK

## API REST de Ventas

Desarrollo e implementación de una API REST para administrar un flujo básico de ventas. El sistema permite consultar productos, registrar ventas, calcular subtotales y totales, controlar existencias, consultar el historial de ventas y realizar su eliminación lógica de manera controlada.

La solución fue desarrollada siguiendo los requerimientos de la prueba técnica, aplicando validaciones, relaciones mediante Eloquent ORM, transacciones de base de datos y pruebas automatizadas.

---

## Funcionalidades implementadas

* Consulta de productos existentes.
* Registro de ventas con uno o varios productos.
* Uso del usuario estático con `user_id = 1`.
* Validación de usuario, productos y cantidades.
* Verificación de que la venta contenga al menos un producto.
* Rechazo de cantidades iguales o menores que cero.
* Validación de productos inexistentes.
* Rechazo de productos duplicados en una misma venta.
* Obtención de precios directamente desde la base de datos.
* Cálculo automático del subtotal de cada producto.
* Cálculo automático del total de la venta.
* Verificación de stock disponible.
* Descuento automático del stock vendido.
* Uso de transacciones para evitar registros parciales.
* Reversión completa de la operación ante cualquier error.
* Bloqueo de productos mediante `lockForUpdate()` durante la venta.
* Consulta de ventas con sus respectivos detalles.
* Eliminación lógica de ventas mediante `SoftDeletes`.
* Restauración del stock cuando una venta es eliminada.
* Manejo de ventas inexistentes.
* Respuestas en formato JSON.
* Uso de códigos HTTP apropiados.
* Pruebas manuales mediante Postman.
* Pruebas automatizadas Feature con PHPUnit.

---

## Tecnologías utilizadas

* PHP 8.3.
* Laravel 11.
* MySQL.
* Composer.
* Eloquent ORM.
* PHPUnit 11.
* Laravel Pint.
* Postman.
* Git.
* GitHub.

> Durante el desarrollo se utilizó PHP 8.3.

---

## Arquitectura general

La API está organizada utilizando la estructura estándar de Laravel:

```text
app/
├── Http/
│   ├── Controllers/Api/
│   │   ├── ProductController.php
│   │   └── SaleController.php
│   └── Requests/
│       └── StoreSaleRequest.php
├── Models/
│   ├── Product.php
│   ├── Sale.php
│   ├── SaleDetail.php
│   └── User.php
database/
├── migrations/
├── factories/
└── seeders/
routes/
└── api.php
tests/
└── Feature/
    └── SaleApiTest.php
```

---

## Relaciones del sistema

Las siguientes relaciones fueron implementadas mediante Eloquent ORM:

* Un usuario puede tener muchas ventas.
* Una venta pertenece a un usuario.
* Una venta puede tener muchos detalles.
* Un detalle pertenece a una venta.
* Un detalle pertenece a un producto.
* Un producto puede estar asociado con muchos detalles de venta.

---

## Requisitos para ejecutar el proyecto

Antes de instalar el proyecto se debe contar con:

* PHP 8.2 o superior.
* Composer.
* MySQL.
* Git.

### Verificación del entorno

```bash
php --version
composer --version
mysql --version
git --version
```

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
```

Ingresar al proyecto:

```bash
cd api-sales
```

### 2. Instalar las dependencias

```bash
composer install
```

### 3. Crear el archivo de entorno

En Git Bash:

```bash
cp .env.example .env
```

En PowerShell:

```powershell
Copy-Item .env.example .env
```

### 4. Crear la base de datos

Ingresar a MySQL:

```bash
mysql -u root -p
```

Crear la base de datos:

```sql
CREATE DATABASE IF NOT EXISTS mercatech_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Salir de MySQL:

```sql
exit;
```

### 5. Configurar `.env`

Configurar las credenciales locales de la aplicación:

```env
APP_NAME="API Sales"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mercatech_db
DB_USERNAME=root
DB_PASSWORD=
```

> Cada desarrollador debe utilizar sus propias credenciales locales. El archivo `.env` contiene información sensible y no debe subirse al repositorio.

### 6. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 7. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Este comando crea las tablas y carga:

* Usuarios de prueba.
* Productos disponibles para las ventas.

El usuario estático requerido para la prueba técnica es:

```text
user_id = 1
```

### 8. Levantar el servidor

```bash
php artisan serve
```

La API estará disponible en:

```text
http://127.0.0.1:8000
```

---

## Verificación inicial

### Estado de Laravel

Abrir en el navegador o Postman:

```text
http://127.0.0.1:8000/up
```

Una respuesta correcta indica que la aplicación Laravel se encuentra funcionando.

### Estado general de la API

```text
http://127.0.0.1:8000/api/test
```

Ejemplo de respuesta:

```json
{
  "app": "Laravel",
  "env": "local",
  "version": "11.48.0",
  "status": "ok"
}
```

### Consultar productos

```text
http://127.0.0.1:8000/api/products
```

### Consultar ventas

```text
http://127.0.0.1:8000/api/sales
```

---

## Endpoints de la API

| Método   | Endpoint          | Descripción                             |
| -------- | ----------------- | --------------------------------------- |
| `GET`    | `/api/test`       | Comprueba el funcionamiento de la API   |
| `GET`    | `/api/products`   | Lista los productos existentes          |
| `POST`   | `/api/sales`      | Registra una nueva venta                |
| `GET`    | `/api/sales`      | Lista las ventas activas y sus detalles |
| `DELETE` | `/api/sales/{id}` | Elimina lógicamente una venta           |

---

## Ejemplo para registrar una venta

### Solicitud

```http
POST /api/sales
Content-Type: application/json
Accept: application/json
```

```json
{
  "user_id": 1,
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ]
}
```

### Respuesta satisfactoria

Código HTTP:

```http
201 Created
```

```json
{
  "message": "Venta creada correctamente",
  "sale_id": 1,
  "total": 15450
}
```

El total depende de los precios registrados en la base de datos.

---

## Reglas de negocio implementadas

* Una venta debe contener al menos un producto.
* El usuario debe existir y tener el ID `1`.
* Cada producto debe existir en la base de datos.
* No se permite repetir un producto dentro de la misma venta.
* Las cantidades deben ser números enteros mayores que cero.
* No se puede vender una cantidad superior al stock disponible.
* El precio se obtiene exclusivamente desde la base de datos.
* El subtotal se calcula multiplicando el precio por la cantidad.
* El total corresponde a la suma de todos los subtotales.
* Los cálculos monetarios se realizan en centavos para reducir errores de precisión.
* La venta, los detalles y el descuento de stock se procesan dentro de una transacción.
* Ante cualquier error, la transacción completa es revertida.
* Los productos son bloqueados durante la operación para controlar ventas concurrentes.
* Al eliminar una venta, el stock vendido es restaurado.
* Una venta eliminada no puede eliminarse nuevamente.

---

## Pruebas automatizadas

Las pruebas Feature pueden ejecutarse con:

```bash
php artisan test --env=testing --filter=SaleApiTest
```

Resultado obtenido:

```text
PASS  Tests\Feature\SaleApiTest

Tests:    13 passed (122 assertions)
Duration: 17.86s
```

### Escenarios verificados

* Listado de productos.
* Creación correcta de una venta.
* Venta con al menos un producto.
* Cantidad mayor que cero.
* Validación de productos existentes.
* Restricción al usuario con ID `1`.
* Rechazo de productos duplicados.
* Rechazo por stock insuficiente.
* Reversión completa de operaciones incompletas.
* Listado de ventas con detalles.
* Eliminación lógica de ventas.
* Restauración del stock.
* Respuesta `404` para ventas inexistentes.
* Rechazo de una segunda eliminación.

---

## Verificación de estilo

Para comprobar el formato del código sin modificar archivos usamos:

```bash
./vendor/bin/pint --test
```

Para aplicar automáticamente el formato de Laravel Pint usamos:

```bash
./vendor/bin/pint
```
---

## Consideraciones de seguridad

Los siguientes archivos no se suben al repositorio por seguridad:

```text
.env
.env.testing
vendor/
node_modules/
storage/logs/
.phpunit.result.cache
```

Antes de realizar un commit se recomienda verificar:

```bash
git status
git check-ignore -v .env
git check-ignore -v .env.testing
```

El repositorio solo incluye`.env.example`, sin contraseñas por seguridad.

Solo Para producción se debe configurar:

```env
APP_ENV=production
APP_DEBUG=false
```

El endpoint `/api/test` solo se utiliza para verificaciones durante el desarrollo local.
Nota: Antes de publicar la API en producción debe eliminarse o protegerse  `/api/test`.

---

## Estado del proyecto

La API cumple los requerimientos funcionales definidos en la prueba técnica:

* Consulta productos existentes.
* Registra ventas con uno o varios detalles.
* Valida usuario, productos y cantidades.
* Calcula subtotales y total.
* Obtiene los precios desde la base de datos.
* Verifica y descuenta el stock.
* Evita ventas sin existencias suficientes.
* Utiliza transacciones de base de datos.
* Evita registros parciales ante errores.
* Consulta ventas con sus detalles.
* Elimina ventas lógicamente.
* Restaura el stock al eliminar una venta.
* Responde mediante JSON y códigos HTTP apropiados.
* Verifica el comportamiento mediante pruebas automatizadas.

### Resultado de la verificación

```text
Requerimientos funcionales: CUMPLIDOS
Pruebas automatizadas: 13 APROBADAS
Aserciones verificadas: 122
Estado general: APROBADO
```

---

## Autor

**Jhonny Tito Castro**
