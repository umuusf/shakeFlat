<?php
/**
 * core/config.inc
 *
 * configuration.
 * Reads config.ini to get the setting value.
 * The setting values are stored in a constant SHAKEFLAT_ENV.
 *
 */

namespace shakeFlat;

__sfConfig__init();

function __sfConfig__init()
{
    // all configurations.
    define("SHAKEFLAT_ENV", __sfConfig__parse_ini_file_extend());

    // timezone
    if (isset(SHAKEFLAT_ENV["config"]["php_timezone"])) date_default_timezone_set(SHAKEFLAT_ENV["config"]["php_timezone"]);

    // shakeFlat dir
    define("SHAKEFLAT_PATH", substr(__DIR__, 0, -4));        // -4 : "core"

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

    // storage
    if (substr(SHAKEFLAT_ENV["storage"]["storage_path"], 0, 1) == "/") {
        define("STORAGE_PATH", rtrim(SHAKEFLAT_ENV["storage"]["storage_path"], " /") . "/");
    } else {
        define("STORAGE_PATH", SHAKEFLAT_PATH . trim(SHAKEFLAT_ENV["storage"]["storage_path"], " /") . "/");
    }
    if (SHAKEFLAT_ENV["storage"]["check_storage"] ?? false) __sfConfig__checkStorage();
}

// Check the storage path in the file system. Also check the upload and log folders located under storage.
function __sfConfig__checkStorage()
{
    if (!is_dir(STORAGE_PATH)) if (!mkdir(STORAGE_PATH, 0775, true)) __sfConfig__error("Failed to create storage folder.");
    if (!is_readable(STORAGE_PATH)) __sfConfig__error("You do not have read permission on the storage folder : " . STORAGE_PATH);
    if (!is_writable(STORAGE_PATH)) __sfConfig__error("You do not have write access to the storage folder : " . STORAGE_PATH);

    $uploadPath = STORAGE_PATH . trim(SHAKEFLAT_ENV["storage"]["upload_path"], " /") . "/";
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0775, true);
    } else {
        if (!is_readable($uploadPath)) __sfConfig__error("You do not have read permission on the upload folder : {$uploadPath}");
        if (!is_writable($uploadPath)) __sfConfig__error("You do not have write access to the upload folder : {$uploadPath}");
    }

    $logPath = STORAGE_PATH . trim(SHAKEFLAT_ENV["storage"]["log_path"], " /") . "/";
    if (!is_dir($logPath)) {
        mkdir($logPath, 0775, true);
    } else {
        if (!is_readable($logPath)) __sfConfig__error("You do not have read permission on the log folder : {$logPath}");
        if (!is_writable($logPath)) __sfConfig__error("You do not have write access to the log folder : {$logPath}");
    }

    $translationPath = trim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/";
    if (substr($translationPath, 0, 1) == "/") {
        $translationPath = rtrim($translationPath, " /");
    } else {
        $translationPath = SHAKEFLAT_PATH . trim($translationPath, " /");
    }
    if (!is_dir($translationPath)) mkdir($translationPath, 0775, true);
    if (!is_readable($translationPath)) __sfConfig__error("You do not have read permission on the translation folder : {$translationPath}");
    if (!is_writable($translationPath)) __sfConfig__error("You do not have write access to the translation folder : {$translationPath}");

    $translationCachePath = $translationPath . "/cache/";
    if (!is_dir($translationCachePath)) mkdir($translationCachePath, 0775, true);
    if (!is_readable($translationCachePath)) __sfConfig__error("You do not have read permission on the translation folder : {$translationCachePath}");
    if (!is_writable($translationCachePath)) __sfConfig__error("You do not have write access to the translation folder : {$translationCachePath}");
}

// Read the config.ini file.
// When : is used in the ini section, consider the case where a dot (.) is used in each variable name.
function __sfConfig__parse_ini_file_extend()
{
    $data = parse_ini_file(__DIR__ . "/../config/config.ini", true);
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