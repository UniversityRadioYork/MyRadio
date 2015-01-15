<?php
/**
 * Just empties out the active Joyride, marking it as done.
 *
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130722
 * @package MyRadio_Core
 */

use \MyRadio\MyRadio\URLUtils;

unset($_SESSION['joyride']);

URLUtils::nocontent();
