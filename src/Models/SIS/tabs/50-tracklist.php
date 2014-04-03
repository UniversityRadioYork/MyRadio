<?php
/**
 * Track Listing Tab for SIS
 *
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130925
 * @package MyRadio_SIS
 */

$moduleInfo = array(
    'name' => 'tracklist',
    'title' => 'Track Listing',
    'enabled' => true,
    'pollfunc' => 'SIS_Remote::queryTracklist',
    'help' => 'Tracklisting is a legal requirement for '
        .Config::$short_name
        .' to broadcast, so you must fill this in. If you use BAPS this will be done automatically, '
        .'but if you use other sources you must fill this in yourself',
);
