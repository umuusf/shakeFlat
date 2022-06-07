<?php
/**
 * modules/welcome/sample.php
 *
 * module sample.
 *
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

function fnc_sample()
{
    // use web template
    sfModeWEB();

    // check paramaters.
    $param = Param::getInstance();
    $param->checkKeyValue("age", Param::TYPE_INT);
    $param->check("name", Param::TYPE_STRING);

    // To do...
    $db = DB::getInstance();
    $rs = $db->query("select * from user where age = :age", array( ":age" => $param->age ));
    $row = $db->fetch($rs);

    // response
    $res = Response::getInstance();
    $res->age = $param->age;
    $res->name = $param->_d_name;
    $res->msg = "This is a demo module.";
    $res->row = $row;
}
