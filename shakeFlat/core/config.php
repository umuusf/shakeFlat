<?php
/**
 * core/config.php
 *
 * configuration.
 * Reads config.ini to get the setting value.
 * The setting values are stored in a constant SHAKEFLAT_ENV.
 *
 */

namespace shakeFlat;

require_once "gpath.php";

__sfConfig__init();

function __sfConfig__init()
{
    // all configurations.
    define("SHAKEFLAT_ENV", __sfConfig__parse_ini_file_extend());

    // timezone
    if (isset(SHAKEFLAT_ENV["config"]["php_timezone"])) date_default_timezone_set(SHAKEFLAT_ENV["config"]["php_timezone"]);

    // shakeFlat dir
    define("SHAKEFLAT_PATH", substr(__DIR__, 0, -4));        // -4 : "core", include last /

    // debug mode
    define("IS_DEBUG", SHAKEFLAT_ENV["config"]["debug_mode"] ?? false);

    // Determining whether to output an error
    if (SHAKEFLAT_ENV["config"]["display_error"] ?? false) {
        error_reporting(E_ALL);
        ini_set("display_errors", "1");
    } else {
        error_reporting(0);
        ini_set("display_errors", "0");
    }

    // path
    $gpath = GPath::getInstance();
    $gpath->MODULES     = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["modules"]     ?? "sample/modules", " /") . "/";
    $gpath->TEMPLATES   = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["templates"]   ?? "sample/modules/admin", " /") . "/";
    $gpath->MODELS      = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["models"]      ?? "sample/models", " /") . "/";
    $gpath->DATATABLES  = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["datatables"]  ?? "sample/datatables", " /") . "/";
    $gpath->TRANSLATION = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["translation"] ?? "sample/translation/trans.json", " /") . "/";
    $gpath->STORAGE     = SHAKEFLAT_PATH . rtrim(SHAKEFLAT_ENV["path"]["storage"]     ?? "sample/storage", " /") . "/";

    __sfConfig__checkStorage();
}

// Check the storage path in the file system. Also check the upload and log folders located under storage.
function __sfConfig__checkStorage()
{
    if (!(SHAKEFLAT_ENV["storage"]["check_storage"] ?? false)) return;

    $gpath = GPath::getInstance();

    if (!is_dir($gpath->STORAGE)) if (!mkdir($gpath->STORAGE, 0775, true)) __sfConfig__error("Failed to create storage folder.");
    if (!is_readable($gpath->STORAGE)) __sfConfig__error("You do not have read permission on the storage folder : " . $gpath->STORAGE);
    if (!is_writable($gpath->STORAGE)) __sfConfig__error("You do not have write access to the storage folder : " . $gpath->STORAGE);

    $list = array ( "upload" => "upload_path", "log" => "log_path", "translation_cache" => "translation_cache" );
    foreach($list as $alias => $p) {
        $subPath = $gpath->STORAGE . trim(SHAKEFLAT_ENV["storage"][$p], " /") . "/";
        if (!is_dir($subPath)) {
            mkdir($subPath, 0775, true);
        } else {
            if (!is_readable($subPath)) __sfConfig__error("You do not have read permission on the {$alias} folder : {$subPath}");
            if (!is_writable($subPath)) __sfConfig__error("You do not have write access to the {$alias} folder : {$subPath}");
        }
    }
}

// Read the config.ini file.
// When : is used in the ini section, consider the case where a dot (.) is used in each variable name.
function __sfConfig__parse_ini_file_extend()
{
    $path = __DIR__ . "/../config/config.ini";
    if (defined("SF_CONFIG_INI")) $path = SF_CONFIG_INI;
    $data = parse_ini_file($path, true);
    if (!$data) __sfConfig__error("Could not read config.ini");

    $explode_str = '.';
    $escape_char = "'";
    foreach ($data as $section_key => $section) {
        $section_data = array();
        foreach ($section as $key => $value) {
            if (strpos($key, $explode_str)) {
                if (substr($key, 0, 1) !== $escape_char) {
                    $sub_keys = explode($explode_str, $key);
                    $subs = & $section_data[$sub_keys[0]];
                    unset($sub_keys[0]);
                    foreach ($sub_keys as $sub_key) {
                        if (!isset($subs[$sub_key])) $subs[$sub_key] = [];
                        $subs =&$subs[$sub_key];
                    }
                    $subs = $value;
                } else {
                    $new_key = trim($key, $escape_char);
                    $section_data[$new_key] = $value;
                }
            } else {
                $section_data[$key] = $value;
            }
        }
        $section_name = trim($section_key);
        $section_extends = "";
        if (strpos($section_key, ":") != false) {
            list($section_name, $section_extends) = explode(':', $section_key);
            $section_name = trim($section_name);
            $section_extends = trim($section_extends);
        }
        if (!isset($data[$section_name])) $data[$section_name] = array();
        if ($section_extends) {
            $data[$section_name][$section_extends] = $section_data;
            unset($data[$section_key]);
        }
    }

    return $data;
}

function __sfConfig__error($msg)
{
    $backtrace = debug_backtrace();
    echo "*** CONFIG ERROR ***<br>\n";
    echo "{$msg}<br>\n";
    if (isset($backtrace[0]["file"])) echo "{$backtrace[0]["file"]}:{$backtrace[0]["line"]}<br>\n";
    exit;
}