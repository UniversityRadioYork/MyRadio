<?php

use MyRadio\MyRadio\URLUtils;
use MyRadio\MyRadioException;
use MyRadio\NIPSWeb\NIPSWeb_Views;
use MyRadio\ServiceAPI\MyRadio_Highlight;

if (!isset($_REQUEST['highlight_id'])) {
    throw new MyRadioException('No highlight ID specified', 400);
}

$hl = MyRadio_Highlight::getInstance($_REQUEST['highlight_id']);

try {
    $path = $hl->audioLogPath();
} catch (MyRadioException $e) {
    if ($e->getCode() === 403) {
        // lol audio logger
        URLUtils::backWithMessage('The audio clip is not ready yet.');
        exit();
    }
    throw $e;
}

NIPSWeb_Views::serveMP3($path);
