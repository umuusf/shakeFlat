<?php
/**
 * core/modt.php
 *
 * Manages objects that require DB transaction processing.
 * If DB transaction processing is required in a batch when the module execution is finished, use this.
 * A method called transactionUpdate must exist in the class that uses this,
 * and the framework calls this method in batches when the module execution ends and executes it.
 *
 * The inherited class must have an update() method.
 */

namespace shakeFlat;

class Modt
{
    protected static $instance = array();

    public static function instanceList()
    {
        return self::$instance;
    }

    public static function getInstance(...$pks)
    {
        if (count($pks) == 1 && (is_integer($pks[0]) || is_string($pks[0]))) {
            $pk = $pks[0];
        } else {
            $pk = json_encode($pks, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        }
        $calledClass = get_called_class();

        if (isset(self::$instance[$calledClass][$pk])) return self::$instance[$calledClass][$pk];
        self::$instance[$calledClass][$pk] = new $calledClass($pk);
        return self::$instance[$calledClass][$pk];
    }

    protected function __construct(...$pk)
    {
        // for child class
    }

    public function update()
    {
        // The inherited class must have an update() method.
    }
}
