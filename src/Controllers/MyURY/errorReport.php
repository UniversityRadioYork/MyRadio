<?php
/**
 * Emails data to Computing
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @package MyURY_Core
 */

MyURYEmail::sendEmailToComputing('Error Report', print_r(array($_REQUEST, $_SESSION), true));