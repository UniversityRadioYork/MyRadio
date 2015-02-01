<?php
/**
 * Webcam Plugin for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20130923
 * @package MyRadio_SIS
 */

use \MyRadio\Config;
use \MyRadio\ServiceAPI\MyRadio_Webcam;

$vars = [
    'webcam_prefix' => Config::$webcam_prefix,
    'cameras' => ['jukebox.jpg', 'studio1', 's1-fos', 'studio2'],
    'current' => MyRadio_Webcam::getCurrentWebcam()['current'],
    'streams' => MyRadio_Webcam::getStreams()
];

$moduleInfo = [
    'name' => 'webcam',
    'title' => 'Webcam Selector',
    'enabled' => true,
    'startOpen' => false,
    'pollfunc' => '\MyRadio\SIS\SIS_Remote::queryWebcam',
    'help' => 'You may have noticed that Studio 1 now has two webcams. '
        .'The Webcam section over to the left lets you choose which of '
        .'the station\'s cameras can be seen by listeners.',
    'vars' => $vars,
    'required_permission' => AUTH_MODIFYWEBCAM,
    'required_location' => true,
];
