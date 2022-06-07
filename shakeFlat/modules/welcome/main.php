<?php
/**
 * modules/welcome/main.php
 *
 * module sample for home page.
 * When accessing http(s)://domain.com/ or http(s)://domain.com/welcome/main, this module is executed.
 */

use shakeFlat\libs\Param;
use shakeFlat\libs\Response;

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
