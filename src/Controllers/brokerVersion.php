<?php
/**
 * This file lets administrators choose a version of the service to use.
 * 
 * @author Lloyd Wallis <lpw@ury.org.uk>
 * @version 15082012
 * @package MyURY_Core
 * 
 * @uses $service - The current service being requested
 * @uses $member - The current user
 * 
 * Sets the $service_version Global Variable
 */

// Get a list of Service Versions for this Service, required by brokerVersionFrm
$versions = CoreUtils::getServiceVersions();

// If the version selector has just been submitted, update the session
require_once 'Models/Core/brokerVersionFrm.php'

// If the session already has a saved service version, use that
if (isset($_SESSION['myury_svc_version_'.$service])) {
  $service_version = $_SESSION['myury_svc_version_'.$service];
  $path = $_SESSION['myury_svc_version_'.$service.'_path'];
} else {
  
  // Shove the versions in the Form Definition and render
}
unset($form, $submitted_data, $versions);