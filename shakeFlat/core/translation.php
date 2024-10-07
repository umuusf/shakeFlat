<?php
/**
 * core/translation.php
 *
 * A class that supports language translation when composing a website consisting of two or more languages.
 * Developers can select the language they want to output through the App class.
 * One entire translation file defined in config.ini is required,
 * and translation files for individual web pages and individual languages are created and managed separately internally.
 * These individual translation files are managed using a file system.
 *
 */

namespace shakeFlat;

require_once "gpath.php";

class Translation
{
    private $cacheTable;
    private $needUpdate;
    private $translationFilePath;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Translation();
        return $instance;
    }

    private function __construct()
    {
        $this->needUpdate = false;
        $this->cacheTable = array();
        $gpath = GPath::getInstance();
        $this->translationFilePath = $gpath->TRANSLATION_FILE;
    }

    public function setFilePathTranslation($filePath)
    {
        $this->translationFilePath = $filePath;
        return $this;
    }

    public function convert($output, $lang)
    {
        // In the case of debug mode, the cache file is updated every time.
        // If the translation file is modified in the live environment, be sure to delete the cache files.
        if (!IS_DEBUG) {
            $filepath = $this->cacheFilepath($lang);
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

        $re = preg_match_all("/\[[0-9]*\:(.*?)\:\]/", $output, $match);
        foreach($match[0] as $idx => $s) {
            $text = $match[1][$idx];
            $code = 0;
            if (substr($s, 0, 2) != "[:") $code = intval(substr($s, 1, -1));
            $output = str_replace($s, $this->_L($text, $code, $lang), $output);
        }

        return $output;
    }

    // Import the original data without translating the content.
    public function passing($output)
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

    public function updateCache($lang)
    {
        if (!$this->needUpdate && !IS_DEBUG) return;

        $filepath = $this->cacheFilepath($lang);
        if ($filepath) {
            $text = json_encode($this->cacheTable, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            if ($text) {
                $re = file_put_contents($filepath, $text);
                //if ($re === false) $this->error = true;
            }
        }
    }

    private function _L($k, $code, $lang)
    {
        if (!isset($this->cacheTable[$k][$code][$lang]) || IS_DEBUG) {
            $allTable = $this->loadAll();
            $re = $k;
            if (!IS_DEBUG && isset($allTable[$k][$code][$lang])) {
                $re = $allTable[$k][$code][$lang];
            } else {
                foreach($allTable as $str => $arr) {
                    $str = str_replace(array("$1", "$2", "$3", "$4", "$5", "$6", "$7", "$8", "$9"), "(.+)", $str);
                    $str = str_replace(array("/"), array("\/"), $str);
                    $reg = preg_match_all("/^{$str}$/", $k, $match);
                    if (isset($match[0]) && $match[0] && isset($arr[$code][$lang])) {
                        $re = $arr[$code][$lang];
                        for($i=1;$i<=9;$i++) {
                            if (isset($match[$i][0])) {
                                $re = str_replace("$".$i, $match[$i][0], $re);
                            }
                        }
                        break;
                    }
                }
            }

            $this->cacheTable[$k][$code][$lang] = $re;
            $this->needUpdate = true;
            return $re;
        }
        return $this->cacheTable[$k][$code][$lang];
    }

    private function cacheFilepath($lang)
    {
        __sfConfig__checkStorage();

        $gpath = GPath::getInstance();
        if (!isset(SHAKEFLAT_ENV["storage"]["translation_cache"])) return false;

        $cachePath = rtrim($gpath->STORAGE . SHAKEFLAT_ENV["storage"]["translation_cache"], " /") . "/";

        if (!is_dir($cachePath)) if (!mkdir($cachePath, 0775, true)) return false;
        if (!is_writable($cachePath)) return false;

        $router = Router::getInstance();
        return $cachePath . str_replace("/", ".", "{$router->module()}/{$router->fnc()}") . ".{$lang}.json";
    }

    private function loadAll()
    {
        static $allTable = null;
        if ($allTable) return $allTable;
        if (!file_exists($this->translationFilePath)) return array();
        $json = file_get_contents($this->translationFilePath);
        if ($json === false) return array();
        $arr = json_decode($json, true);
        if (!$arr) return array();
        $allTable = $arr;

        return $allTable;
    }
}
