<?php
/**
 * libs/param.inc
 *
 * Class Param
 * Parses and analyzes GET and POST parameters.
 * 1. Checks whether the parameter exists.
 * 2. Check whether the value of each type is correct.
 * 3. Default values for each type are provided.
 * 4. Value escaping is handled in preparation for SQL Injection. (removed. We decided to use PDO in the database library, and to handle SQL injection using the bind function of PDO)
 * 5. When the file is uploaded, it is saved as a unique file name in the specified path and related information is provided.
 *
 */

namespace shakeFlat\libs;
use shakeFlat\libs\L;
use \Exception;

class Param extends L
{
    const TYPE_INT       = 1001;
    const TYPE_INTEGER   = 1001;
    const TYPE_FLOAT     = 1002;
    const TYPE_STR       = 1003;
    const TYPE_STRING    = 1003;
    const TYPE_BOOL      = 1004;
    const TYPE_BOOLEAN   = 1004;
    const TYPE_DATETIME  = 1005;
    const TYPE_DATE      = 1006;
    const TYPE_TIMESTAMP = 1007;
    const TYPE_ARRAY     = 1008;
    const TYPE_FILE      = 1009;
    const TYPE_JSON      = 1010;
    const TYPE_EMAIL     = 1011;
    const TYPE_URL       = 1012;
    const TYPE_DOMAIN    = 1013;
    const TYPE_IP        = 1014;

