<?php
use shakeFlat\datatables\dtSampleOnePage;
use shakeFlat\Response;

function fnc_onepage($app)
{
    $dtSampleOnePage = new dtSampleOnePage("example");
    $dtSampleOnePage->build();

    $res = Response::getInstance();
    $res->dtSampleOnePage = $dtSampleOnePage;
}