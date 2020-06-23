<?php
/**
 * PHP versión 7.3.0.
 *
 * @author Alexis Mora <alexis.mora1v@gmail.com>
 *
 * @version 1.0.0
 */

namespace Nimter\Helper\Conn2db;

/**
 * Class Conn2db.
 *
 * Clase para conectarse a base de datos a través de PDO
 */
class Conn2db
{
    // @var, Driver de la base de datos
    protected $driver;
    // @var, Host de la base de datos
    protected $host;
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
    // @bool, Estado de la conexión
    protected $connection = false;

    /**
     * Function __constructor.
     *
     * Función que carga la conexión a la base de datos.
     **/
    public function __construct()
    {
        $this->DBdriver = $_ENV['DB_DRIVER'];
        $this->DBhost   = $_ENV['DB_HOST'];
        $this->DBport   = $_ENV['DB_PORT'];
        $this->DBname   = $_ENV['DB_NAME'];
        $this->DBuser   = $_ENV['DB_USER'];
        $this->DBpwd    = $_ENV['DB_PWD'];
        $this->DBCodification    = $_ENV['DB_CODIFICATION'];
        $this->DBLocale = $_ENV['DB_LOCALE'];
        $this->params   = [];
        $this->connection();
    }

    /**
     * Function query.
     *
     * Función que genera los resultados de la consulta y completa las funciones de la clase.
     *
     * @param string $sql
     * @param array  $params
     * @param string $fetchmode
     *
     * @return void
     **/
    public function query($sql, $params = null, $fetchmode = \PDO::FETCH_ASSOC)
    {
        $sql = trim(str_replace("\r", ' ', $sql));

        $this->prepareSQL($sql, $params);

        $rawStmt = explode(' ', preg_replace("/\s+|\t+|\n+/", ' ', $sql));

        //Determina el tipo de consulta y entrega el resultado adecuado dependiendo de la consulta
        $cleanStmt = strtolower($rawStmt[0]);

        if ('select' === $cleanStmt || 'show' === $cleanStmt) {
            return $this->stmt->fetchAll($fetchmode);
        }

        if ('insert' === $cleanStmt || 'update' === $cleanStmt || 'delete' === $cleanStmt) {
            return $this->stmt->rowCount();
        }

        return null;
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
     * Function close.
     *
     * Cierra la conexión con el servidor de base de datos.
     **/
    public function close()
    {
        $this->pdo = null;
    }

    /**
     * Function connection.
     *
     * Función que configura y establece la conexión a la base de datos.
     **/
    protected function connection()
    {
        try {
            $connector = $this->DBdriver.':host='.$this->DBhost.';dbname='.$this->DBname.';charset='.$this->DBCodification;

            $attributes = [
                \PDO::ATTR_PERSISTENT => false,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET LC_TIME_NAMES='".$this->DBLocale."'",
            ];

            $this->pdo = new \PDO($connector, $this->DBuser, $this->DBpwd, $attributes);

            $this->connection = true;
        } catch (\PDOException $e) {
            echo __LINE__.$e->getMessage();
        }
    }

    /**
     * Function prepareSQL.
     *
     * Función que genera una consulta preparada a la base de datos.
     *
     * @param string $sql
     * @param array  $params
     *
     * @return void
     **/
    private function prepareSQL($sql, $params = '')
    {
        try {
            if (true === $this->connection) {
                $this->connection();
            }

            $this->stmt = $this->pdo->prepare($sql);

            //Agrega los parametros al arreglo de parametros
            $this->addParams($params);

            //Asigna los parametros y el tipo de parametro
            if (!empty($this->params)) {
                foreach ($this->params as $param => $value) {
                    if (is_int($value[1])) {
                        $type = \PDO::PARAM_INT;
                    } elseif (is_bool($value[1])) {
                        $type = \PDO::PARAM_BOOL;
                    } elseif (is_null($value[1])) {
                        $type = \PDO::PARAM_NULL;
                    } else {
                        $type = \PDO::PARAM_STR;
                    }

                    $this->stmt->bindParam($value[0], $value[1], $type);
                }
            }
            // Ejecuta la consulta SQL
            $this->stmt->execute();
        } catch (\PDOException $e) {
            echo __LINE__.$e->getMessage();
        }

        $this->params = []; //Reinicia el arreglo
    }

    /**
     * Function addParams.
     * Función que agrega un parametro bindeado a la consulta.
     *
     * @param array $paramsArray
     *
     * @return void
     **/
    private function addParams($paramsArray)
    {
        if (empty($this->params) && is_array($paramsArray)) {
            $keys = array_keys($paramsArray);
            foreach ($keys as $x => &$key) {
                $this->binder($key, $paramsArray[$key]);
            }
        }
    }
}
