<?php
/**
 * Presenter Infomation Tab for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\Config;

$moduleInfo = [
    'help' => 'Please read this before the start of your show. It\'s among the tabs up at the top and provides lots of '
        .'useful information from our great lord and master, <station manager name>. It\'s a great way to find out how '
        .'to get more involved in '.Config::$short_name.' or see what you events you can advertise on your show.',
    'pollfunc' => 'MyRadio\SIS\SIS_Remote::queryPresenterInfo'
];
