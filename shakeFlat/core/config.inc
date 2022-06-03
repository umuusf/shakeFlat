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
use shakeFlat\libs\L;

class Config
{
    public static function init()
    {
        // all configurations.
        define("SHAKEFLAT_ENV", self::parse_ini_file_extend());

        // shakeFlat dir
        define("SHAKEFLAT_PATH", substr(__DIR__, 0, -4));        // -4 : "libs"

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

        // timezone
        if (isset(SHAKEFLAT_ENV["config"]["php_timezone"])) date_default_timezone_set(SHAKEFLAT_ENV["config"]["php_timezone"]);

        // storage
        if (substr(SHAKEFLAT_ENV["storage"]["storage_path"], 0, 1) == "/") {
            define("STORAGE_PATH", rtrim(SHAKEFLAT_ENV["storage"]["storage_path"], " /") . "/");
        } else {
            define("STORAGE_PATH", SHAKEFLAT_PATH . trim(SHAKEFLAT_ENV["storage"]["storage_path"], " /") . "/");
        }
        if (SHAKEFLAT_ENV["storage"]["check_storage"] ?? false) self::checkStorage();

    }

    // Check the storage path in the file system. Also check the upload and log folders located under storage.
    private static function checkStorage()
    {
        if (!file_exists(STORAGE_PATH)) L::system("The storage folder does not exist in the file system : ".STORAGE_PATH);
        if (!is_readable(STORAGE_PATH)) L::system("You do not have read permission on the storage folder : ".STORAGE_PATH);
        if (!is_writable(STORAGE_PATH)) L::system("You do not have write access to the storage folder : ".STORAGE_PATH);

        $uploadPath = STORAGE_PATH . trim(SHAKEFLAT_ENV["storage"]["upload_path"], " /") . "/";
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath);
        } else {
            if (!is_readable($uploadPath)) L::system("You do not have read permission on the storage folder : {$uploadPath}");
            if (!is_writable($uploadPath)) L::system("You do not have write access to the storage folder : {$uploadPath}");
        }

        $logPath = STORAGE_PATH . trim(SHAKEFLAT_ENV["storage"]["log_path"], " /") . "/";
        if (!is_dir($logPath)) {
            mkdir($logPath);
        } else {
            if (!is_readable($logPath)) L::system("You do not have read permission on the storage folder : {$logPath}");
            if (!is_writable($logPath)) L::system("You do not have write access to the storage folder : {$logPath}");
        }
    }

    // Read the config.ini file.
    // When : is used in the ini section, consider the case where a dot (.) is used in each variable name.
    private static function parse_ini_file_extend()
    {
        $data = parse_ini_file(__DIR__ . "/../config/config.ini", true);
        if (!$data) L::system("Could not read config.ini");

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
}