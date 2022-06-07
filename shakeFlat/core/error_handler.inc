<?php
/**
 * core/error_handler.inc
 *
 * custom error handler
 * When an error occurs, a log is recorded through a separate handler, and an error message is output using a template.
 */

use shakeFlat\libs\L;

// for waring
function sfErrorHandler($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
{
    if ($errfile && $errline) {
        $errfile = str_replace(SHAKEFLAT_PATH, "", $errfile);
        $errstr .= " in {$errfile}:$errline";
    }
    L::exit($errstr, $errcontext);
}

// for fatal
function sfErrorHandlerShutdown()
{
    $lastError = error_get_last();
    if($lastError !== null) {
        $msg = substr($lastError["message"], 0, strpos($lastError["message"], "\nStack trace:\n"));
        L::exit($msg);
    }
}
