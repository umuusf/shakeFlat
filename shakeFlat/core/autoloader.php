<?php
/**
 * core/autoloader.php
 *
 * auto loader
 *
 */

require_once "config.php";
require_once "short_fnc.php";
require_once "gpath.php";

// autoloader
spl_autoload_register(function($class) {
    switch($class) {
        case "shakeFlat\App" :          require_once SHAKEFLAT_PATH . "/core/core.php"; break;
        case "shakeFlat\Template" :     require_once SHAKEFLAT_PATH . "/core/template.php"; break;
        case "shakeFlat\Translation" :  require_once SHAKEFLAT_PATH . "/core/translation.php"; break;
        case "shakeFlat\Param" :        require_once SHAKEFLAT_PATH . "/core/param.php"; break;
        case "shakeFlat\AES256" :       require_once SHAKEFLAT_PATH . "/core/aes256.php"; break;
        case "shakeFlat\DB" :           require_once SHAKEFLAT_PATH . "/core/db.php"; break;
        case "shakeFlat\Ooro" :         require_once SHAKEFLAT_PATH . "/core/ooro.php"; break;
        case "shakeFlat\Modt" :         require_once SHAKEFLAT_PATH . "/core/modt.php"; break;
        case "shakeFlat\Router" :       require_once SHAKEFLAT_PATH . "/core/router.php"; break;
        case "shakeFlat\Response" :     require_once SHAKEFLAT_PATH . "/core/response.php"; break;
        case "shakeFlat\Token" :        require_once SHAKEFLAT_PATH . "/core/token.php"; break;
        case "shakeFlat\Cookie" :       require_once SHAKEFLAT_PATH . "/core/cookie.php"; break;
        case "shakeFlat\AuthCookie" :
        case "shakeFlat\AuthSession" :  require_once SHAKEFLAT_PATH . "/core/auth.php"; break;
        case "shakeFlat\DataTables" :   require_once SHAKEFLAT_PATH . "/core/datatables.php"; break;
        case "shakeFlat\L" :
        case "shakeFlat\Log" :
        case "shakeFlat\LogQuery" :
        case "shakeFlat\LogLevel" :     require_once SHAKEFLAT_PATH . "/core/log.php"; break;

        case "shakeFlat\Util" :         require_once SHAKEFLAT_PATH . "/core/util.php"; break;

        default :
            $gpath = shakeFlat\GPath::getInstance();
            if (substr($class, 0, 16) == "shakeFlat\models") {
                $modelName = substr($class, 17);
                require_once $gpath->MODELS . "{$modelName}.php";
            } elseif (substr($class, 0, 20) == "shakeFlat\datatables") {
                $dataTableName = substr($class, 21);
                require_once $gpath->DATATABLES . "{$dataTableName}.php";
            }
    }
});
