<?php
/**
 * htdocs/index.php
 * shakeFlat Ver 0.1
 *
 * This is a sample.
 * You can see how to make it work simply by using App(), which is the core class of shakeFlat .
 */

require_once __DIR__ . "/../shakeFlat/core/autoloader.inc";

$app = new shakeFlat\App();
$app->setTemplate("default")->setMode(shakeFlat\Template::MODE_WEB);
$app->execModule()->publish();
