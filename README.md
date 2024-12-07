# Conn2db

## Descripción
Conexión a base de datos en PHP 8 para las bases de datos postgresql, mariadb y mysql.
Conexión ligera y sin abstracciones para asegurar un mejor rendiento en cada consulta, las consultas se realizan de manera explicita.

## Configuración
Crear el archivo ``.env`` en la raiz de su proyecto.

Agregar el siguiente contenido a su archivo ``.env``:

```
DB_DRIVER="mysql"
DB_HOST="localhost"
DB_NAME="dbname"
DB_USER="root"
DB_PWD="password"
DB_PORT="3306"
DB_CODIFICATION="utf8"
DB_LOCALE="es_MX"
```

Para acceder a las funciones de la conexión de base de datos y acceder a las varaibles del archivo ``.env``, debe de agregar el siguiente codigo a su archivo principal:

```php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Nimter\Helper\Conn2db\Conn2db;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
```

## Ejemplos de consultas basicas
```php
$conn = new Conn2db();

$stmt = "SELECT username, pwd, avatar FROM users WHERE id = :id;";

$stmtParams = ['id' => 1];

$result = $conn->query($stmt,$stmtParams);

$data = [];

foreach($result as $x) {
    $data[] = [
        'username' => $x['username'],
        'pwd' => $x['pwd'],
        'avatar' => $x['avatar']
    ];
}
```

## Extras
Se requiere de la libreria ``symfony/dotenv``.
