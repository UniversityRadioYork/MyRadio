<?php
/**
 * Emails data to Computing
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;

MyRadioEmail::sendEmailToComputing(
    'Error Report',
    CoreUtils::getRequestInfo() . "\n" . print_r($_SESSION, true)
);
