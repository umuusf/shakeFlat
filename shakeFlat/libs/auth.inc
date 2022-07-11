<?php
/**
 * libs/auth.inc
 *
 * Class for authentication management including login
 *
 */

namespace shakeFlat\libs;
use shakeFlat\libs\Cookie;
use shakeFlat\libs\AES256;
use shakeFlat\libs\L;

// Manage authentication information using session.
class AuthSession extends _Auth
{
    // for log-in.
    // authInfo is an array type containing authentication information.
    // Generally, the one with the result of querying the user table of the DB in the form of an array is used.
    public static function setAuthInfo($authInfo)
    {
        parent::setAuthInfo($authInfo);
        self::burn();
    }

    // for logout
    public static function unsetAuthInfo()
    {
        parent::unsetAuthInfo();
        self::clear();
    }

    // Check whether you are logged in.
    public static function check()
    {
        self::load();
        return (parent::check() && parent::extend());
    }

    // Get each field(key) value of authInfo. The type of authInfo is array.
    public static function get($field)
    {
        if (!parent::authInfo()) self::load();
        if (self::check()) return parent::authInfo()[$field] ?? null;
        return null;
    }

    private static function load()
    {
        if (parent::authInfo()) return;
        self::initSession();
        $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
        $sv = $_SESSION[$key] ?? false;
        if ($sv) {
            $authInfo = AES256::unpackObject($sv);
            if ($authInfo) {
                parent::setAuthInfo($authInfo);
            }
        }
        session_write_close();
    }

    private static function burn()
    {
        if (parent::authInfo()) {
            self::initSession();
            $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
            $_SESSION[$key] = AES256::packObject(parent::authInfo());
            session_write_close();
        }
    }

    private static function clear()
    {
        self::initSession();
        $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
        unset($_SESSION[$key]);
        session_write_close();
    }

    private static function initSession()
    {
        try {
            session_name(SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass");
            if (SHAKEFLAT_ENV["auth"]["session"]["session_redis"] ?? false) {
                ini_set('session.save_handler', 'redis');
                ini_set('session.save_path', SHAKEFLAT_ENV["auth"]["session"]["session_path"] ?? "tcp://localhost:6379");
            } else {
                $path = SHAKEFLAT_ENV["auth"]["session"]["session_path"] ?? false;
                if ($path !== false) {
                    if (substr($path, 0, 1) === "/") {
                        session_save_path($path);
                    } else {
                        $path = SHAKEFLAT_PATH . "/" . $path;
                        if (SHAKEFLAT_ENV["auth"]["session"]["check_path"] ?? false) {
                            if (!is_dir($path)) mkdir($path);
                            if (!file_exists($path)) $this::system("The storage folder does not exist in the file system.", array( "path" => $path ));
                            if (!is_readable($path)) $this::system("You do not have read permission on the storage folder.", array( "path" => $path ));
                            if (!is_writable($path)) $this::system("You do not have write access to the storage folder.", array( "path" => $path ));
                        }
                        session_save_path($path);
                    }
                }
            }
            session_start();
        } catch (Exception $e) {
            L::exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
        }
    }
}

// Manage authentication information using cookies.
class AuthCookie extends _Auth
{
    // for log-in.
    // authInfo is an array type containing authentication information.
    // Generally, the one with the result of querying the user table of the DB in the form of an array is used.
    public static function setAuthInfo($authInfo)
    {
        parent::setAuthInfo($authInfo);
        self::burn();
    }

    // for logout
    public static function unsetAuthInfo()
    {
        parent::unsetAuthInfo();
        self::cookieInstance()->clear();
    }

    // Check whether you are logged in.
    public static function check()
    {
        $info = self::cookieInstance()->getAll();
        if ($info) {
            parent::setAuthInfo($info);
            // Call the parent::check() function inside the parent::extend() function.
            if (parent::extend()) {
                self::burn();       // Extend the expiration time to re-save the cookie.
                return true;
            }
        }
        return false;
    }

    // Get each field(key) value of authInfo. The type of authInfo is array.
    public static function get($field)
    {
        if (self::check()) return parent::authInfo()[$field] ?? null;
        return null;
    }

    // Save the cookie.
    private static function burn()
    {
        if (parent::authInfo()) {
            self::cookieInstance()->setArray(parent::authInfo());
            self::cookieInstance()->save(0);        // Cookie retention time is until the web browser is closed.
                                                    // In the _Auth class, expire_time is managed according to the value set in config.ini.
        }
    }

    // Get a Cookie instance.
    private static function cookieInstance()
    {
        static $authCookie;
        if ($authCookie) return $authCookie;
        $key = SHAKEFLAT_ENV["auth"]["cookie"]["cookie_name"] ?? "sfa";
        $authCookie = Cookie::getInstance($key);
        return $authCookie;
    }
}

// A class that stores and manages authentication information.
// Used as a parent class.
class _Auth extends L
{
    private static $authInfo = array();

    // It is determined whether the current authentication information is valid.
    public static function check()
    {
        if (!self::$authInfo) return false;
        return !self::isExpired();
    }

    public static function expireTime()
    {
        return self::$authInfo["expire_time"] ?? 0;
    }

    public static function authInfo()
    {
        return self::$authInfo;
    }

    public static function isExpired()
    {
        if (self::expireTime() < time()) return true;
        return false;

    }

    public static function extend()
    {
        if (!self::check()) return false;
        $expireAge = SHAKEFLAT_ENV["auth"]["expire_age"] ?? 864000;
        self::$authInfo["expire_time"] = time() + $expireAge;
        return true;
    }

    public static function setAuthInfo($authInfo)
    {
        self::$authInfo = $authInfo;
        $expireAge = SHAKEFLAT_ENV["auth"]["expire_age"] ?? 864000;
        self::$authInfo["expire_time"] = time() + $expireAge;
    }

    public static function unsetAuthInfo()
    {
        self::$authInfo = array();
    }
}