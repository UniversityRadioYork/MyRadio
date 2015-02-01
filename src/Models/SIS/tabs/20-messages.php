<?php
/**
 * Messages Tab for SIS
 *
 * @package MyRadio_SIS
 */


$moduleInfo = [
    'name' => 'messages',
    'title' => 'Messages',
    'enabled' => true,
    'help' => 'This is the big one, probably where you will spend most of your time in SIS. '
        .'The Message tab provides you with all the comunication you can get with the listener, '
        .'whether the message "Via the website" or text the studio it all comes here.',
    'pollfunc' => '\MyRadio\SIS\SIS_Remote::queryMessages'
];
