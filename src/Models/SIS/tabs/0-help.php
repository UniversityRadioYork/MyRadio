<?php
/**
 * Getting Started Tab for SIS
 *
 * @author  Andy Durant <aj@ury.org.uk>
 * @version 20131123
 * @package MyRadio_SIS
 */

use \MyRadio\SIS\SIS_Utils;

$moduleInfo = [
    'name' => 'help',
    'title' => 'Getting Started',
    'enabled' => SIS_Utils::getShowHelpTab($_SESSION['memberid'])
];
