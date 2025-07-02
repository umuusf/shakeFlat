<?php
/**
 * core/route.php
 *
 * By parsing URI, routing is processed for each module.
 *
 */

namespace shakeFlat;

class Router
{
    private $moduleName     = "";
    private $functionName   = "";
    private $welcomePage    = "welcome/page";

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Router();
        return $instance;
    }

    private function __construct()
    {
        $this->welcomePage = SHAKEFLAT_ENV["config"]["main_page"] ?? "welcome/main";
        $this->parseUrl();
    }

    public function setWelcomePage($module = "welcome", $fnc = "main")
    {
        $this->welcomePage = "{$module}/{$fnc}";
        $this->parseUrl();
    }

    private function parseUrl()
    {
        $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
        if (!isset($parseUrl["path"]) || $parseUrl["path"] == "/" || $parseUrl["path"] == "/index.php") {
            $mainPage = $this->welcomePage;
            $ml = explode("/", $mainPage);
            $this->moduleName      = $ml[0] ?? "welcome";
            $this->functionName    = $ml[1] ?? "main";
        } else {
            $pathList = explode("/", trim($parseUrl["path"], "/"));
            $this->moduleName      = $pathList[0] ?? "welcome";
            $this->functionName    = $pathList[1] ?? "main";
        }
    }

    public function module()
    {
        return $this->moduleName;
    }

    public function fnc()
    {
        return $this->functionName;
    }

    public function mf()
    {
        return $this->moduleName . "/" . $this->functionName;
    }
}