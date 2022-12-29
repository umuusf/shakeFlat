<?php
/**
 * modules/welcome/datatable_sample.php
 *
 * module sample for DataTable
 *
 */

use shakeFlat\Response;
use shakeFlat\Util;
use shakeFlat\datatables\dtUser;

function fnc_datatable_sample($app)
{
    // use web template
    sfModeWEB();

    $app->setTranslationLang("kr");

    $dtUser = dtUser::getInstance();

    // response
    $res = Response::getInstance();
    $res->msg = "Sample for DataTable...";
    $res->dtUser = $dtUser;
}
