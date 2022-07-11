<?php
/**
 * libs/db.inc
 *
 * database handling class
 */

namespace shakeFlat\libs;
use shakeFlat\libs\L;
use \PDO;
use \Exception;

class DB extends L
{
    private $dbh = null;

    public static function getInstance($connectionName = "default")
    {
        static $oInstance = array();
        if (isset($oInstance[$connectionName])) return $oInstance[$connectionName];
        $oInstance[$connectionName] = new DB($connectionName);
        return $oInstance[$connectionName];
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

    private function _query($sql, $bindList = null, $noExit = false)
    {
        if (SHAKEFLAT_ENV["log"]["include_query"] ?? false) LogQuery::stack($sql, $bindList);  // log.inc
        try {
            if ($bindList === null) {
                // This is not responsible for the sql injection problem. If there is a field containing a variable, $bindList must be used.
                return $this->dbh->query($sql);
            } elseif (is_array($bindList)) {
                $statement = $this->dbh->prepare($sql);
                foreach($bindList as $param => $value) {
                    switch(gettype($value)) {
                        case "boolean" : $type = PDO::PARAM_BOOL; break;
                        case "integer" : $type = PDO::PARAM_INT; break;
                        default : $type = PDO::PARAM_STR; break;
                    }
                    $statement->bindValue($param, $value, $type);
                }
                $statement->execute();
                return $statement;
            }
        } catch(Exception $e) {
            $msg = $this->dbh->errorInfo()[2] ?? "query error";
            $msg .= " ({code2})";
            $context = array (
                "code1" => $this->dbh->errorCode(),
                "code2" => $this->dbh->errorInfo()[1] ?? "",
            );
            if ($noExit) {
                $this->error($msg, $context);
                return false;
            } else {
                $this->exit($msg, $context);
            }
        }
    }

    public function fetch($statement)
    {
        try {
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
        }
    }

    public function fetchAll($statement)
    {
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastId()
    {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction()
    {
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

    private function __construct($connectionName)
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$connectionName])) $this::system("DB connection information is not defined in config.ini.", array( "connection" => $connectionName ));
        $connInfo = SHAKEFLAT_ENV["database"]["connection"][$connectionName][rand(0, count(SHAKEFLAT_ENV["database"]["connection"][$connectionName])-1)];

        if(isset(SHAKEFLAT_ENV["database"]["common"])) {
            foreach(SHAKEFLAT_ENV["database"]["common"] as $k => $v) {
                if (!isset($connInfo[$k])) $connInfo[$k] = $v;
            }
        }

        $this->dbh = PDOConnection::conn($connInfo);
    }
}

class PDOConnection extends L
{
    private static $connList = array();

    public static function conn($connInfo)
    {
        $uStr = serialize($connInfo);
        if (isset(self::$connList[$uStr])) return self::$connList[$uStr];

        try {
            $dsn    = trim($connInfo["dsn"], " ;");
            $user   = $connInfo["user"];
            $passwd = $connInfo["passwd"];

            $options = array(
                PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT           => $connInfo["connect_timeout"] ?? 5,
                PDO::ATTR_EMULATE_PREPARES  => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            );

            if (stripos($dsn, "mysql") !== false) {
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

            $dbh = new PDO($dsn, $user, $passwd, $options);
            self::$connList[$uStr] = $dbh;
            return self::$connList[$uStr];
        } catch (Exception $e) {
            self::exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
        }
    }
}