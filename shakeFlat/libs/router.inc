<?php
/**
 * libs/router.inc
 *
 * By parsing URI, routing is processed for each module.
 *
 */

namespace shakeFlat\libs;

class Router
{
    private $moduleName     = "";
    private $functionName   = "";

    public static function getInstance()
    {
        static $instance = null;
        if ($instance) return $instance;
        $instance = new Router();
        return $instance;
    }

    private function __construct()
    {
        $this->parseUrl();
    }

    private function parseUrl()
    {
        $parseUrl = parse_url($_SERVER["REQUEST_URI"]);
        if (!isset($parseUrl["path"]) || $parseUrl["path"] == "/" || $parseUrl["path"] == "/index.php") {
            $mainPage = SHAKEFLAT_ENV["config"]["main_page"] ?? "welcome/main";
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
}