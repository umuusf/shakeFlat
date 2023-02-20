<?php
/**
 * core/gpath.php
 *
 * Manage the path values defined in config.
 * These values can be changed using the method in core.php.
 *
 */

namespace shakeFlat;

class GPath
{
    private $path;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new GPath();
        return $instance;
    }

    private function __construct()
    {
        $this->path = array();
    }

    public function __set($key, $value)
    {
        $this->path[$key] = $value;
    }

    public function __get($key)
    {
        if (isset($this->path[$key])) return $this->path[$key];
        return false;
    }

    public function get($key, $default = false)
    {
        return $this->path[$key] ?? $default;
    }

    public function setArray($array)
    {
        foreach($array as $k => $v) {
            $this->path[$k] = $v;
        }
    }

    public function all()
    {
        return $this->path;
    }
}
