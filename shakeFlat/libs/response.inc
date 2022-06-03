<?php
/**
 * libs/response.inc
 *
 * It stores the processed values in modules and delivers them to templates.
 */

namespace shakeFlat\libs;

class Response
{
    private $responseData;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Response();
        return $instance;
    }

    private function __construct()
    {
        $this->responseData = array(
            "common" => array ( "time" => time() ),
            "error"  => array ( "errCode" => 0, "errMsg" => null ),
            "data"   => array (),
        );
    }

    public function setCommon($key, $value)
    {
        $this->responseData["common"][$key] = $value;
    }

    public function __set($key, $value)
    {
        $this->responseData["data"][$key] = $value;
    }

    public function __get($key)
    {
        if (isset($this->responseData["data"][$key])) return $this->responseData["data"][$key];
        return false;
    }

    public function get($key, $default = false)
    {
        return $this->responseData["data"][$key] ?? $default;
    }

    public function setArray($array)
    {
        foreach($array as $k => $v) {
            $this->responseData["data"][$k] = $v;
        }
    }

    public function data()
    {
        return $this->responseData;
    }
}
