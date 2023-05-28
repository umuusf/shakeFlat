<?php
/**
 * core/core.php
 *
 * shakeFlat main
 *
 */

namespace shakeFlat;
use shakeFlat\Template;
use shakeFlat\Router;
use shakeFlat\L;
use shakeFlat\DB;
use shakeFlat\Modt;
use shakeFlat\Util;
use shakeFlat\Translation;

class App extends L
{
    private $transactionDB  = array();
    private $template       = null;
    private $gpath          = null;

    public function __construct()
    {
        $this->transactionDB = array();
        $this->gpath = GPath::getInstance();
        $this->template = Template::getInstance();
        $this->template->setMode(Template::MODE_WEB);
    }

    public function setTransaction($connectionName = "default")
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$connectionName])) $this::system("DB connection information is not defined in config.ini.", array( "connection" => $connectionName ));
        $this->transactionDB[] = $connectionName;
        return $this;
    }

    public function setPathModels($pathModels)
    {
        $this->gpath->MODELS = rtrim($pathModels, " /") . "/";
        return $this;
    }

    public function setPathDatabases($pathDatabases)
    {
        $this->gpath->DATATABLES = rtrim($pathDatabases, " /") . "/";
        return $this;
    }

    public function setPathModules($pathModules)
    {
        $this->gpath->MODULES = rtrim($pathModules . " /") . "/";
        return $this;
    }

    public function setPathTemplates($pathTemplates)
    {
        $this->template->setPathTemplates($pathTemplates);
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

    public function setResponseAES256Key($key, $iv = null)
    {
        $this->template->setAES256Key($key, $iv);
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

    public function setFilePathTranslation($filePath)
    {
        $translation = Translation::getInstance();
        $translation->setFilePathTranslation($filePath);
        return $this;
    }

    // Select the language to use when outputting results.
    // You need a translation file defined in translation session in config.ini.
    public function setTranslationLang($lang = null)
    {
        $this->template->setTranslationLang($lang);
        return $this;
    }

    public function setCharset($charset)
    {
        $this->template->setCharset($charset);
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
        $moduleFile = rtrim($this->gpath->MODULES, " /") . "/{$router->module()}/{$router->fnc()}.php";
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
}
