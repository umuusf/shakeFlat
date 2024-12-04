<?php
/**
 * core/db.php
 *
 * database handling class
 */

namespace shakeFlat;
use shakeFlat\L;
use \PDO;
use \Exception;

class TransactionDBList
{
    private $transactionDBList = array();

    public static function getInstance()
    {
        static $oInstance = null;
        if ($oInstance === null) $oInstance = new TransactionDBList();
        return $oInstance;
    }

    private function __construct()
    {
        $this->transactionDBList = array();
    }

    public function add($connectionName)
    {
        $this->transactionDBList[] = $connectionName;
    }

    public function list()
    {
        return $this->transactionDBList;
    }
}

class DB
{
    private $dbh = null;
    private $errInfo = array();
    private $dbsys = "mysql";       // Database product types for DSN

    public static function getInstance($connectionName = "default")
    {
        static $oInstance = array();
        if (isset($oInstance[$connectionName])) return $oInstance[$connectionName];
        $oInstance[$connectionName] = new DB($connectionName);
        return $oInstance[$connectionName];
    }

    private function __construct($connectionName)
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$connectionName])) L::system("[:DB connection information is not defined in config.ini.:]", array( "connection" => $connectionName ));
        $connInfo = SHAKEFLAT_ENV["database"]["connection"][$connectionName][rand(0, count(SHAKEFLAT_ENV["database"]["connection"][$connectionName])-1)];

        if(isset(SHAKEFLAT_ENV["database"]["common"])) {
            foreach(SHAKEFLAT_ENV["database"]["common"] as $k => $v) {
                if (!isset($connInfo[$k])) $connInfo[$k] = $v;
            }
        }

        $this->dbh = $this->pdoConnection($connInfo);
    }

    public function dbSystem() { return $this->dbsys; }

    private function pdoConnection($connInfo)
    {
        try {
            $dsn    = trim($connInfo["dsn"], " ;");
            $user   = $connInfo["user"];
            $passwd = $connInfo["passwd"];

            $options = array(
                PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES  => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            );

            if (stripos($dsn, "sqlsrv") !== false) {
                $this->dbsys = "sqlsrv";
                $dsn .= ";LoginTimeout=" . ($connInfo["connect_timeout"] ?? 5);

            } elseif (stripos($dsn, "mysql") !== false) {
                $this->dbsys = "mysql";

                $options[PDO::ATTR_TIMEOUT] = $connInfo["connect_timeout"] ?? 5;
                $options[PDO::MYSQL_ATTR_LOCAL_INFILE] = false;

                $initCommand = array();
                if (isset($connInfo["timezone"])) $initCommand[] = "SET time_zone = '".$connInfo["timezone"]."'";
                if (isset($connInfo["mysql_charset"])) {
                    if (version_compare(PHP_VERSION, '5.3.6', '<')) {
                        $initCommand[] = "SET NAMES ".$connInfo["mysql_charset"];
                    } elseif (stripos($dsn, "charset") === false) {
                        $dsn .= ";charset=".$connInfo["mysql_charset"];
                    }
                }
                if ($initCommand) $options[PDO::MYSQL_ATTR_INIT_COMMAND] = implode(";", $initCommand);
            }

            return new PDO($dsn, $user, $passwd, $options);
        } catch (Exception $e) {
            if (SHAKEFLAT_ENV["config"]["debug_mode"] ?? false) {
                L::exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
            } else {
                L::exit("DB connection failed. ({$e->getCode()})");
            }
        }
    }

    public function quote($v)
    {
        return $this->dbh->quote($v);
    }

    // $bind : [ $key => $value, $key => $value, ... ]
    public function query($sql, $bindList = null)
    {
        $re = $this->_query($sql, $bindList);
        return $re;
    }

    // When a query error occurs, false is returned without error handling.
    public function simpleQuery($sql, $bindList = null)
    {
        return $this->_query($sql, $bindList, true);
    }

    public function errorInfo()
    {
        return $this->errInfo;
    }

    private function _query($sql, $bindList = null, $noExit = false)
    {
        if (!is_string($sql)) L::exit("The query sql statement must be in string format.");
        $this->errInfo = array();
        if (SHAKEFLAT_ENV["log"]["include_query"] ?? false) LogQuery::stack($sql, $bindList);  // log.inc
        try {
            if ($bindList === null) {
                // This is not responsible for the sql injection problem.
                // If there is a field containing a variable, $bindList must be used.
                return $this->dbh->query($sql);
            } elseif (is_array($bindList)) {
                $statement = $this->dbh->prepare($sql);
                foreach($bindList as $param => $value) {
                    switch(gettype($value)) {
                        case "boolean" : $type = PDO::PARAM_BOOL; break;
                        case "integer" : $type = PDO::PARAM_INT; break;
                        default : $type = PDO::PARAM_STR; break;
                    }
                    $b = $statement->bindValue($param, $value, $type);
                }
                $statement->execute();
                return $statement;
            }
        } catch(Exception $e) {
            $this->errInfo = array( $e->getCode(), $e->getMessage() );
            if ($noExit) return false;

            $errCode = $this->dbh->errorCode();
            $errInfo = $this->dbh->errorInfo();

            $msg = $errInfo[2] ?? "query error";
            $context = array (
                "code1" => $errCode,
                "code2" => $errInfo[1] ?? "",
            );

            if ((SHAKEFLAT_ENV["config"]["display_error"] ?? false) && (SHAKEFLAT_ENV["display_error"]["database"] ?? false)) {
                $msg .= ":{code2}({code1})\n{code3}";
                $context["code3"] = $e->getMessage();
                if (SHAKEFLAT_ENV["log"]["include_query"] ?? false) {
                    $msg .= "\nQuery:{code4}";
                    $context["code4"] = LogQuery::shakeQuery($sql, $bindList);
                }
            }

            L::exit($msg, $context);
        }
    }

    public function fetch($statement, $mode = PDO::FETCH_ASSOC)
    {
        try {
            return $statement->fetch($mode);
        } catch(Exception $e) {
            L::exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
        }
    }

    public function fetchAll($statement, $mode = PDO::FETCH_ASSOC)
    {
        return $statement->fetchAll($mode);
    }

    public function lastId()
    {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction()
    {
        if ($this->dbh->inTransaction()) return true;
        return $this->dbh->beginTransaction();
    }

    public function rollBack()
    {
        return $this->dbh->rollBack();
    }

    public function commit()
    {
        return $this->dbh->commit();
    }
}
