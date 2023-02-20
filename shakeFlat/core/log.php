<?php
/**
 * core/log.php
 *
 * by shakeFlat design intent,
 * In most cases where an error occurs in the process of processing one web page, it is based on the fact that the process ends after outputting an appropriate error screen.
 * Conversely, most of the logs written without terminating the process will be for debugging purposes.
 * The log class of shakeFlat provides three methods. debug, error, exit.
 * Debug and error end their role by writing logs to their respective log levels. However, exit terminates the process after writing a log.
 *
 */

namespace shakeFlat;
use shakeFlat\Template;
use shakeFlat\Response;
use \DateTime;
use \DateTimeZone;

// Log class in shakeFlat framework
// It can be used alone or as a parent class.
// Here, only two log levels of error and debug are used. In case of exit and system, the error level is used.
class L
{
    private static function logInstance()
    {
        static $log = null;
        if ($log) return $log;
        $log = new Log();
        return $log;
    }

    public static function defaultErrorMessage($message = null)
    {
        static $msg = "Oops!! An error has occurred. Engineers comparable to advanced AI are working hard to fix it. We won't let you down!!";
        if ($message !== null) $msg = $message;
        return $msg;
    }

    // shakeFlat framework structure error. App developers will seldom use it.
    // Do not record logs.
    public static function system($message, $context = array(), $exception = null)
    {
        $message = self::getTranslationLangSystem($message);
        list($message, $context) = self::_shakeMsgContext($message, $context, $exception);
        self::_terminate(array("message" => $message, "context" => $context));
    }

    // Terminates the process after logging. (exit)
    // In general, if a web process (each web page or API) encounters a (severe) error during its operation, all operations are stopped and the process is terminated.
    // For reference, when the process is terminated, the open db transaction is automatically rolled back.
    public static function exit($message, $context = array(), $exception = null)
    {
        self::_terminate(self::error($message, $context, $exception));
    }

    // Terminates the process after logging. (exit)
    // In general, if a web process (each web page or API) encounters a (severe) error during its operation, all operations are stopped and the process is terminated.
    // For reference, when the process is terminated, the open db transaction is automatically rolled back.
    // In addition to $message, displays files and lines where execution is suspended according to config settings.
    public static function exitCode($message, $code)
    {
        self::_terminate(self::error($message, array(), null), $code);
    }

    // Print $message, $code and terminate the process.
    // No other information is displayed.
    // It is recommended to use it in the API module.
    public static function exitNoti($message, $code)
    {
        $template = Template::getInstance();
        $message = self::getTranslationLangSystem($message);
        $template->displayError($message, array(), $code);
        exit;
    }

    // Logging for each log level. exit is not handled.
    public static function error($message, $context = array(), $exception = null)
    {
        list($message, $context) = self::_shakeMsgContext($message, $context, $exception);
        return self::logInstance()->error($message, $context);
    }

    public static function debug($message, $context = array(), $exception = null)
    {
        list($message, $context) = self::_shakeMsgContext($message, $context, $exception);
        return self::logInstance()->debug($message, $context);
    }

    // Display an error screen and exit.
    // $logMsg => array( "message" => string, "context" => array )
    private static function _terminate($logMsg, $errCode = -1)
    {
        if (SHAKEFLAT_ENV["config"]["display_error"] ?? false) {
            if (SHAKEFLAT_ENV["config"]["debug_mode"] ?? false) {
                $message = $logMsg["message"];
                $context = $logMsg["context"];
                if (isset($context["trace"][0]["file"]) && isset($context["trace"][0]["line"])) {
                    $message .= ", passed in {$context["trace"][0]["file"]} on line {$context["trace"][0]["line"]}";
                }
            } else {
                $message = $logMsg["message"];
                $context = null;
            }
        } else {
            $message = self::defaultErrorMessage();
            $context = null;

            $message = self::getTranslationLangSystem($message);
        }

        $template = Template::getInstance();
        $template->displayError($message, $context, $errCode);
        exit;
    }

    // Writes a trace and, if there is an exception error, adds it to the context.
    private static function _shakeMsgContext($message, $context = array(), $exception = null)
    {
        if (SHAKEFLAT_ENV["log"]["include_parameter"] ?? false) {
            $params = array();
            $parseUrl = parse_url($_SERVER["REQUEST_URI"] ?? 0);
            if (isset($parseUrl["query"])) parse_str($parseUrl["query"], $params);
            if ($_POST) $params = array_merge($params, $_POST);
            $context["parameters"] = $params;

            if ($_FILES) $context["file_upload"] = array_keys($_FILES);
        }

        if (SHAKEFLAT_ENV["log"]["include_trace"] ?? false) {
            $backtrace = debug_backtrace();
            $traceLog = array();
            foreach($backtrace as $bt) {
                if (($bt["class"] ?? "") == get_class()) continue;
                if (($bt["file"] ?? "") == "") continue;

                if (SHAKEFLAT_ENV["log"]["trace_short"] ?? false) {
                    $traceLog[] = $bt["file"] . ":" . ($bt["line"] ?? -1);
                } else {
                    $traceLog[] = array (
                        "file"      => $bt["file"],
                        "line"      => $bt["line"] ?? -1,
                        "function"  => str_replace(array("sfErrorHandlerShutdown", "sfErrorHandler"), "", $bt["function"]) ?? "",
                        "class"     => $bt["class"] ?? "",
                    );
                }
            }
            if ($traceLog) $context["trace"] = $traceLog;
        }

        if (SHAKEFLAT_ENV["log"]["include_query"] ?? false) {
            $queries = LogQuery::list();
            if ($queries) $context["query"] = $queries;
        }

        if ($exception !== null) $context["exception"] = $exception;
        return array($message, $context);
    }

