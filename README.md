# Conn2db

## Descripción
Conexión a base de datos en PHP 8.3 para las bases de datos PostgreSQL, MariaDB y MySQL.
Conexión ligera y sin abstracciones para asegurar un mejor rendimiento en cada consulta; las consultas se realizan de manera explícita con soporte completo de prepared statements, transacciones y type hints.

## Requisitos
- PHP >= 8.1
- Extensión PDO habilitada
- Driver PDO correspondiente (`pdo_mysql`, `pdo_pgsql`, etc.)

## Configuración
Crear el archivo `.env` en la raíz del proyecto con el siguiente contenido:

```
DB_DRIVER="mysql"
DB_HOST="localhost"
DB_NAME="dbname"
DB_USER="root"
DB_PWD="password"
DB_PORT="3306"
DB_CODIFICATION="utf8mb4"
DB_LOCALE="es_MX"
```

> **Nota:** `DB_LOCALE` solo aplica para MySQL y MariaDB (configura `LC_TIME_NAMES`).  
> Para PostgreSQL, esta variable se ignora de forma segura.

Para acceder a las funciones de conexión y cargar las variables del archivo `.env`, agrega el siguiente código en tu archivo principal:

```php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Nimter\Helper\Conn2db\Conn2db;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
```

## Ejemplos de consultas básicas

### SELECT con parámetros
```php
$conn = new Conn2db();

$stmt = "SELECT username, avatar FROM users WHERE id = :id";
$result = $conn->query($stmt, ['id' => 1]);

foreach ($result as $row) {
    echo $row['username'];
}
```

### INSERT
```php
$conn = new Conn2db();

$stmt = "INSERT INTO users (username, email) VALUES (:username, :email)";
$affected = $conn->query($stmt, ['username' => 'john', 'email' => 'john@example.com']);
$newId = $conn->lastId();
```

### Parámetros manuales con `binder()`
```php
$conn = new Conn2db();

$conn->binder('id', 42);
$result = $conn->query("SELECT * FROM users WHERE id = :id");
```

### Transacciones
```php
$conn = new Conn2db();

try {
    $conn->beginTransaction();

    $conn->query("UPDATE accounts SET balance = balance - :amount WHERE id = :id", ['amount' => 100, 'id' => 1]);
    $conn->query("UPDATE accounts SET balance = balance + :amount WHERE id = :id", ['amount' => 100, 'id' => 2]);

    $conn->commit();
} catch (\PDOException $e) {
    $conn->rollback();
    throw $e;
}
```

## Manejo de errores
Desde la versión 2.0.0, la clase lanza excepciones en lugar de imprimir mensajes de error directamente.
Esto evita la exposición de información interna al cliente y permite un control centralizado de errores:

```php
try {
    $conn = new Conn2db();
    $result = $conn->query("SELECT * FROM users");
} catch (\RuntimeException $e) {
    // Variable de entorno faltante
    error_log($e->getMessage());
} catch (\PDOException $e) {
    // Error de conexión o consulta
    error_log($e->getMessage());
}
```

## Extras
Requiere la librería `symfony/dotenv`.
