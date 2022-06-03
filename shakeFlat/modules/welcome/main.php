<?php
/**
 * modules/welcome/main.php
 *
 * module sample.
 * When accessing http(s)://domain.com/ or http(s)://domain.com/welcome/main, this module is executed.
 */

use shakeFlat\libs\Param;
use shakeFlat\libs\DB;
use shakeFlat\libs\Response;
use shakeFlat\libs\Util;
use shakeFlat\libs\L;
use shakeFlat\libs\AES256;
use shakeFlat\libs\Cookie;
use shakeFlat\libs\Token;
use shakeFlat\libs\AuthSession;
use shakeFlat\models\mUser;

function fnc_main()
{
    // use web template
    sfModeWEB();

    // check paramaters.
    $param = Param::getInstance();
    $param->check("age", Param::TYPE_INT);
    $param->check("name", Param::TYPE_STRING);

    // To do...

    // response
    $res = Response::getInstance();
    $res->age = $param->_d_age;
    $res->name = $param->_d_name;
    $res->msg = "This is a demo module.";
}
