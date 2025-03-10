<?php
/**
 * libs\short_fnc.php
 *
 * Define frequently used method (in class) as a global function.
 * It will mainly be used in templates.
 */

function sfCSSJSVer()
{
    if (IS_DEBUG) return time();
    return SHAKEFLAT_ENV["config"]["jscss_version"] ?? 100;
}

function sfUnitFormatNumber($n, $isskip1000 = false)
{
    return shakeFlat\Util::unitFormatNumber($n, $isskip1000);
}

function sfKoreanJosa($ex)
{
    return shakeFlat\Util::koreanJosa($ex);
}

function sfNumberKorean($num)
{
    return shakeFlat\Util::numberKorean($num);
}

function sfTimeDiffMinSec($t1, $t2, $isKorean = true)
{
    return shakeFlat\Util::timeDiffMinSec($t1, $t2, $isKorean);
}

function sfTimeDiffPretty($time, $postTime = null, $isKorean = true)
{
    return shakeFlat\Util::timeDiffPretty($time, $postTime, $isKorean);
}

function sfValidateDate($date, $format = null)
{
    return shakeFlat\Util::validateDate($date, $format);
}

function sfYmdHis($date, $format = null)
{
    return shakeFlat\Util::YmdHis($date, $format);
}

function sfNumberFormatX($p, $c = "")
{
    return shakeFlat\Util::number_formatX($p, $c);
}

function sfCutString($p, $l, $with3Dot = true)
{
    return shakeFlat\Util::cutString($p, $l, $with3Dot);
}

function sfWebDump($p, $fontSize = 10)
{
    shakeFlat\Util::webDump($p, $fontSize);
}

function sfDebug($p, $c = array())
{
    shakeFlat\L::debug($p, $c);
}

// The mode defined in shakeFlat\Response is provided as a simple function.
function sfModeWEB()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_WEB);
}

function sfModeAjax()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_AJAX);
}

function sfModeCLI()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_CLI);
}

function sfIsAjax()
{
    $template = shakeFlat\Template::getInstance();
    return $template->isAjax();
}

function sfModeAjaxForDatatable()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_AJAX_FOR_DATATABLE);
}

function sfModeAPI()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_API);
}

function sfModeAPIEncrypt()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_API_ENCRYPT);
}

function sfModeAPIEncryptZip()
{
    $template = shakeFlat\Template::getInstance();
    $template->setMode(shakeFlat\Template::MODE_API_ENCRYPT_ZIP);
}

function sfRedirect($url, $msg = null)
{
    $template = shakeFlat\Template::getInstance();
    $template->setRedirect($url, $msg);
}

// If there is a message delivered when redirecting from the previous page, it is returned.
function sfRedirectMsg()
{
    $cookie = shakeFlat\Cookie::getInstance("_rm_");
    $msg = $cookie->msg;
    if ($msg) return $msg;
    return false;
}

// This command returns the currently set translation language. If no language is set, it will return $default.
function sfLang($default = null)
{
    $lang = shakeFlat\Translation::getInstance()->getTranslationLang();
    if (!$lang) return $default;
    return $lang;
}