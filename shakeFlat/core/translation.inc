<?php
/**
 * core/translation.inc
 *
 * A class that supports language translation when composing a website consisting of two or more languages.
 * Developers can select the language they want to output through the App class.
 * One entire translation file defined in config.ini is required,
 * and translation files for individual web pages and individual languages are created and managed separately internally.
 * These individual translation files are managed using a file system.
 *
 */

namespace shakeFlat;

class Translation
{
    private $module;
    private $lang;
    private $cacheTable;
    private $needUpdate;
    private $error;

    public function __construct($module, $lang)
    {
        $this->module = $module;
        $this->lang = $lang;
        $this->needUpdate = false;
        $this->cacheTable = array();
        $this->error = false;

        $filepath = $this->cacheFilepath();
        if ($filepath) {
            if (file_exists($filepath)) {
                $json = file_get_contents($filepath);
                if ($json !== false) {
                    $arr = json_decode($json, true);
                    if ($arr) $this->cacheTable = $arr;
                }
            }
        }
    }

    public function convert($output)
    {
        $re = preg_match_all("/\[[0-9]*\:(.*?)\:\]/", $output, $match);

        foreach($match[0] as $idx => $s) {
            $text = $match[1][$idx];
            $code = 0;
            if (substr($s, 0, 2) != "[:") $code = intval(substr($s, 1, -1));
            $output = str_replace($s, $this->_L($text, $code, $this->lang), $output);
        }
        return $output;
    }

    // Import the original data without translating the content.
    public static function passing($output)
    {
        $re = preg_match_all("/\[[0-9]*\:(.*?)\:\]/", $output, $match);

        foreach($match[0] as $idx => $s) {
            $text = $match[1][$idx];
            $code = 0;
            if (substr($s, 0, 2) != "[:") $code = intval(substr($s, 1, -1));
            $output = str_replace($s, $text, $output);
        }
        return $output;
    }

    public function updateCache()
    {
        if (!$this->needUpdate) return;

        $filepath = $this->cacheFilepath();
        if ($filepath) {
            $text = json_encode($this->cacheTable, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            if ($text) {
                $re = file_put_contents($filepath, $text);
                if ($re === false) $this->error = true;
            }
        }
    }

    private function _L($k, $code, $lang)
    {
        if (!isset($this->cacheTable[$k][$code][$lang])) {
            $allTable = $this->loadAll();
            $re = $allTable[$k][$code][$lang] ?? $k;
            $this->cacheTable[$k][$code][$lang] = $re;
            $this->needUpdate = true;
            return $re;
        }
        return $this->cacheTable[$k][$code][$lang];
    }

    private function cacheFilepath()
    {
        if (!isset(SHAKEFLAT_ENV["translation"]["path"])) { $this->error = true; return false; }
        if (substr(SHAKEFLAT_ENV["translation"]["path"], 0, 1) == "/") {
            $path = rtrim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/cache/";
        } else {
            $path = SHAKEFLAT_PATH . trim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/cache/";
        }
        if (!is_dir($path)) if (!mkdir($path, 0775, true)) { $this->error = true; return false; }
        if (!is_writable($path)) { $this->error = true; return false; }
        return $path . str_replace("/", ".", $this->module) . ".{$this->lang}.json";
    }

    private function loadAll()
    {
        static $allTable = null;
        if ($allTable) return $allTable;

        if (!isset(SHAKEFLAT_ENV["translation"]["path"])) { $this->error = true; return array(); }
        if (!isset(SHAKEFLAT_ENV["translation"]["translation_file"])) { $this->error = true; return array(); }

        if (substr(SHAKEFLAT_ENV["translation"]["path"], 0, 1) == "/") {
            $filepath = rtrim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/" . SHAKEFLAT_ENV["translation"]["translation_file"];
        } else {
            $filepath = SHAKEFLAT_PATH . trim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/" . SHAKEFLAT_ENV["translation"]["translation_file"];
        }
        if (!file_exists($filepath)) { $this->error = true; return array(); }

        $json = file_get_contents($filepath);
        if ($json === false) { $this->error = true; return array(); }
        $arr = json_decode($json, true);
        if (!$arr) { $this->error = true; return array(); }
        $allTable = $arr;

        return $allTable;
    }
}
