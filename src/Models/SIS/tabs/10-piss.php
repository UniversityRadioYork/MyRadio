<?php
/**
 * Presenter Infomation Tab for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\Config;
use \MyRadio\MyRadio\MyRadioNews;
use \MyRadio\ServiceAPI\MyRadio_User;

$vars = [
    'piss' => MyRadioNews::getLatestNewsItem(Config::$piss_feed, MyRadio_User::getInstance())
];

$moduleInfo = [
    'name' => 'piss',
    'title' => 'Presenter Information',
    'enabled' => true,
    'help' => 'Please read this before the start of your show. It\'s among the tabs up at the top and provides lots of '
        .'useful information from our great lord and master, <station manager name>. It\'s a great way to find out how '
        .'to get more involved in '.Config::$short_name.' or see what you events you can advertise on your show.',
    'vars' => $vars
];
