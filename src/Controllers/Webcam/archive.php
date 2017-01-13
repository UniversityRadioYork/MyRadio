<?php
/**
 * Controller for viewing webcam archives.
 */
use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Webcam;

$streams = MyRadio_Webcam::getStreams();
//Skip "Live"
/*
 * @todo This is quite a nasty way of doing it. Is there a better one?
 */
 CoreUtils::getTemplateObject()->setTemplate('MyRadio/text.twig')
        ->addVariable('title', 'Webcams Archive')
        ->addInfo('Still coming soon to a MyRadio near you...')
        ->render();
//array_shift($streams);

//$times = MyRadio_Webcam::getArchiveTimeRange();


