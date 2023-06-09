<?php
/**
 * core/auth.php
 *
 * Class for authentication management including login
 *
 */

namespace shakeFlat;
use shakeFlat\Cookie;
use shakeFlat\AES256;

// Manage authentication information using session.
class AuthSession
{
    private $_auth;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new AuthSession();
        return $instance;
    }

    private function __construct()
    {
        $this->_auth = _Auth::getInstance();
    }

    // for log-in.
    // authInfo is an array type containing authentication information.
    // Generally, the one with the result of querying the user table of the DB in the form of an array is used.
    public function setAuthInfo($authInfo, $isRemember = false)
    {
        ini_set('session.gc_maxlifetime', SHAKEFLAT_ENV["auth"]["expire_age"] ?? 3600);
        $this->_auth->setAuthInfo($authInfo, $isRemember);
        self::burn();
    }

    public function updateAuthInfo($authInfo)
    {
        $this->_auth->resetAuthInfo($authInfo);
        self::burn();
    }

    // for logout
    public function unsetAuthInfo()
    {
        $this->_auth->unsetAuthInfo();
        self::clear();
    }

    // Check whether you are logged in.
    public function check()
    {
        self::load();
        if (!$this->_auth->extend()) return false;

        self::burn();
        return true;
    }

    public function authInfo()
    {
        if (self::check()) return $this->_auth->authInfo;
        return null;
    }

    // Get each field(key) value of authInfo. The type of authInfo is array.
    public function get($field)
    {
        if (self::check()) return $this->_auth->authInfo[$field] ?? null;
        return null;
    }

    private function load()
    {
        if ($this->_auth->authInfo) return;
        self::initSession();
        $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
        $sv = $_SESSION[$key] ?? false;
        if ($sv) {
            $authInfo = AES256::unpackObject($sv);
            if ($authInfo) {
                $this->_auth->resetAuthInfo($authInfo);
            }
        }
        session_write_close();

        // for remember me
        if (!$this->_auth->authInfo) {
            $info = self::rememberCookieInstance()->getAll();
            if ($info) $this->_auth->resetAuthInfo($info);
        }
    }

    private function burn()
    {
        if ($this->_auth->authInfo) {
            self::initSession();
            $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
            $_SESSION[$key] = AES256::packObject($this->_auth->authInfo);
            session_write_close();

            // Session is automatically deleted by gc, so cookie is used for remember function.
            if ($this->_auth->isRemember()) {
                self::rememberCookieInstance()->setArray($this->_auth->authInfo);
                self::rememberCookieInstance()->save(365 * 24 * 60 * 60);
            }
        }
    }

    private function clear()
    {
        self::rememberCookieInstance()->clear();

        self::initSession();
        $key = SHAKEFLAT_ENV["auth"]["session"]["session_name"] ?? "sfass";
        unset($_SESSION[$key]);
        session_write_close();
    }

    private function initSession()
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
                        $gpath = GPath::getInstance();
                        $path = $gpath->STORAGE . $path;
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

    // Get a Cookie instance.
    private function rememberCookieInstance()
    {
        static $authCookie;
        if ($authCookie) return $authCookie;
        $key = SHAKEFLAT_ENV["auth"]["cookie"]["cookie_name"] ?? "sfa";
        $key .= "_rm";  // remember me
        $authCookie = Cookie::getInstance($key);
        return $authCookie;
    }
}

// Manage authentication information using cookies.
class AuthCookie
{
    private $_auth;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new AuthCookie();
        return $instance;
    }

    private function __construct()
    {
        $this->_auth = _Auth::getInstance();
    }

    // for log-in.
    // authInfo is an array type containing authentication information.
    // Generally, the one with the result of querying the user table of the DB in the form of an array is used.
    public function setAuthInfo($authInfo, $isRemember = false)
    {
        $this->_auth->setAuthInfo($authInfo, $isRemember);
        self::burn();
    }

    public function updateAuthInfo($authInfo)
    {
        $this->_auth->resetAuthInfo($authInfo);
        self::burn();
    }

    // for logout
    public function unsetAuthInfo()
    {
        $this->_auth->unsetAuthInfo();
        self::cookieInstance()->clear();
    }

    // Check whether you are logged in.
    public function check()
    {
        $info = self::cookieInstance()->getAll();
        if ($info) {
            $this->_auth->resetAuthInfo($info);
            // Call the $this->_auth->check() function inside the $this->_auth->extend() function.
            if ($this->_auth->extend()) {
                self::burn();       // Extend the expiration time to re-save the cookie.
                return true;
            }
        }
        return false;
    }

    public function authInfo()
    {
        if (self::check()) return $this->_auth->authInfo;
        return null;
    }

    // Get each field(key) value of authInfo. The type of authInfo is array.
    public function get($field)
    {
        if (self::check()) return $this->_auth->authInfo[$field] ?? null;
        return null;
    }

    // Save the cookie.
    private function burn()
    {
        if ($this->_auth->authInfo) {
            self::cookieInstance()->setArray($this->_auth->authInfo);
            $expireAge = 0;
            if ($this->_auth->isRemember()) $expireAge = 365 * 24 * 60 * 60;      // 1 year...
            // If not remember, cookie retention time is until the web browser is closed. (expireAge = 0)
            // In the _Auth class, expire_time is managed according to the value set in config.ini.
            self::cookieInstance()->save($expireAge);
        }
    }

    // Get a Cookie instance.
    private function cookieInstance()
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
class _Auth
{
    public $authInfo = array();

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new _Auth();
        return $instance;
    }

    private function __construct()
    {
        $this->authInfo = array();
    }

    /*
    public function authInfo()
    {
        return self::$authInfo;
    }
    */

    public function setRemember()
    {
        $this->authInfo["sf_remember"] = true;
    }

    public function isRemember()
    {
        return $this->authInfo["sf_remember"] ?? false;
    }

    public function expireTime()
    {
        return $this->authInfo["sf_expire_time"] ?? 0;
    }

    public function isExpired()
    {
        if ($this->expireTime() < time() && $this->authInfo["sf_remember"] != true) return true;
        return false;
    }

    public function extend()
    {
        if (!$this->authInfo) return false;
        if ($this->authInfo["sf_remember"] ?? false) return true;
        if ($this->isExpired()) return false;

        $expireAge = SHAKEFLAT_ENV["auth"]["expire_age"] ?? 3600;
        $this->authInfo["sf_expire_time"] = time() + $expireAge;
        return true;
    }

    public function resetAuthInfo($authInfo)
    {
        if (!$this->checkValidAuthInfo($authInfo)) return;
        $this->authInfo = $authInfo;
        $expireAge = SHAKEFLAT_ENV["auth"]["expire_age"] ?? 3600;
        $this->authInfo["sf_expire_time"] = time() + $expireAge;
    }

    public function setAuthInfo($authInfo, $isRemember = false)
    {
        if (!$this->checkValidAuthInfo($authInfo)) return;
        $this->authInfo = $authInfo;
        $expireAge = SHAKEFLAT_ENV["auth"]["expire_age"] ?? 3600;
        $this->authInfo["sf_expire_time"] = time() + $expireAge;
        $this->authInfo["sf_remember"] = $isRemember;
    }

    public function updateAuthInfo($authInfo)
    {
        $this->resetAuthInfo($authInfo);
    }

    public function unsetAuthInfo()
    {
        $this->authInfo = array();
    }

    private function checkValidAuthInfo($authInfo)
    {
        if (!is_array($authInfo)) return false;
        if (!$authInfo) return false;
        return true;
    }
}