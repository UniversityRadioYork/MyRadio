<?php
/**
 * Emails data to Computing
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

MyRadioEmail::sendEmailToComputing(
    'Error Report',
    CoreUtils::getRequestInfo() . "\n" . print_r($_SESSION, true)
);