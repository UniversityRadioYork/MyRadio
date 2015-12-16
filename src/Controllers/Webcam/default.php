<?php
/**
 * The default Controller for the Webcam Module. It's pretty much some webcams.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Webcam;

$streams = MyRadio_Webcam::getStreams();

CoreUtils::getTemplateObject()->setTemplate('Webcam/grid.twig')
    ->addVariable('streams', $streams)
    ->render();
