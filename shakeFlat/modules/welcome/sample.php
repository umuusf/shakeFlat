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

function fnc_sample($app)
{
    // use web template
    sfModeWEB();

    $app->setTranslationLang("kr");

    // check paramaters.
    $param = Param::getInstance();
    $param->check("user_no", Param::TYPE_INT);
    $param->check("age", Param::TYPE_INT);
    $param->check("name", Param::TYPE_STRING);


    // DB Query test
    /*
    $db = DB::getInstance();
    $rs = $db->query("select * from user where age = :age", array( ":age" => $param->_d_age ));
    $row = $db->fetch($rs);
    */

    // OORO
    /*
    $mUser = mUser::getInstance($param->user_no);
    if ($mUser->name == $param->name) {
        $mUser->age = $param->_d_age;
    }
    */

    // response
    $res = Response::getInstance();
    $res->age = $param->_d_age;
    $res->name = $param->name;
    $res->msg = "[0:This is a demo module.:]";
    //$res->row = $row;
}
