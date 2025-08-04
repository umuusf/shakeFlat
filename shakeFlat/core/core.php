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
use shakeFlat\TransactionDBList;
use shakeFlat\DB;
use shakeFlat\Modt;
use shakeFlat\Util;
use shakeFlat\Translation;

class App
{
    private $template = null;
    private $gpath    = null;

    public function __construct()
    {
        $this->gpath = GPath::getInstance();
        $this->template = Template::getInstance();
        $this->template->setMode(Template::MODE_WEB);

        $translation = Translation::getInstance();
        if (SHAKEFLAT_ENV["config"]["translation"] ?? false) {
            $translation->enable();
        } else {
            $translation->disable();
        }
    }

    public function setWelcomePage($module = "welcome", $fnc = "main")
    {
        $router = Router::getInstance();
        $router->setWelcomePage($module, $fnc);
        return $this;
    }

    public function setTransaction($connectionName = "default")
    {
        if (!isset(SHAKEFLAT_ENV["database"]["connection"][$connectionName])) L::system("[:DB connection information is not defined in config.ini.:]", array( "connection" => $connectionName ));
        $tdbList = TransactionDBList::getInstance();
        $tdbList->add($connectionName);
        return $this;
    }

    public function setLayoutFile($layoutFile)
    {
        $this->template->setLayoutFile($layoutFile);
        return $this;
    }

    public function setCustomTemplateFile($templateFile)
    {
        $this->template->setCustomTemplateFile($templateFile);
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
        if (!in_array($mode, array_values($constList))) L::system("[:This template mode does not exist.:]");
        $this->template->setMode($mode);
        return $this;
    }

    // If display_error is false in config.ini, set the error message to be displayed on the screen when an error occurs.
    public function setDefaultErrorMessage($msg)
    {
        L::defaultErrorMessage($msg);
    }

    public function translationEnable()
    {
        $translation = Translation::getInstance();
        $translation->enable();
        return $this;
    }

    public function translationDisable()
    {
        $translation = Translation::getInstance();
        $translation->disable();
        return $this;
    }

    // Select the language to use when outputting results.
    // You need a translation file defined in translation session in config.ini.
    public function setTranslationLang($lang = null)
    {
        $translation = Translation::getInstance();
        $translation->setTranslationLang($lang);
        return $this;
    }

    public function getTranslationLang()
    {
        $translation = Translation::getInstance();
        return $translation->getTranslationLang();
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
        $router = Router::getInstance();
        $moduleFile = rtrim($this->gpath->MODULES, " /") . "/{$router->module()}/{$router->fnc()}.php";
        if (!file_exists($moduleFile)) {
            if (IS_DEBUG) {
                L::system("[:The file corresponding to module/function({$router->module()}/{$router->fnc()}) does not exist.:]", array( "module" => $router->module(), "function" => $router->fnc() ));
            } else {
                L::system("잘못 된 접근입니다.");
            }
        }
        if (!include_once($moduleFile)) L::system("[:The file corresponding to module/function({$router->module()}/{$router->fnc()}) cannot be included.:]", array( "module" => $router->module(), "function" => $router->fnc() ));

        $fncName = "fnc_" . str_replace("-", "_", $router->fnc());
        if (!function_exists($fncName)) L::system("[:A function corresponding to module/function({$router->module()}/{$router->fnc()}) does not exist.:]", array( "module" => $router->module(), "function" => $router->fnc() ));

        $dbList = array();
        $tdbList = TransactionDBList::getInstance();
        if ($tdbList->list()) {
            foreach($tdbList->list() as $connectionName) {
                $db = DB::getInstance($connectionName);
                $db->beginTransaction();
                $dbList[] = $db;
            }
        }

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
