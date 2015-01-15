<?php
/**
 * Allows Librarian-level officers to approve automatically-suggested rec database corrections.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130720
 * @package MyRadio_Library
 */

use \MyRadio\MyRadioException;
use \MyRadio\MyRadio\URLUtils;
use \MyRadio\ServiceAPI\MyRadio_TrackCorrection;

if (isset($_REQUEST['correctionid'])) {
    $correction = MyRadio_TrackCorrection::getInstance($_REQUEST['correctionid']);
} else {
    throw new MyRadioException('Correctionid is required!', 400);
}

$correction->apply(empty($_REQUEST['ignorealbum']) ? false : (bool) $_REQUEST['ignorealbum']);

URLUtils::backWithMessage('The correction was applied successfully!');
