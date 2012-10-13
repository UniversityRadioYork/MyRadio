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
 * 
 * Sets the $service_version Global Variable
 */

//Check if the user is allowed to select a version of the service
/**
 * AUTH_SELECTSERVICEVERSION = 270
 * @todo Remove hardcoded value
 */
if (CoreUtils::hasPermission(270)) {
  require 'Controllers/brokerVersion.php';
} else {
  $path = CoreUtils::getServiceVersionForUser(CoreUtils::getServiceID($service), $member);
  if ($path === false
      //MyURY is a special case - *everyone* has this build by default
      && $service !== 'MyURY') {
    //This user doesn't have permission to use that Service
    require 'Controllers/Errors/403.php';
    exit;
  }
  $service_version = $path['version'];
  set_include_path($path['path'].':'.get_include_path());
  $service_path = $path['path'];
  unset($path);
}