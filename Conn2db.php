<?php
/**
 * PHP versión 8.1.0.
 *
 * @author Alexis Mora <alexis.mora1v@gmail.com>
 *
 * @version 1.0.5
 */

namespace Nimter\Helper\Conn2db;

use PDO;
use PDOException;
use Exception;

/**
 * Class Conn2db.
 *
 * Clase para conectarse a base de datos a través de PDO
 */
class Conn2db
{
    // @var, Driver de la base de datos
    protected $DBdriver;
    // @var, Host de la base de datos
    protected $DBhost;
    // @var, Puerto de conexión de la base de datos
    protected $DBport;
    // @var, Nombre de la base de datos
    protected $DBname;
    // @var, Nombre de usuario de la base de datos
    protected $DBuser;
    // @var, Contraseña de la base de datos
    protected $DBpwd;
    // @var, Codificación de la base de datos
    protected $DBCodification;
    // @var, Hora local de la base de datos
    private $DBLocale;
    // @object, Objeto PDO
    protected $pdo;
    // @array, Parametros para la consulta
    protected $params;
    // @object, PDOStatement
    protected $stmt;
    // @bool, Estado de la conexión
    protected $connection = false;

/**
     * Constructor.
     *
     * Initializes the database connection.
     */
    public function __construct()
    {
        $this->DBdriver = getenv('DB_DRIVER');
        $this->DBhost = getenv('DB_HOST');
        $this->DBport = getenv('DB_PORT');
        $this->DBname = getenv('DB_NAME');
        $this->DBuser = getenv('DB_USER');
        $this->DBpwd = getenv('DB_PWD');
        $this->DBCodification = getenv('DB_CODIFICATION');
        $this->DBLocale = getenv('DB_LOCALE');
        $this->params = [];
        $this->connect();
    }

    /**
     * Executes a query and returns the results.
     *
     * @param string $sql
     * @param array|null $params
     * @param string $fetchmode
     *
     * @return mixed
     */
    public function query($sql, $params = null, $fetchmode = PDO::FETCH_ASSOC)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($params) {
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll($fetchmode);
        } catch (PDOException $e) {
            // Log the error message
            error_log("Query error: " . $e->getMessage());
            throw new Exception("Query error");
        }
    }

    /**
     * Function binder.
     *
     * Función recorre los parametros y los añade al arreglo despues de bindearlos.
     *
     * @param string $param
     * @param string $value
     *
     * @return void
     **/
    public function binder($param, $value)
    {
        $this->params[sizeof($this->params)] = [':'.$param, $value];
    }

    /**
     * Function lastId.
     *
     * Función retorna el ultimo ID insertado en una transacción.
     *
     * @return string - El ultimo ID insertado
     **/
    public function lastId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Closes the connection to the database server.
     */
    public function close()
    {
        try {
            $this->pdo = null;
            $this->connection = false;
            // Log the successful closure of the connection
            error_log("Database connection closed successfully.");
        } catch (Exception $e) {
            // Log any errors that occur during the closure
            error_log("Error closing database connection: " . $e->getMessage());
            throw new Exception("Error closing database connection");
        }
    }

    /**
     * Establishes the database connection using PDO.
     */
    private function connect()
    {
        try {
            $dsn = "{$this->DBdriver}:host={$this->DBhost};port={$this->DBport};dbname={$this->DBname};charset={$this->DBCodification}";
            $this->pdo = new PDO($dsn, $this->DBuser, $this->DBpwd);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection = true;
        } catch (PDOException $e) {
            // Log the error message
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection error");
        }
    }
}