    private $params = array();
    private $typeInfo = array();    // It has type and enum information specified for each parameter.

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Param();
        return $instance;
    }

    private function __construct()
    {
        $this->parse();
    }

    private function parse()
    {
        $this->params = array();

        $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
        // get parameters
        if (isset($parseUrl["query"])) parse_str($parseUrl["query"], $this->params);
        // post parameters
        if ($_POST) $this->params = array_merge($this->params, $_POST);

        // File upload($_FILES) is handled separately.
    }

    // magic method to get parameter value
    // usage example) $param->view  (Gets the value of the 'view' parameter.)
    //
    // If "_d_" is added in front of the parameter name, if the value of the parameter is empty, a default value suitable for the parameter type can be obtained.
    // usage example) $param->_d_view  (If the 'view' parameter is empty and the type is integer, 0 is returned.)
    // For the default value for each type, refer to _defaultValue method.
    public function __get($key)
    {
        return $this->get($key);
    }

    // When retrieving the value of a parameter, a default value can be specified.
    public function get($key, $default = "_+-=NO_VALUE=-+_")
    {
        if (array_key_exists($key, $this->params) !== false) {
            if ($default != "_+-=NO_VALUE=-+_" && $this->params[$key] == "") return $default;
            return $this->params[$key];
        } elseif (substr($key, 0, 3) == "_d_") {
            if (array_key_exists(substr($key, 3), $this->params) !== false) {
                if ($this->params[substr($key, 3)] != "") return $this->params[substr($key, 3)];
                if ($default != "_+-=NO_VALUE=-+_") return $default;
                return $this->_defaultValue(substr($key, 3));
            } elseif (array_key_exists(substr($key, 3), $this->typeInfo) !== false) {
                return $this->_defaultValue(substr($key, 3));
            }
        }

        return null;
    }

    // Check the parameter format. If no parameters are passed, it is passed.
    // If there is a set list of values, put them in $enum in array.
    public function check($key, $type, $enum = null)
    {
        $this->_setTypeEnum($key, $type, $enum);
        if ($this->_existKey($key, $type) && $this->_existValue($key, $type)) $this->_checkType($key, $type, $enum);
    }

    // When the parameter must exist and the value can be empty
    public function checkKey($key, $type, $enum = null)
    {
        $this->_setTypeEnum($key, $type, $enum);
        if (!$this->_existKey($key, $type)) $this->exit("The parameter {$key} does not exist.");
        if ($this->_existValue($key, $type)) $this->_checkType($key, $type, $enum);
    }

    // When a parameter must exist and must also have a value
    public function checkKeyValue($key, $type, $enum = null)
    {
        $this->_setTypeEnum($key, $type, $enum);
        if (!$this->_existKey($key, $type)) $this->exit("The parameter {$key} does not exist.");
        if (!$this->_existValue($key, $type)) $this->exit("The value of parameter {$key} is empty.");
        $this->_checkType($key, $type, $enum);
    }

    private function _existKey($key, $type)
    {
        if ($type == "file") {
            if (!isset($_FILES[$key])) return false;
        } else {
            if (array_key_exists($key, $this->params) === false) return false;
        }
        return true;
    }

    private function _existValue($key, $type)
    {
        if ($type == "file") {
            if (($_FILES[$key]["error"] ?? 100) != 0) return false;
        } else {
            if (($this->params[$key] ?? "") === "") return false;
        }
        return true;
    }

    private function _defaultValue($key)
    {
        if (array_key_exists($key, $this->typeInfo) === false) return null;
        if (is_array($this->typeInfo[$key]["enum"]) && isset($this->typeInfo[$key]["enum"][0])) return $this->typeInfo[$key]["enum"][0];

        switch($this->typeInfo[$key]["type"]) {
            case Param::TYPE_INT :
            case Param::TYPE_FLOAT :     return 0;

            case Param::TYPE_STRING :
            case Param::TYPE_EMAIL :
            case Param::TYPE_URL :
            case Param::TYPE_DOMAIN :
            case Param::TYPE_IP :        return "";

            case Param::TYPE_BOOLEAN :   return false;

            case Param::TYPE_DATETIME :
            case Param::TYPE_TIMESTAMP : return "0000-00-00 00:00:00";
            case Param::TYPE_DATE :      return "0000-00-00";

            case Param::TYPE_ARRAY :
            case Param::TYPE_JSON :      return array();

            case Param::TYPE_FILE :      return null;
        }
    }

    // Saves the type and enum specified for each parameter.
    private function _setTypeEnum($key, $type, $enum)
    {
        $this->typeInfo[$key] = array (
            "type" => $type,
            "enum" => $enum,
        );
    }

    private function _checkType($key, $type, $enum)
    {
        if ($enum !== null && is_array($enum) && !in_array($this->params[$key], $enum)) $this->exit("The type of parameter {$key} is incorrect.");

        switch($type) {
            case Param::TYPE_INT :
                $val = strval(intval($this->params[$key]));
                if ($val != $this->params[$key]) $this->exit("The type of parameter {$key} is incorrect.");
                $this->params[$key] = intval($this->params[$key]);
                return;
            case Param::TYPE_FLOAT :
                $val = strval(floatval($this->params[$key]));
                if ($val != $this->params[$key]) $this->exit("The type of parameter {$key} is incorrect.");
                $this->params[$key] = floatval($this->params[$key]);
                return;
            case Param::TYPE_STRING :
                if (!is_string($this->params[$key])) $this->exit("The type of parameter {$key} is incorrect.");
                $this->params[$key] = strval($this->params[$key]);
                return;
            case Param::TYPE_BOOLEAN :
                if (strtolower($this->params[$key]) == "true") $this->params[$key] = true;
                if (strtolower($this->params[$key]) == "false") $this->params[$key] = false;
                if (!is_bool($this->params[$key])) $this->exit("The type of parameter {$key} is incorrect.");
                return;
            case Param::TYPE_JSON :
                if ($this->params[$key]) {
                    if (!is_array(json_decode($this->params[$key], true))) {
                        switch (json_last_error()) {
                            case JSON_ERROR_NONE            : $this->exit("{$key} Error in json format : No errors"); break;
                            case JSON_ERROR_DEPTH           : $this->exit("{$key} Error in json format : Maximum stack depth exceeded"); break;
                            case JSON_ERROR_STATE_MISMATCH  : $this->exit("{$key} Error in json format : Underflow or the modes mismatch"); break;
                            case JSON_ERROR_CTRL_CHAR       : $this->exit("{$key} Error in json format : Unexpected control character found"); break;
                            case JSON_ERROR_SYNTAX          : $this->exit("{$key} Error in json format : Syntax error, malformed JSON"); break;
                            case JSON_ERROR_UTF8            : $this->exit("{$key} Error in json format : Malformed UTF-8 characters, possibly incorrectly encoded"); break;
                            default                         : $this->exit("{$key} Error in json format : Unknown error"); break;
                        }
                    }
                    $this->params[$key] = json_decode($this->params[$key], true);
                } else {
                    $this->params[$key] = array();
                }
                return;

            case Param::TYPE_ARRAY       : if (!is_array($this->params[$key]))                                               $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_EMAIL       : if (filter_var($this->params[$key], FILTER_VALIDATE_EMAIL) === false)             $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_URL         : if (filter_var($this->params[$key], FILTER_VALIDATE_URL) === false)               $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_DOMAIN      : if (filter_var($this->params[$key], FILTER_VALIDATE_DOMAIN) === false)            $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_IP          : if (filter_var($this->params[$key], FILTER_VALIDATE_IP) === false)                $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_DATETIME    : if ($this->params[$key] != date("Y-m-d H:i:s", strtotime($this->params[$key])))   $this->exit("The type of parameter {$key} is incorrect.");  return;
            case Param::TYPE_DATE        : if ($this->params[$key] != date("Y-m-d", strtotime($this->params[$key])))         $this->exit("The type of parameter {$key} is incorrect.");  return;

            case Param::TYPE_FILE        : if (!isset($_FILES[$key]) || ($_FILES[$key]["error"] ?? 100) != 0)                $this->exit("The type of parameter {$key} is incorrect.");  return;
        }
        $this->exit("The type of parameter {$key} is incorrect.");
    }

    /**
     * It saves the uploaded file and returns information about the saved file (original file name, saved file name, capacity, etc.).
     * $subFolder is the path where the file will be saved on the file system. It refers to the path below the default path ($rootFolder).
     * @return array    savedFilename       저장된 파일명 (경로 포함)
     *                  originalFilename    업로드한 원본 파일명
     *                  fileType            파일 형식
     *                  fileSize            파일 크기(byte)
     */
    public function saveFile($key, $subFolder = "", $rootFolder = UPLOAD_ROOT)
    {
        try {
            if (($_FILES[$key]["error"] ?? 100) != 0) throw new Exception("파일이 업로드 되지 않았습니다.", 1);

            // 파일이 저장될 경로 결정
            if (substr($rootFolder, -1) == "/") $rootFolder = substr($rootFolder, 0, -1);
            $subFolder = trim($subFolder, " \n\r\t\v\x00\/");
            $path = "{$rootFolder}/{$subFolder}/";

            if (!is_dir($path)) {
                // warning 에러 발생 방지를 위해 @를 붙인다.
                if (!@mkdir($path, 0755, true)) throw new Exception("경로 생성에 실패하였습니다.", 2);
            }

            // 파일명 결정
            $filenamePrefix = strtolower(substr(md5($_FILES[$key]["name"] . time()), 0, 20));
            $ext = strtolower(pathinfo($_FILES[$key]["name"], PATHINFO_EXTENSION));
            $idx = 0;
            while(1) {
                $saveFilename = "{$path}{$filenamePrefix}_{$idx}.{$ext}";
                if (!file_Needs($saveFilename)) break;
                $idx++;
            }

            if (!move_uploaded_file($_FILES[$key]["tmp_name"], $saveFilename)) {
                throw new Exception("파일 시스템에 저장하는 중에 오류가 발생하였습니다.", 3);
            }

            return array (
                "savedFilename"     => $saveFilename,
                "originalFilename"  => $_FILES[$key]["name"],
                "fileType"          => $_FILES[$key]["type"],
                "fileSize"          => $_FILES[$key]["size"],
            );
        } catch(Exception $e) {
            $this->exit("[{$key}] 파일 업로드 오류 - {$e->getMessage()} ({$e->getCode()})");
        }
    }

}