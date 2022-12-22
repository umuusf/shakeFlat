<?php
/**
 * core/autoloader.inc
 *
 * auto loader
 *
 */

require_once "config.inc";
require_once "short_fnc.inc";

// autoloader
spl_autoload_register(function($class) {
    switch($class) {
        case "shakeFlat\App" :              require_once "core.inc"; break;
        case "shakeFlat\Template" :         require_once "template.inc"; break;
        case "shakeFlat\Translation" :      require_once "translation.inc"; break;

        case "shakeFlat\libs\DB" :          require_once __DIR__ . "/../libs/db.inc"; break;
        case "shakeFlat\libs\Ooro" :        require_once __DIR__ . "/../libs/ooro.inc"; break;
        case "shakeFlat\libs\Modt" :        require_once __DIR__ . "/../libs/modt.inc"; break;
        case "shakeFlat\libs\Router" :      require_once __DIR__ . "/../libs/router.inc"; break;
        case "shakeFlat\libs\Param" :       require_once __DIR__ . "/../libs/param.inc"; break;
        case "shakeFlat\libs\Response" :    require_once __DIR__ . "/../libs/response.inc"; break;
        case "shakeFlat\libs\AES256" :      require_once __DIR__ . "/../libs/aes256.inc"; break;
        case "shakeFlat\libs\Token" :       require_once __DIR__ . "/../libs/token.inc"; break;
        case "shakeFlat\libs\Cookie" :      require_once __DIR__ . "/../libs/cookie.inc"; break;
        case "shakeFlat\libs\AuthCookie" :
        case "shakeFlat\libs\AuthSession" : require_once __DIR__ . "/../libs/auth.inc"; break;
        case "shakeFlat\libs\Util" :        require_once __DIR__ . "/../libs/util.inc"; break;
        case "shakeFlat\libs\DataTable" :   require_once __DIR__ . "/../libs/datatable.inc"; break;

        case "shakeFlat\libs\L" :
        case "shakeFlat\libs\Log" :
        case "shakeFlat\libs\LogQuery" :
        case "shakeFlat\libs\LogLevel" :    require_once __DIR__ . "/../libs/log.inc"; break;

        default :
            if (substr($class, 0, 16) == "shakeFlat\models") {
                $modelName = substr($class, 17);
                require_once __DIR__ . "/../models/{$modelName}.inc";
            } elseif (substr($class, 0, 20) == "shakeFlat\datatables") {
                $dataTableName = substr($class, 21);
                require_once __DIR__ . "/../datatables/{$dataTableName}.inc";
            }
    }
});
