<?php
/**
 * Allows Librarian-level officers to reject automatically-suggested rec database corrections.
 * 
 * $_REQUEST['permanent'], default false, will also mark the data currently in the library as *correct*, meaning 
 * that the background scanner will not propose any corrections in future.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130722
 * @package MyURY_Library
 */

if (isset($_REQUEST['correctionid'])) {
  $correction = MyURY_TrackCorrection::getInstance($_REQUEST['correctionid']);
} else {
  throw new MyURYException('Correctionid is required!', 400);
}

$correction->reject(empty($_REQUEST['permanent']) ? false : (bool)$_REQUEST['permanent']);

CoreUtils::backWithMessage('The correction was applied succesfully!');