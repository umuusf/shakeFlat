<?php
/**
 * modules/welcome/datatable_onepage.php
 *
 * module sample for DataTable
 * Example of handling all modules in one page (file)
 *
 */

use shakeFlat\Response;
use shakeFlat\datatables\dtUserOnePage;

function fnc_datatable_onepage($app)
{
    // use web template
    sfModeWEB();

    $app->setTranslationLang("kr");

    $dtUserOnePage = dtUserOnePage::getInstance();
    $dtUserOnePage->onePageExec();

    // response
    $res = Response::getInstance();
    $res->msg = "Sample for DataTable... (onePage)";
    $res->dtUserOnePage = $dtUserOnePage;
}
