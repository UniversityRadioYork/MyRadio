<?php
/**
 * Emails data to Computing.
 */
use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;

MyRadioEmail::sendEmailToComputing(
    'Error Report',
    CoreUtils::getRequestInfo()."\n".print_r($_SESSION, true)
);
