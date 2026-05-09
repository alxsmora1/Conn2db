<?php
/**
 * PHP versión 8.3.
 *
 * @author Alexis Mora <alexis.mora1v@gmail.com>
 *
 * @version 2.0.0
 */

namespace Nimter\Helper\Conn2db;

/**
 * Class Conn2db.
 *
 * Clase para conectarse a base de datos a través de PDO.
 * Compatible con MySQL, MariaDB y PostgreSQL.
 */
class Conn2db
{
    // Driver de la base de datos
    protected string $DBdriver;
    // Host de la base de datos
    protected string $DBhost;
    // Puerto de conexión de la base de datos
    protected string $DBport;
    // Nombre de la base de datos
    protected string $DBname;
    // Nombre de usuario de la base de datos
    protected string $DBuser;
    // Contraseña de la base de datos
    protected string $DBpwd;
    // Codificación de la base de datos
    protected string $DBCodification;
    // Hora local de la base de datos
    private string $DBLocale;
    // Objeto PDO
    protected ?\PDO $pdo = null;
    // Parámetros para la consulta
    protected array $params = [];
    // Estado de la conexión
    protected bool $connection = false;
    // Objeto PDOStatement
    protected ?\PDOStatement $stmt = null;

    /**
     * Constructor: carga la configuración desde variables de entorno y establece la conexión.
     *
     * @throws \RuntimeException Si falta alguna variable de entorno requerida.
     **/
    public function __construct()
    {
        $required = ['DB_DRIVER', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PWD', 'DB_CODIFICATION', 'DB_LOCALE'];

        foreach ($required as $key) {
            if (!isset($_ENV[$key]) || $_ENV[$key] === '') {
                throw new \RuntimeException("Variable de entorno requerida no definida: {$key}");
            }
        }

        $this->DBdriver = $_ENV['DB_DRIVER'];
        $this->DBhost = $_ENV['DB_HOST'];
        $this->DBport = $_ENV['DB_PORT'];
        $this->DBname = $_ENV['DB_NAME'];
        $this->DBuser = $_ENV['DB_USER'];
        $this->DBpwd = $_ENV['DB_PWD'];
        $this->DBCodification = $_ENV['DB_CODIFICATION'];
        $this->DBLocale = $_ENV['DB_LOCALE'];

        $this->connection();
    }

    /**
     * Ejecuta una consulta SQL preparada y retorna el resultado adecuado según el tipo.
     *
     * @param string     $sql       Consulta SQL con placeholders nombrados.
     * @param array|null $params    Parámetros para la consulta (key => value).
     * @param int        $fetchmode Modo de fetch de PDO (por defecto FETCH_ASSOC).
     *
     * @return array|int|null Arreglo de filas para SELECT/SHOW, número de filas afectadas para
     *                        INSERT/UPDATE/DELETE, o null para otros tipos de consulta.
     *
     * @throws \PDOException Si ocurre un error en la consulta.
     **/
    public function query(string $sql, ?array $params = null, int $fetchmode = \PDO::FETCH_ASSOC): array|int|null
    {
        $sql = trim(str_replace("\r", ' ', $sql));

        $this->prepareSQL($sql, $params);

        // Extrae el primer token ignorando espacios múltiples para determinar el tipo de consulta
        $normalized = (string) preg_replace('/\s+/', ' ', $sql);
        $firstToken = strtolower(explode(' ', ltrim($normalized))[0]);

        if ('select' === $firstToken || 'show' === $firstToken) {
            return $this->stmt->fetchAll($fetchmode);
        }

        if ('insert' === $firstToken || 'update' === $firstToken || 'delete' === $firstToken) {
            return $this->stmt->rowCount();
        }

        return null;
    }

    /**
     * Agrega un parámetro al arreglo interno para ser enlazado en la próxima consulta.
     *
     * @param string $param Nombre del parámetro (sin los dos puntos).
     * @param mixed  $value Valor del parámetro.
     **/
    public function binder(string $param, mixed $value): void
    {
        $this->params[] = [':' . $param, $value];
    }

    /**
     * Retorna el último ID insertado en la sesión actual.
     *
     * @param string|null $name Nombre de la secuencia (requerido para PostgreSQL).
     *
     * @return string|false El último ID insertado, o false si no aplica.
     **/
    public function lastId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Inicia una transacción.
     *
     * @throws \PDOException Si ya hay una transacción activa o la BD no soporta transacciones.
     **/
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Confirma la transacción activa.
     *
     * @throws \PDOException Si no hay una transacción activa.
     **/
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Revierte la transacción activa.
     *
     * @throws \PDOException Si no hay una transacción activa.
     **/
    public function rollback(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Cierra la conexión con el servidor de base de datos.
     **/
    public function close(): void
    {
        $this->pdo = null;
        $this->stmt = null;
        $this->connection = false;
    }

    /**
     * Configura y establece la conexión a la base de datos.
     *
     * @throws \PDOException Si no se puede establecer la conexión.
     **/
    protected function connection(): void
    {
        $connector = $this->DBdriver . ':host=' . $this->DBhost
            . ';port=' . $this->DBport
            . ';dbname=' . $this->DBname
            . ';charset=' . $this->DBCodification;

        $attributes = [
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        // MYSQL_ATTR_INIT_COMMAND solo aplica para MySQL y MariaDB
        if (in_array(strtolower($this->DBdriver), ['mysql', 'mariadb'], true)) {
            $safeLocale = preg_replace('/[^a-zA-Z0-9_-]/', '', $this->DBLocale);
            $attributes[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET LC_TIME_NAMES='" . $safeLocale . "'";
        }

        $this->pdo = new \PDO($connector, $this->DBuser, $this->DBpwd, $attributes);
        $this->connection = true;
    }

    /**
     * Genera y ejecuta una consulta preparada a la base de datos.
     *
     * @param string     $sql    Consulta SQL.
     * @param array|null $params Parámetros de la consulta.
     *
     * @throws \PDOException Si ocurre un error en la preparación o ejecución.
     **/
    private function prepareSQL(string $sql, ?array $params = null): void
    {
        if (false === $this->connection) {
            $this->connection();
        }

        $this->stmt = $this->pdo->prepare($sql);

        // Agrega los parámetros pasados directamente al método query
        $this->addParams($params);

        // Enlaza los parámetros con su tipo correspondiente
        foreach ($this->params as $value) {
            $type = match (true) {
                is_int($value[1])  => \PDO::PARAM_INT,
                is_bool($value[1]) => \PDO::PARAM_BOOL,
                is_null($value[1]) => \PDO::PARAM_NULL,
                default            => \PDO::PARAM_STR,
            };

            // bindValue (no bindParam) para evitar problemas de referencia en bucles
            $this->stmt->bindValue($value[0], $value[1], $type);
        }

        $this->stmt->execute();

        $this->params = []; // Reinicia el arreglo de parámetros
    }

    /**
     * Transforma un arreglo asociativo de parámetros y los añade al arreglo interno.
     *
     * @param array|null $paramsArray Arreglo asociativo (key => value).
     **/
    private function addParams(?array $paramsArray): void
    {
        if (empty($this->params) && is_array($paramsArray)) {
            foreach ($paramsArray as $key => $value) {
                $this->binder((string) $key, $value);
            }
        }
    }
}
