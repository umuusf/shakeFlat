<?php
/**
 * modules/welcome/datatable_sample_ajax.php
 *
 * module sample for DataTable ajax
 *
 */

use shakeFlat\libs\Response;
use shakeFlat\libs\Util;
use shakeFlat\datatables\dtUser;

function fnc_datatable_sample_ajax($app)
{
    // use datatable ajax template
    sfModeAjaxForDatatable();

    $app->setTranslationLang("kr");

    $dtUser = dtUser::getInstance();
    $dtUser->ajaxResponse();
}
