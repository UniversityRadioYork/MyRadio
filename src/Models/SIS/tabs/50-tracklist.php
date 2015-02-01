<?php
/**
 * Track Listing Tab for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\Config;

$moduleInfo = [
    'name' => 'tracklist',
    'title' => 'Track Listing',
    'enabled' => true,
    'pollfunc' => '\MyRadio\SIS\SIS_Remote::queryTracklist',
    'help' => 'Tracklisting is a legal requirement for '
        .Config::$short_name
        .' to broadcast, so you must fill this in. If you use BAPS this will be done automatically, '
        .'but if you use other sources you must fill this in yourself',
];
