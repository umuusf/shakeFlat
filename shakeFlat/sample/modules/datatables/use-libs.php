<?php
use shakeFlat\datatables\dtSample;
use shakeFlat\Response;

function fnc_use_libs()
{
    $dtSample = new dtSample("example");
    $dtSample->build();

    $res = Response::getInstance();
    $res->dtSample = $dtSample;
}