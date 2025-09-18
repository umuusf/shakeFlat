<?php
/**
 * core/param.php
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

namespace shakeFlat;
use shakeFlat\AES256;
use shakeFlat\Util;
use shakeFlat\L;
use \Exception;

class Param
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

    const FILE_ALL       = 2001;
    const FILE_IMAGE     = 2002;

    private $params = array();
    private $encryptParams = array();
    private $enableEncrypt = false;
    private $typeInfo = array();    // It has type and enum information specified for each parameter.
    private $noEnc = false;

    // $queryString : a=1&b=2&...
    public static function getInstance($queryString = null)
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Param($queryString);
        return $instance;
    }

    private function __construct($queryString)
    {
        $this->params = array();
        $this->encryptParams = array();
        $this->enableEncrypt = false;

        if ($queryString === null) {
            $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
            // get parameters
            if (isset($parseUrl["query"])) parse_str($parseUrl["query"], $this->params);
            // post parameters
            if ($_POST) $this->params = array_merge($this->params, $_POST);
        } else {
            parse_str($queryString, $this->params);
        }
        // File upload($_FILES) is handled separately.

        if (IS_DEBUG) {
            if (($this->params["noenc"] ?? 0) == 1) $this->noEnc = true;
        }
    }

    public function encryptParam($k, $key = null, $iv = null, $zip = true, $reset = true)
    {
        if ($reset) $this->encryptParams = array();

        // get parameters
        $get = null;
        $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
        if (isset($parseUrl["query"])) parse_str($parseUrl["query"], $get);

        if (IS_DEBUG) {
            $noenc = $_POST["noenc"] ?? $get["noenc"] ?? null;
            if ($noenc == 1) $this->noEnc = true;

            if ($get) $this->encryptParams = array_merge($this->encryptParams, $get);
            if ($_POST) $this->encryptParams = array_merge($this->encryptParams, $_POST);
            unset($this->encryptParams[$k]);
        }

        $data = $_POST[$k] ?? $get[$k] ?? null;
        if ($data) {
            if ($key === null) $key = SHAKEFLAT_ENV["aes256"]["key_with_client"] ?? "00000000000000000000000000000000";
            if ($iv === null) $iv = SHAKEFLAT_ENV["aes256"]["iv_with_client"] ?? "0000000000000000";
            $dec = AES256::unpack($data, $key, $iv, $zip);
            if ($dec) parse_str($dec, $this->encryptParams);
        }

        $this->enableEncrypt = true;
    }

    public function importParam($params, $reset = true)
    {
        if ($reset) $this->params = array();
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->params[$k] = $v;
            }
        } else {
            L::exit("[:Invalid parameter format. It must be an array.:]");
        }
    }

    public function getAll()
    {
        if ($this->enableEncrypt) {
            // If encryption is enabled, return the decrypted parameters.
            return $this->encryptParams;
        }
        return $this->params;
    }

    public function debugNoEnc()
    {
        return IS_DEBUG && $this->noEnc;
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
        $param = $this->params;
        if ($this->enableEncrypt) {
            // If encryption is enabled, return the decrypted parameters.
            $param = $this->encryptParams;
        }
        if (array_key_exists($key, $param) !== false) {
            if ($default !== "_+-=NO_VALUE=-+_" && $param[$key] == "") return $default;
            return $param[$key];
        } elseif (substr($key, 0, 3) == "_d_") {
            if (array_key_exists(substr($key, 3), $param) !== false) {
                if ($param[substr($key, 3)] != "") return $param[substr($key, 3)];
                if ($default !== "_+-=NO_VALUE=-+_") return $default;
                return $this->_defaultValue(substr($key, 3));
            } elseif (array_key_exists(substr($key, 3), $this->typeInfo) !== false) {
                return $this->_defaultValue(substr($key, 3));
            }
        }

        if ($default !== "_+-=NO_VALUE=-+_") return $default;
        return null;
    }

    public function getDate($key, $format = "Y-m-d")
    {
        $val = $this->get($key);
        if ($val === null || $val === "") return null;
        $t = strtotime($val);
        if ($t === false) return null;
        return date($format, $t);
    }

    public function getDateTime($key, $format = "Y-m-d H:i:s")
    {
        return $this->getDate($key, $format);
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
        if (!$this->_existKey($key, $type)) L::exit("[:The parameter {$key} does not exist.:]");
        if ($this->_existValue($key, $type)) $this->_checkType($key, $type, $enum);
    }

    // When a parameter must exist and must also have a value
    public function checkKeyValue($key, $type, $enum = null)
    {
        $this->_setTypeEnum($key, $type, $enum);
        if (!$this->_existKey($key, $type)) L::exit("[:The parameter {$key} does not exist.:]");
        if (!$this->_existValue($key, $type)) L::exit("[:The value of parameter {$key} is empty.:]");
        $this->_checkType($key, $type, $enum);
    }

    public function checkFileType($key, $type = SELF::FILE_ALL)
    {
        if (!$this->_existKey($key, self::TYPE_FILE)) L::exit("[:The parameter {$key} does not exist.:]");
        if (!$this->_existValue($key, SELF::TYPE_FILE)) return;
        switch($type) {
            case SELF::FILE_ALL : break;
            case SELF::FILE_IMAGE :
                if (!exif_imagetype($_FILES[$key]["tmp_name"])) L::exit("[:The type of parameter {$key} is incorrect.:]");
                break;
        }
        return;
    }

    public function existsFile($key)
    {
        return (($_FILES[$key]["error"] ?? 100) === 0) && isset($_FILES[$key]["tmp_name"]);
    }

    public function exists($key)
    {
        $param = $this->params;
        if ($this->enableEncrypt) {
            // If encryption is enabled, check the decrypted parameters.
            $param = $this->encryptParams;
        }
        return (array_key_exists($key, $param) === true || array_key_exists($key, $_FILES) === true);
    }

    private function _existKey($key, $type)
    {
        $param = $this->params;
        if ($this->enableEncrypt) {
            // If encryption is enabled, check the decrypted parameters.
            $param = $this->encryptParams;
        }
        if ($type == self::TYPE_FILE) {
            if (!isset($_FILES[$key]) && array_key_exists($key, $param) === false) return false;
        } else {
            if (array_key_exists($key, $param) === false) return false;
        }
        return true;
    }

    private function _existValue($key, $type)
    {
        $param = $this->params;
        if ($this->enableEncrypt) {
            // If encryption is enabled, check the decrypted parameters.
            $param = $this->encryptParams;
        }
        if ($type == self::TYPE_FILE) {
            if (($_FILES[$key]["error"] ?? 100) != 0) return false;
        } else {
            if (($param[$key] ?? "") === "") return false;
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
        $param = &$this->params;
        if ($this->enableEncrypt) {
            // If encryption is enabled, check the decrypted parameters.
            $param = &$this->encryptParams;
        }
        if ($enum !== null && is_array($enum) && !in_array($param[$key], $enum)) L::exit("[:The type of parameter {$key} is incorrect.:]");

        switch($type) {
            case Param::TYPE_INT :
                $val = strval(intval($param[$key]));
                if ($val != $param[$key]) L::exit("[:The type of parameter {$key} is incorrect.:]");
                $param[$key] = intval($param[$key]);
                return;
            case Param::TYPE_FLOAT :
                $val = strval(floatval($param[$key]));
                if ($val != $param[$key]) L::exit("[:The type of parameter {$key} is incorrect.:]");
                $param[$key] = floatval($param[$key]);
                return;
            case Param::TYPE_STRING :
                if (!is_string($param[$key])) L::exit("[:The type of parameter {$key} is incorrect.:]");
                $param[$key] = strval($param[$key]);
                return;
            case Param::TYPE_BOOLEAN :
                if (strtolower($param[$key]) == "true") $param[$key] = true;
                if (strtolower($param[$key]) == "false") $param[$key] = false;
                if (!is_bool($param[$key])) L::exit("[:The type of parameter {$key} is incorrect.:]");
                return;
            case Param::TYPE_JSON :
                if ($param[$key]) {
                    if (!is_array(json_decode($param[$key], true))) {
                        switch (json_last_error()) {
                            case JSON_ERROR_NONE            : L::exit("[:{$key} Error in json format : No errors:]"); break;
                            case JSON_ERROR_DEPTH           : L::exit("[:{$key} Error in json format : Maximum stack depth exceeded:]"); break;
                            case JSON_ERROR_STATE_MISMATCH  : L::exit("[:{$key} Error in json format : Underflow or the modes mismatch:]"); break;
                            case JSON_ERROR_CTRL_CHAR       : L::exit("[:{$key} Error in json format : Unexpected control character found:]"); break;
                            case JSON_ERROR_SYNTAX          : L::exit("[:{$key} Error in json format : Syntax error, malformed JSON:]"); break;
                            case JSON_ERROR_UTF8            : L::exit("[:{$key} Error in json format : Malformed UTF-8 characters, possibly incorrectly encoded:]"); break;
                            default                         : L::exit("[:{$key} Error in json format : Unknown error:]"); break;
                        }
                    }
                    $param[$key] = json_decode($param[$key], true);
                } else {
                    $param[$key] = array();
                }
                return;

            case Param::TYPE_ARRAY       : if (!is_array($param[$key]))                                         L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_EMAIL       : if (filter_var($param[$key], FILTER_VALIDATE_EMAIL) === false)       L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_URL         : if (filter_var($param[$key], FILTER_VALIDATE_URL) === false)         L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_DOMAIN      : if (filter_var($param[$key], FILTER_VALIDATE_DOMAIN) === false)      L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_IP          : if (filter_var($param[$key], FILTER_VALIDATE_IP) === false)          L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_FILE        : if (!isset($_FILES[$key]) || ($_FILES[$key]["error"] ?? 100) != 0)   L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_DATETIME    : if (Util::validateDateTime($param[$key]) === false)                  L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
            case Param::TYPE_DATE        : if (Util::validateDate($param[$key]) === false)                      L::exit("[:The type of parameter {$key} is incorrect.:]");  return;
        }
        L::exit("[:The type of parameter {$key} is incorrect.:]");
    }

    /**
     * It saves the uploaded file and returns information about the saved file (original file name, saved file name, capacity, etc.).
     * $subFolder is the path where the file will be saved on the file system. It refers to the path below the default path ($rootFolder).
     * @return array    savedFilename       저장된 파일명 (경로 포함)
     *                  originalFilename    업로드한 원본 파일명
     *                  fileType            파일 형식
     *                  fileSize            파일 크기(byte)
     */
    public function saveFile($key, $subFolder, $rootFolder)
    {
        try {
            if (($_FILES[$key]["error"] ?? 100) != 0) throw new Exception("upload failed.");

            // 파일이 저장될 경로 결정
            if (substr($rootFolder, -1) == "/") $rootFolder = substr($rootFolder, 0, -1);
            $subFolder = trim($subFolder, " \n\r\t\v\x00\/");
            $path = "{$rootFolder}/{$subFolder}/";

            if (!is_dir($path)) {
                // warning 에러 발생 방지를 위해 @를 붙인다.
                if (!@mkdir($path, 0755, true)) throw new Exception("Directory creation failed.");
            }

            // 파일명 결정
            $filenamePrefix = strtolower(substr(md5($_FILES[$key]["name"] . time()), 0, 20));
            $ext = strtolower(pathinfo($_FILES[$key]["name"], PATHINFO_EXTENSION));
            $idx = 0;
            while(1) {
                $saveFilename = "{$path}{$filenamePrefix}_{$idx}.{$ext}";
                if (!file_exists($saveFilename)) break;
                $idx++;
            }

            if (!move_uploaded_file($_FILES[$key]["tmp_name"], $saveFilename)) {
                throw new Exception("Failed to save file.");
            }

            return array (
                "savedFilename"     => $saveFilename,
                "originalFilename"  => $_FILES[$key]["name"],
                "fileType"          => $_FILES[$key]["type"],
                "fileSize"          => $_FILES[$key]["size"],
            );
        } catch(Exception $e) {
            L::exit("[:'{$key}' File upload error - {$e->getMessage()}:]", $e->getCode());
        }
    }

}