<?php
/**
 * Just empties out the active Joyride, marking it as done.
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\URLUtils;

unset($_SESSION['joyride']);

URLUtils::nocontent();
