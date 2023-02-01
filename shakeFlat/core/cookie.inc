<?php
/**
 * core/cookie.inc
 *
 * cookie handling class
 * Cookie values are packed in one cookie variable and stored.
 * For packing, security is strengthened by using AES256 encryption module.
 * If you do this, I think it's okay to use a cookie without using a session when saving login information.
 */

namespace shakeFlat;
use shakeFlat\AES256;

class Cookie
{
    private $cookie;
    private $cookieName = "sf";

    public static function getInstance($cookieName = null)
    {
        static $instance = array();
        if ($cookieName == null) $cookieName = SHAKEFLAT_ENV["cookie"]["name"] ?? "sf";
        if (isset($instance[$cookieName])) return $instance[$cookieName];
        $instance[$cookieName] = new Cookie($cookieName);
        return $instance[$cookieName];
    }

    private function __construct($cookieName)
    {
        $this->cookieName = $cookieName;
        $this->cookie = array();
        $ec = $_COOKIE[$this->cookieName] ?? null;
        if ($ec) {
            $dc = AES256::unpackObject($ec);
            if ($dc && is_array($dc)) $this->cookie = $dc;
        }
    }

    public function __set($key, $value)
    {
        $this->cookie[$key] = $value;
    }

    public function __get($key)
    {
        return $this->cookie[$key] ?? null;
    }

    public function setArray($array)
    {
        $this->cookie = array_merge($this->cookie, $array);
    }

    public function getAll()
    {
        return $this->cookie;
    }

    public function save($expireAge = null)
    {
        if ($expireAge === null) $expireAge = (SHAKEFLAT_ENV["cookie"]["expire_age"] ?? 3600);
        if ($this->cookie) {
            if ($expireAge == 0) $time = 0; else $time = time() + $expireAge;
            setcookie($this->cookieName, AES256::packObject($this->cookie), $time, "/");
        }
    }

    public function clear()
    {
        setcookie($this->cookieName, null, time(), "/");
        $this->cookie = array();
    }
}