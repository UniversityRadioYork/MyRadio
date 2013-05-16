<?php
/**
 * 
 * @todo Proper Documentation
 * @todo Permissions
 * @author Andy Durant <aj@ury.org.uk>
 * @version 20130516
 * @package MyURY_Profile
 */

$members = Profile::getThisYearsMembers();
require 'Views/MyURY/Profile/list.php';
