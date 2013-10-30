<?php
/**
 * Emails data to Computing
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyRadio_Core
 */

MyRadioEmail::sendEmailToComputing('Error Report', print_r(array($_REQUEST, $_SESSION), true));