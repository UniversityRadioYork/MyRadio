<?php
/**
 * Emails data to Computing
 *
 * @author  Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

use \MyRadio\MyRadioEmail;
use \MyRadio\MyRadio\CoreUtils;

MyRadioEmail::sendEmailToComputing(
    'Error Report',
    CoreUtils::getRequestInfo() . "\n" . print_r($_SESSION, true)
);
