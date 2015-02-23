<?php
/**
 * Just empties out the active Joyride, marking it as done.
 *
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\CoreUtils;

unset($_SESSION['joyride']);

CoreUtils::nocontent();
