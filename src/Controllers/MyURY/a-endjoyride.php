<?php
/**
 * Just empties out the active Joyride, marking it as done.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130722
 * @package MyURY_Core
 */

unset($_SESSION['joyride']);

require 'Views/MyURY/nocontent.php';