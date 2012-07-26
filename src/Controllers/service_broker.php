<?php
/**
 * This file is for services other than MyURY (for now...). It decides what version of a URY Web Service to point a
 * user to, with a seamless URL for everyone
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 26072012
 * @package MyURY_Core
 * 
 * @uses $service - The current service being requested
 * @uses $member - The current user
 */

//Check if the user is allowed to select a version of the service
/**
 * AUTH_SELECTSERVICEVERSION = 
 * @todo Remove hardcoded value
 */
if (CoreUtils::hasPermission(270)) {
  require 'Views/MyURY/brokerVersion.php';
} else {
  $path = CoreUtils::getServiceVersionForUser(CoreUtils::getServiceID($service), $member);
  if (!$path) {
    //This user doesn't have permission to use that Service
    require 'Controllers/Error/403.php';
    exit;
  }
  set_include_path($path.':'.get_include_path());
}