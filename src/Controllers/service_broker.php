<?php
/**
 * This file is for services other than MyRadio (for now...). It decides what version of a URY Web Service to point a
 * user to, with a seamless URL for everyone
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 20130525
 * @package MyRadio_Core
 * 
 * @uses $service - The current service being requested
 * @uses $member - The current user
 * 
 * @deprecated 20130525 There is now only one Service - MyRadio. Modules are not seperate.
 * @todo Remove unneccessary queries for services
 * 
 * Sets the $service_version Global Variable
 */

//Check if the user is allowed to select a version of the service
/**
 * AUTH_SELECTSERVICEVERSION = 270
 * @todo Remove hardcoded value
 * @todo fix redirection issues with the version selector
 */
if (CoreUtils::hasPermission(270)) {
  require 'Controllers/brokerVersion.php';
}

$path = CoreUtils::getServiceVersionForUser(User::getInstance());

$service_version = $path['version'];
set_include_path($path['path'].':'.get_include_path());
$service_path = $path['path'];
unset($path);