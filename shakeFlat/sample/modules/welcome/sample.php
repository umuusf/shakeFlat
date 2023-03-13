<?php
/**
 * modules/welcome/sample.php
 *
 * module sample.
 *
 */

use shakeFlat\Param;
use shakeFlat\DB;
use shakeFlat\Response;
use shakeFlat\Util;
use shakeFlat\L;
use shakeFlat\AES256;
use shakeFlat\Cookie;
use shakeFlat\Token;
use shakeFlat\AuthSession;
use shakeFlat\DataTable;
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
    $token = new Token();
    $newToken = $token->create( array( "name" => "David", "age" => 25, "login_id" => "david19" ) );

    // response
    $res = Response::getInstance();
    $res->age = $param->_d_age;
    $res->name = $param->name;
    $res->msg = "[0:This is a demo module.:]";
    //$res->row = $row;
    $res->token = $newToken;
    $res->payload = $token->payload();
}
