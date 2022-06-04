<?php
/**
 * htdocs/index.php
 * shakeFlat Ver 0.1
 *
 */

require_once __DIR__ . "/../shakeFlat/core/autoloader.inc";

$app = new shakeFlat\App();
$app->setTransaction()->setTemplate("default")->setMode(shakeFlat\Template::MODE_WEB);
$app->execModule()->publish();
