<?php
/**
 * core/ooro.php
 *
 * Only One Row ORM(Object Relational Mapping).
 *
 * ORM class that manages only one row.
 * Select only one row from the database and manage it as an object.
 * Since it is an object managed for one row, if you want to manage multiple rows, you can create multiple objects.
 * This class is provided as a parent class, and it is recommended to inherit from each model and use it.
 *
 */

namespace shakeFlat;
use shakeFlat\DB;
use shakeFlat\Modt;
use shakeFlat\L;

class Ooro extends Modt
{
    private $connectionName     = "default";
    private $pkList             = null;
    private $tableName          = "";
    private $pkFieldNameList    = array();
    private $excludeFieldsList  = array();
    private $preloadDataList    = null;
    private $isNoUpdate         = false;

    private $sourceDataList     = array();
    private $dataList           = array();

    // If there is preload data, use this instead of getInstance of the parent class (Modt).
    public static function getInstancePreload($pks, $preloadData)
    {
        if (is_array($pks)) {
            $pk = json_encode($pks, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } else {
            $pk = $pks;
        }
        $calledClass = get_called_class();

        if (isset(self::$instance[$calledClass][$pk])) return self::$instance[$calledClass][$pk];
        self::$instance[$calledClass][$pk] = new $calledClass($pk, $preloadData);
        return self::$instance[$calledClass][$pk];
    }

    /**
     * connection_name  : defined in config.ini
     * table_name       : table's name
     * pk_field         : Field name(s) to be used in the where condition when querying one row
     * pk_value         : Value(s) for each field name(s) defined in pk_field
     * exclude_fields   : Fields to exclude from processing (eg auto-fill fields, etc.)
     * preload_data     : If a DB query has already been performed and there is a value, a row array is delivered. This prevents the same query from being executed more than once.
     * nodata_error     : Whether or not to process an error when data does not exist. If this value is false, it automatically enters the insert mode for new row. You can determine it by calling the isNoData() method.
     */
    protected function __construct($modelsInfo)
    {
        $this->sourceDataList = $this->dataList = array();

        $this->connectionName       = $modelsInfo["connection_name"] ?? "default";
        $this->tableName            = $modelsInfo["table_name"];
        $this->pkFieldNameList      = $modelsInfo["pk_field"];
        $this->pkList               = $modelsInfo["pk_value"];
        $this->excludeFieldsList    = $modelsInfo["exclude_fields"];
        $this->preloadDataList      = $modelsInfo["preload_data"] ?? null;
        $this->isNoUpdate           = $modelsInfo["no_update"] ?? false;

        if (is_string($this->pkFieldNameList)) $this->pkFieldNameList = array( $this->pkFieldNameList );

        if ($this->preloadDataList) {
            // When the preload_data value, which is the data read in advance, is delivered
            $this->sourceDataList = $this->dataList = $this->preloadDataList;
        } else {
            $db = DB::getInstance($this->connectionName);
            $whereList = array();
            $bind = array();
            $where = "";
            foreach($this->pkFieldNameList as $idx => $f) {
                $whereList[] = "{$f} = :{$f}";
                $bind[$f] = $this->pkList[$idx];
            }
            if ($whereList) $where = implode(" and ", $whereList);
            $stmt = $db->query("select count(*) as cnt from ".$this->tableName." where $where", $bind);
            $count = intval($db->fetch($stmt)["cnt"]);
            if ($count > 1) L::exit("OORO : The query result is not one row.");
            if ($count == 1) {
                $stmt = $db->query("select * from ".$this->tableName." where $where", $bind);
                $row = $db->fetch($stmt);
                $this->sourceDataList = $this->dataList = $row;
            } else {
                if (($modelsInfo["nodata_error"] ?? false) == true) L::exit("OORO : The data does not exist.");
                foreach($this->pkFieldNameList as $idx => $f) {
                    $this->dataList[$f] = $this->pkList[$idx];      // This means that data is inserted with a new pk value.
                }
                $this->sourceDataList = null;
            }
        }
    }

    public function setNoUpdate()
    {
        $this->isNoUpdate = true;
    }

    public function isNoData()
    {
        if (!$this->sourceDataList) return true;
        return false;
    }

    public function row()
    {
        return $this->dataList;
    }

    public function __get($key)
    {
        return $this->dataList[$key] ?? false;
    }

    public function __set($key, $value)
    {
        if (in_array($key, $this->pkFieldNameList)) return false;        // The pk value cannot be changed.
        if ($this->sourceDataList && !array_key_exists($key, $this->dataList)) return false;
        $this->dataList[$key] = $value;
    }

    // Compare the original data with the modified data to update only the updated field values.
    public function update()
    {
        if ($this->isNoUpdate) return;
        if ($this->sourceDataList === $this->dataList) return;

        $db = DB::getInstance($this->connectionName);

        if (!$this->sourceDataList) {
            // If the original data is empty but new data is received, insert is executed.
            $fList = array();
            $vList = array();
            $bind = array();
            foreach($this->dataList as $f => $v) {
                if (in_array($f, $this->excludeFieldsList)) continue;
                $fList[] = "`{$f}`";
                $vList[] = ":{$f}";
                $bind[":{$f}"] = $v;
            }
            $db->query("insert into {$this->tableName} ( ".implode(",", $fList)." ) values ( ".implode(",", $vList)." )", $bind);
        } else {
            $updateList = array();
            $bind = array();
            foreach($this->sourceDataList as $k => $v) {
                if (in_array($k, $this->excludeFieldsList) || in_array($k, $this->pkFieldNameList)) continue;
                if ($this->sourceDataList[$k] != $this->dataList[$k]) {
                    $updateList[] = "{$k} = :{$k}";
                    $bind[":{$k}"] = $this->dataList[$k];
                }
            }

            if ($updateList) {
                $whereList = array();
                $where = "";
                foreach($this->pkFieldNameList as $idx => $f) {
                    $whereList[] = "{$f} = :{$f}";
                    $bind[":{$f}"] = $this->pkList[$idx];
                }
                if ($whereList) $where = implode(" and ", $whereList);

                $db->query("update {$this->tableName} set " . implode(",", $updateList) . " where {$where}", $bind);
            }
        }
        $this->sourceDataList = $this->dataList;
    }
}