    private static function getTranslationLangSystem($msg)
    {
        $path = __DIR__ . "/translation.json";
        if (!file_exists($path)) return $msg;
        $json = file_get_contents($path);
        if ($json === false) return $msg;
        $allTable = json_decode($json, true);
        $template = Template::getInstance();
        $lang = $template->getTranslationLang();
        if (!$lang) return $msg;
        if (isset($allTable[$msg][0][$lang])) return $allTable[$msg][0][$lang];

        $re = $msg;
        foreach($allTable as $str => $arr) {
            $str = str_replace(array("$1", "$2", "$3", "$4", "$5", "$6", "$7", "$8", "$9"), "([a-zA-Z0-9, ]*)", $str);
            $str = str_replace(array("/"), array("\/"), $str);

            $reg = preg_match_all("/^{$str}$/", $msg, $match);
            if (isset($match[0]) && $match[0] && isset($arr[0][$lang])) {
                $re = $arr[0][$lang];
                for($i=1;$i<=9;$i++) {
                    if (isset($match[$i][0])) {
                        $re = str_replace("$".$i, $match[$i][0], $re);
                    }
                }
                break;
            }
        }

        return $re;
    }
}

// A simple class based on PSR3 (https://www.php-fig.org/psr/psr-3/)
class Log
{
    public function emergency($message, $context = array()) { return $this->_log(LogLevel::EMERGENCY, $message, $context); }
    public function alert($message, $context = array())     { return $this->_log(LogLevel::ALERT, $message, $context); }
    public function critical($message, $context = array())  { return $this->_log(LogLevel::CRITICAL, $message, $context); }
    public function error($message, $context = array())     { return $this->_log(LogLevel::ERROR, $message, $context); }
    public function warning($message, $context = array())   { return $this->_log(LogLevel::WARNING, $message, $context); }
    public function notice($message, $context = array())    { return $this->_log(LogLevel::NOTICE, $message, $context); }
    public function info($message, $context = array())      { return $this->_log(LogLevel::INFO, $message, $context); }
    public function debug($message, $context = array())     { return $this->_log(LogLevel::DEBUG, $message, $context); }

    public function query($message, $context = array())     { return $this->_log(LogLevel::DEBUG, $message, $context, true); }

    // If an Exception object is passed to the context data, it must be in the 'exception' key
    private function _log($logLevel, $message, $context = array())
    {
        __sfConfig__checkStorage();

        $gpath = GPath::getInstance();
        $logPath = $gpath->STORAGE . trim(SHAKEFLAT_ENV["storage"]["log_path"], " /") . "/";
        $logFile = "log-".date("Ymd").".log";

        $message = $this->interpolate($message, $context);

        $date = new DateTime('now', new DateTimeZone(SHAKEFLAT_ENV["log"]["timezone"] ?? SHAKEFLAT_ENV["config"]["php_timezone"]));
        $time = $date->format('Y-m-d\TH:i:sP');     // W3C style
        $level = strtoupper($logLevel);

        if ((SHAKEFLAT_ENV["log"]["json_format"] ?? false)) {
            $logMsg = json_encode(array(
                "datetime"  => $time,
                "level"     => $level,
                "message"   => $message,
                "context"   => $context,
            ), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } else {
            $delimiter = SHAKEFLAT_ENV["log"]["delimiter"] ?? "\t";
            if ($delimiter == "\\t") $delimiter = "\t";
            $context_str = json_encode($context, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            $logMsg = "{$time}{$delimiter}{$level}{$delimiter}{$message}{$delimiter}{$context_str}";
        }

        error_log($logMsg . "\n", 3, $logPath . $logFile);

        return array("message" => $message, "context" => $context);
    }

    private function interpolate($message, $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            // check that the value can be casted to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace["{" . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}

// log level list (PSR3 recommendation)
class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
}

// Saves all executed SQL query statements. It is recorded in the log according to the config settings.
class LogQuery
{
    private static $queryStack = array();

    public static function shakeQuery($sql, $bind)
    {
        $l = explode("\n", $sql);
        $ns = array();
        foreach($l as $i) $ns[] .= trim($i, " \r\t");
        $sql = trim(implode(" ", $ns));
        if ($bind) {
            foreach($bind as $k => $v) {
                if (substr($k, 0, 1) != ":") $k = ":".$k;
                $sql = str_replace($k, $v, $sql);
            }
        }

        if (SHAKEFLAT_ENV["log"]["query_logging"] ?? false) {
            $gpath = GPath::getInstance();
            $logPath = $gpath->STORAGE . trim(SHAKEFLAT_ENV["storage"]["log_path"], " /") . "/";
            $logFile = "query-".date("Ymd").".log";
            $date = new DateTime('now', new DateTimeZone(SHAKEFLAT_ENV["log"]["timezone"] ?? SHAKEFLAT_ENV["config"]["php_timezone"]));
            $time = $date->format('Y-m-d\TH:i:sP');     // W3C style
            $level = LogLevel::INFO;

            if ((SHAKEFLAT_ENV["log"]["json_format"] ?? false)) {
                $logMsg = json_encode(array(
                    "datetime"  => $time,
                    "level"     => $level,
                    "message"   => $sql,
                    "context"   => array(),
                ), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
            } else {
                $delimiter = SHAKEFLAT_ENV["log"]["delimiter"] ?? "\t";
                if ($delimiter == "\\t") $delimiter = "\t";
                $logMsg = "{$time}{$delimiter}{$level}{$delimiter}{$sql}";
            }

            error_log($logMsg . "\n", 3, $logPath . $logFile);
        }
        return $sql;
    }

    public static function stack($sql, $bind)
    {
        self::$queryStack[] = self::shakeQuery($sql, $bind);
    }

    public static function list()
    {
        return self::$queryStack;
    }
}