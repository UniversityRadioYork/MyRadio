<?php

/**
 * This is the Root Controller - it is the backbone of everything MyRadio
 *
 * @package MyRadio_Core
 */

require_once __DIR__ . '/../Classes/MyRadioInit.php';
use \MyRadio\MyRadioInit;

$container = MyRadioInit::init(false);
