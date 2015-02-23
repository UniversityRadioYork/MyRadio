<?php
/**
 * Controller for the focus Webcam Module. It's pretty much some webcams.
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 02082012
 * @package MyRadio_Webcam
 */

use \MyRadio\MyRadio\CoreUtils;
use \MyRadio\ServiceAPI\MyRadio_Webcam;

$streams = MyRadio_Webcam::getStreams();
$live = array_shift($streams);

CoreUtils::getTemplateObject()->setTemplate('Webcam/focus.twig')
    ->addVariable('streams', $streams)
    ->addVariable('live', $live)
    ->render();
