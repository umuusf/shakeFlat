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
use shakeFlat\libs\Response;
use shakeFlat\libs\Util;

use \Exception;

class App extends L
{
    private $transactionDB  = array();
    private $pathConfig     = "";
    private $pathModule     = "";
    private $template       = null;

    public function __construct()
    {
        $config = Config::init();

        $this->transactionDB    = array();
        $this->pathConfig       = SHAKEFLAT_PATH . "config/";
        $this->pathModule       = SHAKEFLAT_PATH . "modules/";

        $this->template = Template::getInstance();
        $this->template->setMode(Template::MODE_WEB);
    }

    public function setTransaction($databaseConnection = "default")
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$databaseConnection])) $this->exit("[{$databaseConnection}] that does not exist.");
        $this->transactionDB[] = $databaseConnection;
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
        $this->template->setMode($mode);
        return $this;
    }

    // Executes one module.
    // Be sure to call exec after calling set related method first.
    public function execModule()
    {
        try {
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
            if (!file_exists($moduleFile)) throw new Exception("The file corresponding to module/function does not exist.");
            if (!include_once($moduleFile)) throw new Exception("The file corresponding to module/function cannot be included.");

            $fncName = "fnc_" . str_replace("-", "_", $router->fnc());
            if (!function_exists($fncName)) throw new Exception("A function corresponding to module/function does not exist.");
            call_user_func($fncName);

            // ooro commit
            $ooroList = Ooro::instanceList();
            if ($ooroList) {
                foreach($ooroList as $class => $pks) foreach($pks as $pk => $ooro) $ooro->update();
            }

            if ($this->transactionDB && $dbList) {
                foreach($dbList as $db) $db->commit();
            }

            return $this;
        } catch(Exception $e) {
            $this->exit($e->getMessage() . " ({code})", array("code"=>$e->getCode()));
        }
    }

    public function publish()
    {
        $this->template->displayResult();
    }
}
