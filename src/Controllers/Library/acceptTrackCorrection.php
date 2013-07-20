<?php
/**
 * Allows Librarian-level officers to approve automatically-suggested rec database corrections.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130720
 * @package MyURY_Library
 */

if (isset($_REQUEST['correctionid'])) {
  $correction = MyURY_TrackCorrection::getInstance($_REQUEST['correctionid']);
} else {
  throw new MyURYException('Correctionid is required!', 400);
}

$correction->apply();

CoreUtils::backWithMessage('The correction was applied succesfully!');