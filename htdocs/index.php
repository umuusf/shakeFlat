<?php
/**
 * htdocs/index.php
 * shakeFlat Ver 0.1
 *
 * This is a sample.
 * You can see how to make it work simply by using App(), which is the core class of shakeFlat .
 */

require_once __DIR__ . "/../shakeFlat/core/autoloader.php";

$app = new shakeFlat\App();
$app->setMode(shakeFlat\Template::MODE_WEB)->execModule()->publish();
