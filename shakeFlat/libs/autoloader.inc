<?php
/**
 * libs/autoloader.inc
 *
 * auto loader
 *
 */

require_once __DIR__ . "/../core/config.inc";
require_once SHAKEFLAT_PATH . "/libs/short_fnc.inc";
require_once SHAKEFLAT_PATH . "/libs/global_code.inc";

use shakeFlat\libs\GCode;

// autoloader
spl_autoload_register(function($class) {
    switch($class) {
        case "shakeFlat\App" :          require_once SHAKEFLAT_PATH . "/core/core.inc"; break;
        case "shakeFlat\Template" :     require_once SHAKEFLAT_PATH . "/core/template.inc"; break;
        case "shakeFlat\Translation" :  require_once SHAKEFLAT_PATH . "/core/translation.inc"; break;
        case "shakeFlat\Param" :        require_once SHAKEFLAT_PATH . "/core/param.inc"; break;
        case "shakeFlat\AES256" :       require_once SHAKEFLAT_PATH . "/core/aes256.inc"; break;
        case "shakeFlat\DB" :           require_once SHAKEFLAT_PATH . "/core/db.inc"; break;
        case "shakeFlat\Ooro" :         require_once SHAKEFLAT_PATH . "/core/ooro.inc"; break;
        case "shakeFlat\Modt" :         require_once SHAKEFLAT_PATH . "/core/modt.inc"; break;
        case "shakeFlat\Router" :       require_once SHAKEFLAT_PATH . "/core/router.inc"; break;
        case "shakeFlat\Response" :     require_once SHAKEFLAT_PATH . "/core/response.inc"; break;
        case "shakeFlat\Token" :        require_once SHAKEFLAT_PATH . "/core/token.inc"; break;
        case "shakeFlat\Cookie" :       require_once SHAKEFLAT_PATH . "/core/cookie.inc"; break;
        case "shakeFlat\AuthCookie" :
        case "shakeFlat\AuthSession" :  require_once SHAKEFLAT_PATH . "/core/auth.inc"; break;
        case "shakeFlat\DataTable" :    require_once SHAKEFLAT_PATH . "/core/datatable.inc"; break;
        case "shakeFlat\L" :
        case "shakeFlat\Log" :
        case "shakeFlat\LogQuery" :
        case "shakeFlat\LogLevel" :     require_once SHAKEFLAT_PATH . "/core/log.inc"; break;

        case "shakeFlat\Util" :         require_once SHAKEFLAT_PATH . "/core/util.inc"; break;

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

// Write your require and include code here...