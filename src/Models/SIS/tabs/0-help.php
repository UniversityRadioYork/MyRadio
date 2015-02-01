<?php
/**
 * Getting Started Tab for SIS
 *
 * @package MyRadio_SIS
 */

use \MyRadio\SIS\SIS_Utils;

$moduleInfo = [
    'name' => 'help',
    'title' => 'Getting Started',
    'enabled' => SIS_Utils::getShowHelpTab($_SESSION['memberid'])
];
