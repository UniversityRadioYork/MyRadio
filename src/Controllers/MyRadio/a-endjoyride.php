<?php
/**
 * Just empties out the active Joyride, marking it as done.
 */
use \MyRadio\MyRadio\URLUtils;

unset($_SESSION['joyride']);

URLUtils::nocontent();
