<?php
/**
 * core/core.inc
 *
 * shakeFlat main
 *
 */

namespace shakeFlat;
use shakeFlat\Template;
use shakeFlat\libs\Router;
use shakeFlat\libs\L;
use shakeFlat\libs\DB;
use shakeFlat\libs\Ooro;
use shakeFlat\libs\Modt;
use shakeFlat\libs\Response;
use shakeFlat\libs\Util;

use \Exception;

class App extends L
{
    private $transactionDB  = array();
    private $pathModule     = "";
    private $template       = null;

    public function __construct()
    {
        $this->transactionDB    = array();
        $this->pathModule       = SHAKEFLAT_PATH . "modules/";

        $this->template = Template::getInstance();
        $this->template->setMode(Template::MODE_WEB);
    }

    public function setTransaction($connectionName = "default")
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$connectionName])) $this::system("DB connection information is not defined in config.ini.", array( "connection" => $connectionName ));
        $this->transactionDB[] = $connectionName;
        return $this;
    }

    public function setPathModule($pathModule)
    {
        $this->pathModule = $pathModule;
        return $this;
    }

    public function setPathTemplate($pathTemplate)
    {
        $this->template->setPathTemplate($pathTemplate);
        return $this;
    }

    public function setTemplate($template)
    {
        $this->template->setTemplate($template);
        return $this;
    }

    public function setLayoutFile($layoutFile)
    {
        $this->template->setLayoutFile($layoutFile);
        return $this;
    }

    // Set the file to be used as layout on the screen where the error will be displayed. (default is layout.html)
    public function setLayoutFileForError($layoutFileForError)
    {
        $this->template->setLayoutFileForError($layoutFileForError);
        return $this;
    }

    // Set the default value of response mode.
    // If you set it up again in an individual module, use it.
    public function setMode($mode)
    {
        $constList = Util::classDefineList("shakeFlat\Template", "MODE_", true);
        if (!in_array($mode, array_values($constList))) $this::system("This template mode does not exist.");
        $this->template->setMode($mode);
        return $this;
    }

    // If display_error is false in config.ini, set the error message to be displayed on the screen when an error occurs.
    public function setDefaultErrorMessage($msg)
    {
        L::defaultErrorMessage($msg);
    }

    // Select the language to use when outputting results.
    // You need a translation file defined in translation session in config.ini.
    public function setTranslationLang($lang = null)
    {
        $this->template->setTranslationLang($lang);
        if ($lang) $this->checkTranslation();
        return $this;
    }

    // Executes one module.
    // Be sure to call exec after calling set related method first.
    public function execModule()
    {
        $dbList = array();
        if ($this->transactionDB) {
            foreach($this->transactionDB as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

        $router = Router::getInstance();
        $moduleFile = rtrim($this->pathModule, " /") . "/{$router->module()}/{$router->fnc()}.php";
        if (!file_exists($moduleFile)) $this::system("The file corresponding to module/function does not exist.", array( "module" => $router->module(), "function" => $router->fnc() ));
        if (!include_once($moduleFile)) $this::system("The file corresponding to module/function cannot be included.", array( "module" => $router->module(), "function" => $router->fnc() ));

        $fncName = "fnc_" . str_replace("-", "_", $router->fnc());
        if (!function_exists($fncName)) $this::system("A function corresponding to module/function does not exist.", array( "module" => $router->module(), "function" => $router->fnc() ));

        call_user_func($fncName, $this);

        $modtList = Modt::instanceList();
        if ($modtList) {
            foreach($modtList as $class => $pks) foreach($pks as $pk => $modt) $modt->update();
        }

        if ($dbList) {
            foreach($dbList as $db) $db->commit();
        }

        return $this;
    }

    public function publish()
    {
        $this->template->displayResult();
    }

    public function redirect($url, $msg = null)
    {
        $this->template->setRedirect($url, $msg);
    }

    private function checkTranslation()
    {
        if (!isset(SHAKEFLAT_ENV["translation"]["path"])) $this::system("The path information for translation data does not exist in config.ini.");
        if (substr(SHAKEFLAT_ENV["translation"]["path"], 0, 1) == "/") {
            $path = rtrim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/";
        } else {
            $path = SHAKEFLAT_PATH . trim(SHAKEFLAT_ENV["translation"]["path"], " /") . "/";
        }
        if (!is_dir($path)) if (!mkdir($path, 0775, true)) $this::system("Failed to create path to translation file.", array("path" => $path));

        $cachePath = $path . "cache/";
        if (!is_dir($cachePath)) if (!mkdir($cachePath, 0775, true)) $this::system("Failed to create translation cache data storage path.", array("path"=>$cachePath));
        if (!is_writable($cachePath)) $this::system("You do not have write access to the translation data cache file.", array("path"=>$cachePath));

        if (!isset(SHAKEFLAT_ENV["translation"]["translation_file"])) $this::system("There is no data file information for translation in config.ini.");
        $allFile = $path . SHAKEFLAT_ENV["translation"]["translation_file"];
        if (!file_exists($allFile)) $this::system("Unable to read data file for translation.");

        //$json = file_get_contents($allFile);
        //if ($json === false) $this::system("Unable to read data file for translation.");
        //$arr = json_decode($json, true);
        //if (!$arr || !is_array($arr)) $this::system("The data file for translation is malformed.");
    }
}
